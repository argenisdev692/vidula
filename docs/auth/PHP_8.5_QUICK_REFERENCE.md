# PHP 8.5 Quick Reference - Auth Module

**Fecha:** 2 de marzo de 2026  
**Propósito:** Guía rápida de características PHP 8.5 implementadas en Auth

---

## 1. Property Hooks

### Qué son
Permiten definir comportamiento get/set directamente en la declaración de propiedades.

### Cuándo usar
- Value Objects con validación
- Normalización automática de datos
- Propiedades calculadas

### Ejemplo en Auth
```php
// UserEmail.php
final readonly class UserEmail
{
    public function __construct(
        public string $value {
            get => strtolower($this->value);
            set {
                $normalized = strtolower(trim($value));
                if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid email: {$value}");
                }
                $this->value = $normalized;
            }
        }
    ) {}
}

// Uso
$email = new UserEmail('TEST@EXAMPLE.COM');
echo $email->value; // "test@example.com" (normalizado automáticamente)
```

### Beneficios
- ✅ Validación automática
- ✅ Normalización inline
- ✅ Menos código boilerplate
- ✅ Más declarativo

---

## 2. Pipe Operator (`|>`)

### Qué es
Permite encadenar funciones de izquierda a derecha, pasando el resultado de una a la siguiente.

### Cuándo usar
- Transformaciones de datos
- Mappers
- Pipelines de procesamiento
- Sanitización de inputs

### Ejemplo en Auth
```php
// UserMapper.php
#[\NoDiscard]
public static function toDomain(UserEloquentModel $eloquent): User
{
    return $eloquent
        |> self::extractBaseData(...)
        |> self::addTimestamps(...)
        |> self::buildEntity(...);
}

private static function extractBaseData(UserEloquentModel $model): array
{
    return [
        'id' => $model->id,
        'uuid' => $model->uuid,
        'name' => $model->name,
        // ...
        'model' => $model,
    ];
}

private static function addTimestamps(array $data): array
{
    $model = $data['model'];
    unset($data['model']);
    
    return [
        ...$data,
        'createdAt' => $model->created_at?->toIso8601String() ?? '',
        'updatedAt' => $model->updated_at?->toIso8601String() ?? '',
        'deletedAt' => $model->deleted_at?->toIso8601String(),
    ];
}

private static function buildEntity(array $data): User
{
    return new User(...$data);
}
```

### Beneficios
- ✅ Flujo de datos claro (izquierda a derecha)
- ✅ Cada paso es aislado y testeable
- ✅ Fácil agregar/remover pasos
- ✅ Más legible que funciones anidadas

---

## 3. Clone With

### Qué es
Permite clonar objetos y modificar propiedades en una sola operación.

### Cuándo usar
- Entities inmutables
- Patrón "wither"
- Actualizaciones de estado
- Domain entities con eventos

### Ejemplo en Auth
```php
// User.php
public function updateProfile(
    string $name,
    ?string $lastName = null,
    ?string $phone = null,
    ?string $username = null,
): self {
    $updated = clone $this with [
        'name' => $name,
        'lastName' => $lastName,
        'phone' => $phone,
        'username' => $username,
        'updatedAt' => date('c'),
    ];

    $updated->recordDomainEvent(new UserUpdated(
        userId: $this->id,
        uuid: $this->uuid,
        changes: ['name', 'lastName', 'phone', 'username'],
        occurredAt: date('c'),
    ));

    return $updated;
}

// Uso
$user = new User(/* ... */);
$updatedUser = $user->updateProfile('John', 'Doe', '+1234567890');
// $user sigue sin cambios (inmutable)
// $updatedUser tiene los nuevos valores
```

### Beneficios
- ✅ Inmutabilidad simplificada
- ✅ Menos código boilerplate
- ✅ Patrón "wither" más limpio
- ✅ Perfecto para DDD

---

## 4. #[\NoDiscard] Attribute

### Qué es
Emite una advertencia si el valor de retorno de una función es ignorado.

### Cuándo usar
- Funciones de transformación
- Mappers
- Métodos que retornan valores importantes
- Funciones de sanitización

### Ejemplo en Auth
```php
// UserMapper.php
#[\NoDiscard]
public static function toDomain(UserEloquentModel $eloquent): User
{
    return $eloquent
        |> self::extractBaseData(...)
        |> self::addTimestamps(...)
        |> self::buildEntity(...);
}

// AuthProvider.php
#[\NoDiscard]
public function label(): string
{
    return match ($this) {
        self::Email => 'Email & Password',
        self::Google => 'Google',
        // ...
    };
}

// Uso INCORRECTO (genera warning)
UserMapper::toDomain($eloquent); // ⚠️ Warning: Result not used

// Uso CORRECTO
$user = UserMapper::toDomain($eloquent); // ✅ OK
```

### Beneficios
- ✅ Previene errores comunes
- ✅ Advertencias en tiempo de compilación
- ✅ Código más seguro
- ✅ Costo cero en runtime

---

## 5. Enums con Métodos

### Qué son
Enums backed que pueden tener métodos helper.

### Cuándo usar
- Estados con lógica asociada
- Tipos con comportamiento
- Valores con metadata
- UI helpers

### Ejemplo en Auth
```php
// AuthProvider.php
enum AuthProvider: string
{
    case Email = 'email';
    case Google = 'google';
    case Github = 'github';
    case Otp = 'otp';

    #[\NoDiscard]
    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email & Password',
            self::Google => 'Google',
            self::Github => 'GitHub',
            self::Otp => 'One-Time Password',
        };
    }

    #[\NoDiscard]
    public function icon(): string
    {
        return match ($this) {
            self::Email => 'envelope',
            self::Google => 'google',
            self::Github => 'github',
            self::Otp => 'key',
        };
    }

    public function requiresPassword(): bool
    {
        return $this === self::Email;
    }

    public function isOAuth(): bool
    {
        return in_array($this, [self::Google, self::Github]);
    }
}

// Uso
$provider = AuthProvider::Google;
echo $provider->label(); // "Google"
echo $provider->icon(); // "google"
var_dump($provider->isOAuth()); // true
var_dump($provider->requiresPassword()); // false
```

### Beneficios
- ✅ Lógica encapsulada en el enum
- ✅ Más expresivo
- ✅ Fácil de usar en UI
- ✅ Type-safe

---

## Patrones Comunes

### Pattern 1: Value Object con Property Hooks
```php
final readonly class Email
{
    public function __construct(
        public string $value {
            get => strtolower($this->value);
            set {
                $normalized = strtolower(trim($value));
                if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid email");
                }
                $this->value = $normalized;
            }
        }
    ) {}
}
```

### Pattern 2: Mapper con Pipe Operator
```php
#[\NoDiscard]
public static function toDomain(EloquentModel $model): Entity
{
    return $model
        |> self::extractData(...)
        |> self::transform(...)
        |> self::build(...);
}
```

### Pattern 3: Entity con Clone With
```php
public function update(array $data): self
{
    $updated = clone $this with [
        ...$data,
        'updatedAt' => date('c'),
    ];
    
    $updated->recordDomainEvent(new EntityUpdated(/* ... */));
    
    return $updated;
}
```

### Pattern 4: Enum con Métodos Helper
```php
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    
    #[\NoDiscard]
    public function label(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
    
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
```

---

## Checklist de Implementación

### Para Value Objects
- [ ] Usar `final readonly class`
- [ ] Implementar property hooks para validación
- [ ] Agregar métodos helper si es necesario
- [ ] Implementar `__toString()` si aplica

### Para Mappers
- [ ] Agregar `#[\NoDiscard]` al método principal
- [ ] Usar pipe operator para transformaciones
- [ ] Separar en métodos privados cada paso
- [ ] Convertir fechas a ISO8601

### Para Entities
- [ ] Extender `AggregateRoot`
- [ ] Usar clone with para actualizaciones
- [ ] Emitir eventos de dominio
- [ ] Métodos de negocio encapsulados

### Para Enums
- [ ] Usar backed enums (`string` o `int`)
- [ ] Agregar método `label()` con `#[\NoDiscard]`
- [ ] Agregar métodos de validación (is*, can*)
- [ ] Agregar metadata si es necesario (icon, color, etc.)

---

## Errores Comunes

### ❌ Error 1: Olvidar #[\NoDiscard]
```php
// MAL
public static function toDomain($model): Entity { /* ... */ }

// BIEN
#[\NoDiscard]
public static function toDomain($model): Entity { /* ... */ }
```

### ❌ Error 2: No usar clone with
```php
// MAL
public function update($data): self
{
    $this->name = $data['name']; // Mutación directa
    return $this;
}

// BIEN
public function update($data): self
{
    return clone $this with ['name' => $data['name']];
}
```

### ❌ Error 3: Property hooks sin validación
```php
// MAL
public function __construct(
    public string $email {
        set => $this->email = $value; // Sin validación
    }
) {}

// BIEN
public function __construct(
    public string $email {
        set {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email");
            }
            $this->email = $value;
        }
    }
) {}
```

### ❌ Error 4: Pipe operator sin pasos separados
```php
// MAL
return $model |> fn($m) => new Entity(
    id: $m->id,
    name: $m->name,
    // ... 20 líneas más
);

// BIEN
return $model
    |> self::extractData(...)
    |> self::transform(...)
    |> self::build(...);
```

---

## Recursos

### Documentación
- [PHP 8.5 Release Notes](https://www.php.net/releases/8.5/en.php)
- [Property Hooks RFC](https://wiki.php.net/rfc/property-hooks)
- [Pipe Operator RFC](https://wiki.php.net/rfc/pipe-operator-v3)
- [Clone With RFC](https://wiki.php.net/rfc/clone_with_v2)

### Ejemplos en el Proyecto
- `src/Modules/Auth/Domain/ValueObjects/` - Property hooks
- `src/Modules/Auth/Infrastructure/Persistence/Mappers/` - Pipe operator
- `src/Modules/Auth/Domain/Entities/User.php` - Clone with
- `src/Modules/Auth/Domain/Enums/` - Enums con métodos

---

**Elaborado por:** Kiro AI Assistant  
**Fecha:** 2 de marzo de 2026  
**Versión:** 1.0
