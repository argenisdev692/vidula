# Informe de Cumplimiento: Módulo Clients

**Fecha:** 2 de marzo de 2026  
**Módulo:** `src/Modules/Clients`  
**Arquitectura de referencia:** `.agents/skills/ARCHITECTURE-INTERMEDIATE-PHP.md`  
**Versión PHP:** 8.5

---

## Resumen Ejecutivo

El módulo Clients cumple **parcialmente** con la arquitectura especificada y las características de PHP 8.5. Tiene una base sólida pero requiere mejoras significativas.

### Puntuación General: 7/10

- ✅ Arquitectura hexagonal: **8/10**
- ⚠️ PHP 8.5 features: **6/10** (usa Property Hooks que NO están en PHP 8.5)
- ✅ Manejo de fechas: **10/10**
- ✅ Convenciones de nombres: **10/10**
- ✅ Cache management: **8/10**
- ⚠️ Exports: **5/10**
- ⚠️ Uso de readonly: **7/10**

---

## 1. Cumplimiento de Arquitectura Hexagonal

### ✅ Fortalezas

#### 1.1 Estructura de Carpetas
```
✅ Domain/
   ✅ Entities/
   ✅ ValueObjects/
   ✅ Events/
   ✅ Exceptions/
   ✅ Ports/
   ✅ Services/
   ✅ Enums/ (vacío)
   ✅ Specifications/
   ✅ Policies/

✅ Application/
   ✅ Commands/
   ✅ Queries/
   ✅ DTOs/
   ✅ Services/ (ClientDataTransformer)
   ✅ EventHandlers/
   ✅ IntegrationEvents/

✅ Infrastructure/
   ✅ Http/Controllers/
   ✅ Http/Export/
   ✅ Http/Requests/
   ✅ Http/Resources/
   ✅ Persistence/Eloquent/
   ✅ Persistence/Mappers/
   ✅ Persistence/Repositories/
   ✅ Routes/
   ✅ Utils/

✅ Providers/
✅ Tests/
```

**Cumplimiento:** 95% - Estructura casi perfecta.

### ⚠️ Problemas Críticos Identificados

#### 1.1 Property Hooks NO ESTÁN EN PHP 8.5

**PROBLEMA GRAVE:** El módulo usa Property Hooks que fueron propuestos pero NO incluidos en PHP 8.5 final.

```php
// ❌ INCORRECTO - Property Hooks NO existen en PHP 8.5
final readonly class Email
{
    public function __construct(
        public string $value {
            get => strtolower($this->value);
            set {
                $normalized = strtolower(trim($value));
                if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid email format: {$value}");
                }
                $this->value = $normalized;
            }
        }
    ) {}
}
```

**Archivos afectados:**
- `Domain/ValueObjects/Email.php`
- `Domain/ValueObjects/Coordinates.php`
- `Domain/ValueObjects/SocialLinks.php`
- `Domain/ValueObjects/PhoneNumber.php`

**Impacto:** El código NO funcionará en PHP 8.5 real. Causará errores de sintaxis.

#### 1.2 Client Entity NO es Readonly pero debería serlo

```php
// ❌ PROBLEMA - Propiedades mutables en entidad
final class Client extends AggregateRoot
{
    public function __construct(
        public readonly ClientId $id,      // ✅ readonly
        public readonly UserId $userId,    // ✅ readonly
        public string $companyName,        // ❌ mutable
        public ?string $email = null,      // ❌ mutable
        // ...
    ) {}
}
```

**Problema:** Mezcla propiedades readonly con mutables, violando inmutabilidad.

#### 1.3 ClientMapper NO usa Pipe Operator

```php
// ❌ ACTUAL - Sin pipe operator
public static function toDomain(ClientEloquentModel $model): Client
{
    return new Client(
        id: new ClientId($model->uuid),
        userId: new UserId($model->user?->uuid ?? ''),
        // ... muchas propiedades
    );
}
```

#### 1.4 Excel Export Accede Directamente a Eloquent

```php
// ❌ PROBLEMA - Namespace incorrecto y acceso directo
namespace Modules\Client\Infrastructure\Http\Export; // ❌ Client en lugar de Clients

use App\Models\Client as ClientEloquentModel; // ❌ Acceso a App\Models

public function query(): Builder
{
    $query = ClientEloquentModel::query() // ❌ Acceso directo a Eloquent
        ->select([/* ... */])
        // ...
}
```

**Problemas:**
1. Namespace incorrecto (`Client` vs `Clients`)
2. Accede a `App\Models` en lugar del modelo del módulo
3. No usa repositorio (viola arquitectura hexagonal)

#### 1.5 UpdateClientHandler Muta Entidad Directamente

```php
// ❌ PROBLEMA - Mutación directa
$client->companyName = $dto->companyName ?? $client->companyName;
$client->email = $dto->email ?? $client->email;
$client->phone = $dto->phone ?? $client->phone;
```

**Problema:** Viola inmutabilidad. Debería usar `clone with`.

---

## 2. Cumplimiento PHP 8.5

### ✅ Características Implementadas Correctamente

#### 2.1 Pipe Operator en ClientDataTransformer

```php
// ✅ EXCELENTE
public static function transformForExport(ClientReadModel $client): array
{
    return $client
        |> self::extractBaseData(...)
        |> self::addFormattedDates(...)
        |> self::addSocialLinks(...)
        |> self::addCoordinates(...);
}

#[\NoDiscard]
public static function sanitizeInput(array $input): array
{
    return $input
        |> self::trimStrings(...)
        |> self::normalizeUrls(...)
        |> self::validateCoordinates(...);
}
```

**Impacto:** ✅ Uso correcto y ejemplar del pipe operator.

#### 2.2 Atributo #[\NoDiscard]

```php
// ✅ BIEN
#[\NoDiscard]
public static function sanitizeInput(array $input): array { /* ... */ }
```

**Pero falta en:**
- Value Objects methods
- Transformer methods

### ❌ Características NO Implementadas o Incorrectas

#### 2.3 Property Hooks (NO EXISTEN EN PHP 8.5)

**CRÍTICO:** Todos los Value Objects usan Property Hooks que no existen.

**Solución:** Mover validación a constructor.

```php
// ✅ CORRECTO para PHP 8.5
final readonly class Email
{
    public function __construct(
        public string $value
    ) {
        $normalized = strtolower(trim($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$value}");
        }
        $this->value = $normalized;
    }

    #[\NoDiscard]
    public function getDomain(): string
    {
        return explode('@', $this->value)[1] ?? '';
    }

    #[\NoDiscard]
    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0] ?? '';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

#### 2.4 Clone With - NO UTILIZADO

```php
// ❌ ACTUAL - UpdateClientHandler
$client->companyName = $dto->companyName ?? $client->companyName;
$client->email = $dto->email ?? $client->email;
// ...
$this->repository->save($client);

// ✅ DEBERÍA SER
$updatedClient = clone($client, [
    'companyName' => $dto->companyName ?? $client->companyName,
    'email' => $dto->email ?? $client->email,
    'phone' => $dto->phone ?? $client->phone,
    'address' => $dto->address ?? $client->address,
    'socialLinks' => new SocialLinks(/* ... */),
    'coordinates' => new Coordinates(/* ... */),
]);

$this->repository->save($updatedClient);
```

#### 2.5 URI Extension - NO UTILIZADA

```php
// ❌ ACTUAL - SocialLinks con Property Hooks
public ?string $facebook {
    set => $this->facebook = $value !== null ? filter_var($value, FILTER_VALIDATE_URL) ? $value : null : null
}

// ✅ DEBERÍA SER con URI Extension
use Uri\WhatWg\Url;

final readonly class SocialLinks
{
    public function __construct(
        public ?string $facebook = null,
        // ...
    ) {
        $this->validateUrls();
    }

    private function validateUrls(): void
    {
        foreach (['facebook', 'instagram', 'linkedin', 'twitter', 'website'] as $field) {
            if ($this->$field !== null && $this->$field !== '') {
                try {
                    new Url($this->$field);
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException("Invalid URL for {$field}");
                }
            }
        }
    }
}
```

---

## 3. Manejo de Fechas

### ✅ EXCELENTE - Cumplimiento 100%

```php
// ✅ PERFECTO - ClientMapper.php
createdAt: $model->created_at?->toIso8601String(),
updatedAt: $model->updated_at?->toIso8601String(),
deletedAt: $model->deleted_at?->toIso8601String()

// ✅ PERFECTO - Client.php
public ?string $createdAt = null,
public ?string $updatedAt = null,
public ?string $deletedAt = null
```

---

## 4. Cache Management

### ✅ Bueno - Cumplimiento 80%

#### 4.1 List Cache con Tags

```php
// ✅ BIEN - ListClientHandler
try {
    return Cache::tags(['clients_list'])->remember($cacheKey, $ttl, function () {
        return $this->fetchData($filters);
    });
} catch (\Exception $e) {
    return Cache::remember($cacheKey, $ttl, function () {
        return $this->fetchData($filters);
    });
}
```

#### 4.2 Cache Invalidation

```php
// ✅ BIEN - En repository y handlers
try {
    Cache::tags(['clients_list'])->flush();
} catch (\Exception $e) {}
```

### ⚠️ Área de Mejora

**Falta:** Cache individual para GetClient query.

```php
// ❌ FALTA implementar
// GetClientHandler debería cachear cliente individual
$cacheKey = "client_read_{$query->uuid}";
return Cache::remember($cacheKey, $ttl, function () { /* ... */ });
```

---

## 5. Exports

### ⚠️ Problemas Críticos - 5/10

#### 5.1 ClientExcelExport

**Problemas:**

1. **Namespace incorrecto:**
```php
// ❌ INCORRECTO
namespace Modules\Client\Infrastructure\Http\Export;

// ✅ CORRECTO
namespace Modules\Clients\Infrastructure\Http\Export;
```

2. **Acceso directo a App\Models:**
```php
// ❌ INCORRECTO
use App\Models\Client as ClientEloquentModel;

// ✅ CORRECTO
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
```

3. **No usa repositorio:**
```php
// ❌ INCORRECTO
public function query(): Builder
{
    return ClientEloquentModel::query()
        ->select([/* ... */])
        // ...
}

// ✅ CORRECTO - Debería usar repositorio como PDF export
```

4. **No usa transformer:**
```php
// ❌ ACTUAL
public function map($company): array
{
    return [
        $company->id,
        $company->uuid,
        // ... lista larga
    ];
}

// ✅ DEBERÍA usar ClientExportTransformer
```

#### 5.2 ClientPdfExport

**✅ Fortalezas:**
- Usa handler (arquitectura correcta)
- Usa query object

**⚠️ Problemas:**
- No transforma datos con pipe operator
- Pasa ReadModels directamente a vista

```php
// ❌ ACTUAL
$result = $this->handler->handle($this->query);
$rows = $result['data']; // ReadModels directos

// ✅ DEBERÍA transformar
$result = $this->handler->handle($this->query);
$rows = $result['data']
    |> (fn($clients) => array_map(ClientExportTransformer::transformForPdf(...), $clients));
```

---

## 6. Recomendaciones Prioritarias

### 🔴 Alta Prioridad (CRÍTICO)

1. **Eliminar Property Hooks (NO existen en PHP 8.5)**
   - Impacto: CRÍTICO
   - Esfuerzo: Alto
   - Archivos: Email, Coordinates, SocialLinks, PhoneNumber
   - Beneficio: Código funcionará en PHP 8.5 real

2. **Hacer Client Entity Inmutable**
   - Impacto: Alto
   - Esfuerzo: Medio
   - Beneficio: Arquitectura correcta

3. **Usar Clone With en UpdateClientHandler**
   - Impacto: Alto
   - Esfuerzo: Bajo
   - Beneficio: Inmutabilidad correcta

4. **Refactorizar ClientExcelExport**
   - Impacto: Alto
   - Esfuerzo: Medio
   - Beneficio: Arquitectura hexagonal correcta

### 🟡 Media Prioridad

5. **Agregar Pipe Operator en ClientMapper**
   - Impacto: Medio
   - Esfuerzo: Bajo
   - Beneficio: Consistencia

6. **Usar URI Extension en SocialLinks**
   - Impacto: Medio
   - Esfuerzo: Bajo
   - Beneficio: Validación robusta

7. **Agregar Cache Individual en GetClient**
   - Impacto: Medio
   - Esfuerzo: Bajo
   - Beneficio: Performance

8. **Crear ClientExportTransformer**
   - Impacto: Medio
   - Esfuerzo: Medio
   - Beneficio: Centralización

### 🟢 Baja Prioridad

9. **Agregar #[\NoDiscard] a Value Objects**
   - Impacto: Bajo
   - Esfuerzo: Bajo
   - Beneficio: Prevención de errores

10. **Agregar Tests Comprehensivos**
    - Impacto: Alto (largo plazo)
    - Esfuerzo: Alto
    - Beneficio: Confianza

---

## 7. Plan de Acción Detallado

### Fase 1: Correcciones Críticas (2-3 días)

**Día 1: Eliminar Property Hooks**
- [ ] Refactorizar Email.php
- [ ] Refactorizar Coordinates.php
- [ ] Refactorizar SocialLinks.php
- [ ] Refactorizar PhoneNumber.php
- [ ] Mover validación a constructores
- [ ] Agregar #[\NoDiscard] a métodos

**Día 2: Inmutabilidad**
- [ ] Hacer Client entity completamente readonly
- [ ] Implementar clone with en UpdateClientHandler
- [ ] Agregar métodos with* en Client si necesario

**Día 3: Excel Export**
- [ ] Corregir namespace
- [ ] Usar modelo correcto del módulo
- [ ] Implementar con repositorio
- [ ] Crear ClientExportTransformer
- [ ] Usar transformer en map()

### Fase 2: Mejoras Arquitectónicas (2-3 días)

**Día 4: Mappers y Transformers**
- [ ] Agregar pipe operator en ClientMapper
- [ ] Refactorizar ClientDataTransformer
- [ ] Crear ClientExportTransformer completo
- [ ] Usar en Excel y PDF exports

**Día 5: URI Extension y Cache**
- [ ] Implementar URI extension en SocialLinks
- [ ] Agregar cache individual en GetClientHandler
- [ ] Optimizar cache keys

**Día 6: PDF Export**
- [ ] Refactorizar con pipe operator
- [ ] Usar ClientExportTransformer
- [ ] Mejorar vista PDF

### Fase 3: Polish y Testing (2 días)

**Día 7: Refinamiento**
- [ ] Agregar #[\NoDiscard] donde falta
- [ ] Revisar convenciones de nombres
- [ ] Documentar patrones

**Día 8: Testing**
- [ ] Unit tests para Value Objects
- [ ] Integration tests para exports
- [ ] Feature tests para CRUD

---

## 8. Comparación con Módulo Users

| Aspecto | Users | Clients | Diferencia |
|---------|-------|---------|------------|
| Arquitectura | 9/10 | 8/10 | Users mejor |
| PHP 8.5 | 10/10 | 6/10 | Clients usa Property Hooks inexistentes |
| Fechas | 10/10 | 10/10 | Igual |
| Cache | 10/10 | 8/10 | Users mejor |
| Exports | 10/10 | 5/10 | Users mucho mejor |
| Inmutabilidad | 10/10 | 7/10 | Clients muta entidades |

---

## 9. Conclusión

El módulo Clients tiene una **base sólida** pero requiere **correcciones críticas**:

### Problemas Críticos:
1. ❌ Usa Property Hooks que NO existen en PHP 8.5
2. ❌ Excel export viola arquitectura hexagonal
3. ❌ UpdateClientHandler muta entidades directamente
4. ❌ No usa clone with

### Fortalezas:
1. ✅ ClientDataTransformer usa pipe operator correctamente
2. ✅ Manejo de fechas perfecto
3. ✅ Cache management bueno
4. ✅ PDF export usa arquitectura correcta

**Calificación Actual: 7/10**  
**Calificación Objetivo: 10/10**  
**Tiempo Estimado: 6-8 días de desarrollo**

---

**Elaborado por:** Kiro AI Assistant  
**Basado en:** PHP 8.5 Features y Architecture Guidelines  
**Próxima acción:** Implementar Fase 1 (Correcciones Críticas)
