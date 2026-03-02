# Informe de Cumplimiento: Módulo Students

**Fecha:** 2 de marzo de 2026  
**Módulo:** `src/Modules/Students`  
**Arquitectura de referencia:** `.agents/skills/ARCHITECTURE-INTERMEDIATE-PHP.md`  
**Versión PHP:** 8.5

---

## Resumen Ejecutivo

El módulo Students presenta **problemas críticos** de arquitectura y cumplimiento con PHP 8.5. Requiere refactorización significativa.

### Puntuación General: 4/10

- ❌ Arquitectura hexagonal: **4/10** (estructura incompleta, inconsistencias graves)
- ❌ PHP 8.5 features: **2/10** (no usa ninguna característica nueva)
- ⚠️ Manejo de fechas: **8/10** (mapper correcto, pero entity incompleta)
- ❌ Convenciones de nombres: **3/10** (inconsistencias críticas)
- ❌ Cache management: **5/10** (sin tags, sin invalidación)
- ❌ Domain logic: **3/10** (entity anémica, sin métodos de negocio)
- ❌ Value Objects: **4/10** (sin validación, sin property hooks)

---

## 1. Problemas Críticos de Arquitectura

### 🔴 CRÍTICO 1: Entity Anémica Sin Lógica de Negocio

```php
// ❌ ACTUAL - Student.php
class Student
{
    public function __construct(
        public readonly StudentId $id,
        public readonly string $name,
        public readonly ?string $email,
        // ... solo propiedades
    ) {}
}
```


**Problemas:**
- No tiene método `create()` estático
- No tiene método `update()` para inmutabilidad
- No extiende `AggregateRoot` (no maneja eventos de dominio)
- No tiene métodos de negocio
- No valida invariantes de dominio

**Comparación con Clients (correcto):**
```php
// ✅ CORRECTO - Client.php del módulo Clients
final class Client extends AggregateRoot
{
    public static function create(
        ClientId $id,
        UserId $userId,
        string $companyName,
        string $email,
        // ...
    ): self {
        $client = new self(/* ... */);
        $client->recordEvent(new ClientCreated(/* ... */));
        return $client;
    }

    public function update(/* ... */): self {
        return clone($this, [
            'companyName' => $companyName,
            // ...
        ]);
    }
}
```

### 🔴 CRÍTICO 2: Inconsistencias de Namespace

**Problema:** El módulo usa `Modules\Student` (singular) en lugar de `Modules\Students` (plural).


```php
// ❌ INCORRECTO - Archivos usan Student (singular)
namespace Modules\Student\Application\Commands\CreateStudent;
namespace Modules\Student\Domain\Entities;
namespace Modules\Student\Infrastructure\Persistence\Repositories;

// ✅ CORRECTO - Debería ser Students (plural)
namespace Modules\Students\Application\Commands\CreateStudent;
namespace Modules\Students\Domain\Entities;
namespace Modules\Students\Infrastructure\Persistence\Repositories;

// ⚠️ PERO el modelo Eloquent usa Students (plural)
namespace Modules\Students\Infrastructure\Persistence\Eloquent\Models;
```

**Impacto:** Inconsistencia total que causa confusión y errores.

### 🔴 CRÍTICO 3: CreateStudentHandler Incompleto

```php
// ❌ ACTUAL - CreateStudentHandler.php
$student = Student::create(
    id: new StudentId($uuid),
    userId: new UserId($dto->userUuid),
    companyName: $dto->companyName,  // ❌ Student no tiene companyName
    email: $dto->email,
    phone: $dto->phone,
    address: $dto->address,
    status: CompanyStatus::Active     // ❌ Student no tiene status
);
```

**Problemas:**
1. Llama a `Student::create()` que NO existe
2. Usa propiedades que NO están en Student entity
3. Usa `CompanyStatus` en lugar de estado de estudiante
4. No coincide con la estructura real de Student


### 🔴 CRÍTICO 4: UpdateStudentHandler Usa Propiedades Inexistentes

```php
// ❌ ACTUAL - UpdateStudentHandler.php
$updatedStudent = $student->update(
    companyName: $dto->companyName,  // ❌ NO existe en Student
    email: $dto->email,
    phone: $dto->phone,
    address: $dto->address,
    socialLinks: new SocialLinks(/* ... */),  // ❌ NO existe en Student
    coordinates: new Coordinates(/* ... */)   // ❌ NO existe en Student
);
```

**Problemas:**
1. Student entity NO tiene `companyName`, `socialLinks`, `coordinates`
2. Llama a método `update()` que NO existe
3. Código completamente desconectado de la realidad

### 🔴 CRÍTICO 5: ReadModel Desconectado de Entity

```php
// ❌ StudentReadModel.php
class StudentReadModel extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $dni,
        public ?string $birth_date,
        public ?string $address,
        public ?string $avatar,
        public ?string $notes,
        public bool $active,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $deleted_at
    ) {}
}
```


**Pero ListStudentHandler usa propiedades diferentes:**
```php
// ❌ ListStudentHandler.php
new StudentReadModel(
    uuid: $student->id->value,           // ❌ ReadModel espera 'id', no 'uuid'
    userUuid: $student->userId->value,   // ❌ ReadModel NO tiene 'userUuid'
    companyName: $student->companyName,  // ❌ ReadModel NO tiene 'companyName'
    socialLinks: $student->socialLinks->toArray(),  // ❌ NO existe
    coordinates: $student->coordinates->toArray(),  // ❌ NO existe
    status: $student->status->value,     // ❌ ReadModel NO tiene 'status'
    signatureUrl: $student->signaturePath  // ❌ ReadModel NO tiene 'signatureUrl'
);
```

**Impacto:** El código NO puede funcionar. Causará errores fatales.

---

## 2. Cumplimiento PHP 8.5

### ❌ NO USA NINGUNA CARACTERÍSTICA DE PHP 8.5

**Características ausentes:**

#### 2.1 Pipe Operator - NO USADO
```php
// ❌ ACTUAL - Sin pipe operator
$result['data'] = array_map(
    fn($student) => new StudentReadModel(/* ... */),
    $result['data']
);

// ✅ DEBERÍA SER
$result['data'] = $result['data']
    |> (fn($students) => array_map(StudentMapper::toReadModel(...), $students));
```


#### 2.2 Property Hooks - NO USADO (pero debería)

```php
// ❌ ACTUAL - Coordinates.php sin validación
final readonly class Coordinates
{
    public function __construct(
        public ?float $latitude,
        public ?float $longitude
    ) {}
}

// ✅ DEBERÍA SER (sin property hooks, validación en constructor)
final readonly class Coordinates
{
    public function __construct(
        public ?float $latitude,
        public ?float $longitude
    ) {
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90');
        }
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180');
        }
    }

    #[\NoDiscard]
    public function hasValues(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
```

#### 2.3 Clone With - NO USADO

```php
// ❌ ACTUAL - No hay método update en Student

// ✅ DEBERÍA SER
public function update(
    string $name,
    ?string $email,
    // ...
): self {
    return clone($this, [
        'name' => $name,
        'email' => $email,
        // ...
    ]);
}
```


#### 2.4 #[\NoDiscard] - NO USADO

```php
// ❌ ACTUAL - Sin atributo
public function hasValues(): bool { /* ... */ }
public function toArray(): array { /* ... */ }

// ✅ DEBERÍA SER
#[\NoDiscard]
public function hasValues(): bool { /* ... */ }

#[\NoDiscard]
public function toArray(): array { /* ... */ }
```

#### 2.5 URI Extension - NO USADO

```php
// ❌ ACTUAL - SocialLinks sin validación
final readonly class SocialLinks
{
    public function __construct(
        public ?string $facebook = null,
        public ?string $instagram = null,
        // ... sin validación
    ) {}
}

// ✅ DEBERÍA SER
use Uri\WhatWg\Url;

final readonly class SocialLinks
{
    public function __construct(
        public ?string $facebook = null,
        public ?string $instagram = null,
        public ?string $linkedin = null,
        public ?string $twitter = null,
        public ?string $website = null
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
                    throw new \InvalidArgumentException("Invalid URL for {$field}: {$this->$field}");
                }
            }
        }
    }
}
```


---

## 3. Manejo de Fechas

### ⚠️ Parcialmente Correcto - 8/10

#### ✅ Mapper Correcto

```php
// ✅ BIEN - StudentMapper.php
createdAt: $model->created_at?->toIso8601String(),
updatedAt: $model->updated_at?->toIso8601String(),
deletedAt: $model->deleted_at?->toIso8601String()
```

#### ✅ Entity Correcto

```php
// ✅ BIEN - Student.php
public readonly ?string $createdAt = null,
public readonly ?string $updatedAt = null,
public readonly ?string $deletedAt = null
```

#### ❌ ReadModel Incorrecto

```php
// ❌ INCORRECTO - StudentReadModel.php usa snake_case
public ?string $created_at,
public ?string $updated_at,
public ?string $deleted_at

// ✅ DEBERÍA SER camelCase
public ?string $createdAt,
public ?string $updatedAt,
public ?string $deletedAt
```

---

## 4. Cache Management

### ⚠️ Básico Sin Optimizaciones - 5/10

#### ❌ Sin Cache Tags

```php
// ❌ ACTUAL - ListStudentHandler.php
return Cache::remember($cacheKey, $ttl, function () use ($filters) {
    // ...
});

// ✅ DEBERÍA SER
try {
    return Cache::tags(['students_list'])->remember($cacheKey, $ttl, function () use ($filters) {
        return $this->fetchData($filters);
    });
} catch (\Exception $e) {
    return Cache::remember($cacheKey, $ttl, function () use ($filters) {
        return $this->fetchData($filters);
    });
}
```


#### ❌ Sin Invalidación de Cache

```php
// ❌ FALTA - En CreateStudentHandler, UpdateStudentHandler, DeleteStudentHandler
// No hay invalidación de cache después de mutaciones

// ✅ DEBERÍA AGREGAR
Cache::forget("student_{$userId->value}");
try {
    Cache::tags(['students_list'])->flush();
} catch (\Exception $e) {
    // Tags not supported
}
```

---

## 5. Estructura de Carpetas vs Arquitectura

### ⚠️ Estructura Presente pero Vacía

```
✅ Domain/
   ✅ Entities/ (Student.php existe pero anémico)
   ✅ ValueObjects/ (existen pero sin validación)
   ✅ Events/ (vacío excepto StudentUpdated)
   ✅ Exceptions/ (solo StudentNotFoundException)
   ✅ Ports/ (solo StudentRepositoryPort)
   ❌ Services/ (vacío)
   ⚠️ Enums/ (CompanyStatus - nombre incorrecto)
   ❌ Specifications/ (vacío)
   ❌ Policies/ (vacío)
   ❌ Subscribers/ (vacío)

✅ Application/
   ✅ Commands/ (existen pero con errores)
   ✅ Queries/ (existen pero con errores)
   ✅ DTOs/ (existen)
   ❌ Services/ (vacío)
   ❌ EventHandlers/ (vacío)
   ❌ IntegrationEvents/ (vacío)

✅ Infrastructure/
   ✅ Http/Controllers/ (estructura existe)
   ✅ Http/Requests/ (estructura existe)
   ✅ Http/Resources/ (estructura existe)
   ⚠️ Http/Export/ (estructura existe)
   ✅ Persistence/Eloquent/ (existe)
   ✅ Persistence/Mappers/ (existe)
   ✅ Persistence/Repositories/ (existe)
   ✅ Routes/ (existe)
   ⚠️ Utils/ (StudentHelper.php)
```


---

## 6. Comparación con Módulo Clients

| Aspecto | Clients | Students | Diferencia |
|---------|---------|----------|------------|
| Arquitectura | 8/10 | 4/10 | Clients mucho mejor |
| PHP 8.5 | 6/10 | 2/10 | Clients usa pipe operator |
| Fechas | 10/10 | 8/10 | Clients perfecto |
| Cache | 8/10 | 5/10 | Clients usa tags |
| Entity Logic | 8/10 | 3/10 | Students anémico |
| Value Objects | 7/10 | 4/10 | Students sin validación |
| Consistency | 9/10 | 3/10 | Students inconsistente |
| Exports | 5/10 | ? | Students no revisado |

**Conclusión:** Students está significativamente por debajo de Clients.

---

## 7. Problemas Específicos Detallados

### 7.1 Student Entity

**Problemas:**
1. No extiende `AggregateRoot`
2. No tiene método `create()` estático
3. No tiene método `update()` para inmutabilidad
4. No registra eventos de dominio
5. No valida invariantes
6. Propiedades no coinciden con handlers

**Solución:**
```php
final class Student extends AggregateRoot
{
    private function __construct(
        public readonly StudentId $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $dni,
        public readonly ?string $birthDate,
        public readonly ?string $address,
        public readonly ?string $avatar,
        public readonly ?string $notes,
        public readonly bool $active,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $deletedAt = null
    ) {}

    public static function create(
        StudentId $id,
        string $name,
        ?string $email = null,
        ?string $phone = null,
        ?string $dni = null,
        ?string $birthDate = null,
        ?string $address = null,
        ?string $avatar = null,
        ?string $notes = null,
        bool $active = true
    ): self {
        $student = new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            dni: $dni,
            birthDate: $birthDate,
            address: $address,
            avatar: $avatar,
            notes: $notes,
            active: $active,
            createdAt: now()->toIso8601String()
        );

        $student->recordEvent(new StudentCreated(
            aggregateId: $id->value,
            name: $name,
            occurredOn: now()->toDateTimeString()
        ));

        return $student;
    }

    public function update(
        string $name,
        ?string $email,
        ?string $phone,
        ?string $dni,
        ?string $birthDate,
        ?string $address,
        ?string $notes,
        bool $active
    ): self {
        $updated = clone($this, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dni' => $dni,
            'birthDate' => $birthDate,
            'address' => $address,
            'notes' => $notes,
            'active' => $active,
            'updatedAt' => now()->toIso8601String()
        ]);

        $updated->recordEvent(new StudentUpdated(
            aggregateId: $this->id->value,
            name: $name,
            occurredOn: now()->toDateTimeString()
        ));

        return $updated;
    }

    public function deactivate(): self
    {
        return clone($this, ['active' => false]);
    }

    public function activate(): self
    {
        return clone($this, ['active' => true]);
    }
}
```


### 7.2 Value Objects Sin Validación

**Coordinates.php:**
```php
// ❌ ACTUAL - Sin validación
final readonly class Coordinates
{
    public function __construct(
        public ?float $latitude,
        public ?float $longitude
    ) {}
}

// ✅ CORRECTO
final readonly class Coordinates
{
    public function __construct(
        public ?float $latitude,
        public ?float $longitude
    ) {
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            throw new \InvalidArgumentException(
                "Latitude must be between -90 and 90, got: {$latitude}"
            );
        }
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            throw new \InvalidArgumentException(
                "Longitude must be between -180 and 180, got: {$longitude}"
            );
        }
    }

    #[\NoDiscard]
    public function hasValues(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    #[\NoDiscard]
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    #[\NoDiscard]
    public function distanceTo(self $other): ?float
    {
        if (!$this->hasValues() || !$other->hasValues()) {
            return null;
        }
        // Haversine formula
        $earthRadius = 6371; // km
        $latDiff = deg2rad($other->latitude - $this->latitude);
        $lonDiff = deg2rad($other->longitude - $this->longitude);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($other->latitude)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
```


**SocialLinks.php:**
```php
// ✅ CORRECTO con URI Extension
use Uri\WhatWg\Url;

final readonly class SocialLinks
{
    public function __construct(
        public ?string $facebook = null,
        public ?string $instagram = null,
        public ?string $linkedin = null,
        public ?string $twitter = null,
        public ?string $website = null
    ) {
        $this->validateUrls();
    }

    private function validateUrls(): void
    {
        $fields = ['facebook', 'instagram', 'linkedin', 'twitter', 'website'];
        
        foreach ($fields as $field) {
            if ($this->$field !== null && $this->$field !== '') {
                try {
                    new Url($this->$field);
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException(
                        "Invalid URL for {$field}: {$this->$field}"
                    );
                }
            }
        }
    }

    #[\NoDiscard]
    public function hasAny(): bool
    {
        return $this->facebook !== null
            || $this->instagram !== null
            || $this->linkedin !== null
            || $this->twitter !== null
            || $this->website !== null;
    }

    #[\NoDiscard]
    public function toArray(): array
    {
        return [
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'linkedin' => $this->linkedin,
            'twitter' => $this->twitter,
            'website' => $this->website,
        ];
    }
}
```


### 7.3 Handlers Desconectados de la Realidad

**CreateStudentHandler - Corrección:**
```php
final readonly class CreateStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {}

    public function handle(CreateStudentCommand $command): void
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $student = Student::create(
            id: new StudentId($uuid),
            name: $dto->name,
            email: $dto->email,
            phone: $dto->phone,
            dni: $dto->dni,
            birthDate: $dto->birth_date,
            address: $dto->address,
            avatar: $dto->avatar,
            notes: $dto->notes,
            active: $dto->active
        );

        $this->repository->save($student);

        // Clear cache
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
```

**UpdateStudentHandler - Corrección:**
```php
final readonly class UpdateStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {}

    public function handle(UpdateStudentCommand $command): void
    {
        $student = $this->repository->findById(new StudentId($command->uuid));

        if (null === $student) {
            throw StudentNotFoundException::forId($command->uuid);
        }

        $dto = $command->dto;

        $updatedStudent = $student->update(
            name: $dto->name,
            email: $dto->email,
            phone: $dto->phone,
            dni: $dto->dni,
            birthDate: $dto->birth_date,
            address: $dto->address,
            notes: $dto->notes,
            active: $dto->active
        );

        $this->repository->save($updatedStudent);

        // Clear cache
        Cache::forget("student_{$command->uuid}");
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
```


### 7.4 ReadModel Corrección

```php
// ✅ CORRECTO - StudentReadModel.php
final class StudentReadModel extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $dni,
        public ?string $birthDate,  // camelCase
        public ?string $address,
        public ?string $avatar,
        public ?string $notes,
        public bool $active,
        public ?string $createdAt,  // camelCase
        public ?string $updatedAt,  // camelCase
        public ?string $deletedAt   // camelCase
    ) {}
}
```

**ListStudentHandler - Corrección:**
```php
public function handle(ListStudentQuery $query): array
{
    $filters = $query->filters;
    $cacheKey = "students_list_" . md5(serialize($filters->toArray()));
    $ttl = 60 * 15;

    try {
        return Cache::tags(['students_list'])->remember($cacheKey, $ttl, function () use ($filters) {
            return $this->fetchData($filters);
        });
    } catch (\Exception $e) {
        return Cache::remember($cacheKey, $ttl, function () use ($filters) {
            return $this->fetchData($filters);
        });
    }
}

private function fetchData(StudentFilterDTO $filters): array
{
    $result = $this->repository->findAllPaginated(
        filters: $filters->toArray(),
        page: $filters->page,
        perPage: $filters->perPage
    );

    $result['data'] = $result['data']
        |> (fn($students) => array_map(
            fn($student) => new StudentReadModel(
                id: $student->id->value,
                name: $student->name,
                email: $student->email,
                phone: $student->phone,
                dni: $student->dni,
                birthDate: $student->birthDate,
                address: $student->address,
                avatar: $student->avatar,
                notes: $student->notes,
                active: $student->active,
                createdAt: $student->createdAt,
                updatedAt: $student->updatedAt,
                deletedAt: $student->deletedAt
            ),
            $students
        ));

    return $result;
}
```


---

## 8. Plan de Acción Detallado

### 🔴 Fase 1: Correcciones Críticas (3-4 días)

#### Día 1: Corregir Namespaces
- [ ] Cambiar todos los namespaces de `Modules\Student` a `Modules\Students`
- [ ] Actualizar imports en todos los archivos
- [ ] Actualizar ServiceProvider
- [ ] Verificar que no haya errores de autoload

#### Día 2: Refactorizar Student Entity
- [ ] Hacer que Student extienda AggregateRoot
- [ ] Agregar método `create()` estático
- [ ] Agregar método `update()` con clone
- [ ] Agregar métodos `activate()` y `deactivate()`
- [ ] Crear evento `StudentCreated`
- [ ] Actualizar evento `StudentUpdated`

#### Día 3: Corregir Value Objects
- [ ] Agregar validación a Coordinates
- [ ] Agregar validación a SocialLinks con URI Extension
- [ ] Agregar métodos útiles con #[\NoDiscard]
- [ ] Crear tests unitarios

#### Día 4: Corregir Handlers
- [ ] Refactorizar CreateStudentHandler
- [ ] Refactorizar UpdateStudentHandler
- [ ] Refactorizar DeleteStudentHandler
- [ ] Agregar invalidación de cache
- [ ] Corregir ReadModel

### 🟡 Fase 2: Mejoras Arquitectónicas (2-3 días)

#### Día 5: Pipe Operator y Cache
- [ ] Implementar pipe operator en handlers
- [ ] Agregar cache tags
- [ ] Implementar cache individual en GetStudent
- [ ] Crear StudentDataTransformer

#### Día 6: Enums y Exceptions
- [ ] Renombrar CompanyStatus a StudentStatus
- [ ] Agregar estados apropiados (Active, Inactive, Suspended)
- [ ] Crear excepciones específicas
- [ ] Agregar validaciones de dominio

#### Día 7: Repository y Mapper
- [ ] Refactorizar EloquentStudentRepository
- [ ] Usar pipe operator en mapper
- [ ] Optimizar queries
- [ ] Agregar métodos de búsqueda


### 🟢 Fase 3: Features Avanzadas (2-3 días)

#### Día 8: Exports
- [ ] Crear StudentExcelExport
- [ ] Crear StudentPdfExport
- [ ] Usar StudentDataTransformer
- [ ] Implementar con pipe operator

#### Día 9: Domain Services
- [ ] Crear StudentDomainService si necesario
- [ ] Implementar lógica de negocio compleja
- [ ] Agregar specifications si necesario

#### Día 10: Testing
- [ ] Unit tests para Value Objects
- [ ] Unit tests para Entity
- [ ] Integration tests para Handlers
- [ ] Feature tests para CRUD completo

---

## 9. Checklist de Cumplimiento

### Arquitectura Hexagonal

- [ ] Entity extiende AggregateRoot
- [ ] Entity tiene método create() estático
- [ ] Entity tiene método update() con clone
- [ ] Entity registra eventos de dominio
- [ ] Value Objects validan en constructor
- [ ] Value Objects son readonly
- [ ] Handlers usan repositorio (no Eloquent directo)
- [ ] Mapper convierte Carbon a ISO8601
- [ ] Repository implementa Port
- [ ] Namespaces consistentes (Students plural)

### PHP 8.5

- [ ] Pipe operator en handlers
- [ ] Pipe operator en transformers
- [ ] Clone with en entity
- [ ] #[\NoDiscard] en value objects
- [ ] #[\NoDiscard] en transformers
- [ ] URI Extension en SocialLinks
- [ ] Validación en constructores (no property hooks)

### Cache

- [ ] Cache tags en list queries
- [ ] Cache individual en get query
- [ ] Invalidación en create
- [ ] Invalidación en update
- [ ] Invalidación en delete
- [ ] Fallback sin tags


### Convenciones

- [ ] Propiedades en camelCase (domain/application)
- [ ] Propiedades en snake_case (eloquent)
- [ ] Fechas como string ISO8601 en domain
- [ ] Fechas como Carbon en eloquent
- [ ] ReadModel usa camelCase
- [ ] DTOs usan camelCase

### Domain Logic

- [ ] Entity no es anémica
- [ ] Métodos de negocio en entity
- [ ] Validaciones en value objects
- [ ] Eventos de dominio registrados
- [ ] Invariantes protegidas

---

## 10. Resumen de Problemas por Severidad

### 🔴 CRÍTICOS (Bloquean funcionalidad)

1. Namespaces inconsistentes (Student vs Students)
2. CreateStudentHandler usa propiedades inexistentes
3. UpdateStudentHandler usa propiedades inexistentes
4. ListStudentHandler crea ReadModel con propiedades incorrectas
5. Student entity no tiene métodos create() ni update()
6. Student entity no extiende AggregateRoot

### 🟡 IMPORTANTES (Afectan calidad)

7. Value Objects sin validación
8. No usa pipe operator
9. No usa clone with
10. No usa URI Extension
11. Cache sin tags
12. Sin invalidación de cache
13. ReadModel usa snake_case

### 🟢 MEJORAS (Optimizaciones)

14. Falta #[\NoDiscard]
15. Falta StudentDataTransformer
16. Falta exports
17. Falta domain services
18. Falta specifications
19. Falta tests comprehensivos

---

## 11. Conclusión Final

### Estado Actual: 4/10 ❌

El módulo Students tiene **problemas críticos** que impiden su funcionamiento:

**Problemas Bloqueantes:**
- Handlers usan propiedades que no existen en entity
- Namespaces inconsistentes
- Entity anémica sin lógica de negocio
- ReadModel incompatible con handlers

**Problemas de Calidad:**
- No usa características de PHP 8.5
- Value Objects sin validación
- Cache básico sin optimizaciones
- Sin inmutabilidad correcta

### Estado Objetivo: 10/10 ✅

**Tiempo Estimado:** 8-10 días de desarrollo

**Prioridad:** ALTA - Requiere refactorización inmediata

**Recomendación:** Usar módulo Clients como referencia y aplicar las mismas correcciones.

---

**Elaborado por:** Kiro AI Assistant  
**Fecha:** 2 de marzo de 2026  
**Basado en:** PHP 8.5 Features y Architecture Guidelines  
**Próxima acción:** Iniciar Fase 1 - Correcciones Críticas

