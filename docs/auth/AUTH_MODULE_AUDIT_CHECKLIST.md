# Auth Module - Architecture & PHP 8.5 Compliance Audit

**Fecha:** 2 de marzo de 2026  
**Módulo:** Auth (Authentication & Authorization)  
**Estado Inicial:** 🔍 EN AUDITORÍA

---

## Resumen Ejecutivo

Este documento audita el módulo Auth contra:
- ✅ Arquitectura Hexagonal (ARCHITECTURE-INTERMEDIATE-PHP.md)
- ✅ Características de PHP 8.5
- ✅ Mejores prácticas de DDD
- ✅ Patrones CQRS

---

## 1. Estructura de Arquitectura Hexagonal

### 1.1 Domain Layer ✅ COMPLETO

#### Entities
- ✅ `User.php` - Extiende AggregateRoot correctamente
- ✅ `SocialiteProvider.php` - Entity simple
- ⚠️ User NO tiene métodos de negocio (create, update, changePassword)
- ⚠️ User NO emite eventos en métodos de negocio (solo logIn)

#### Value Objects
- ✅ `UserEmail.php` - Extiende StringValueObject, validación correcta
- ✅ `OtpCode.php` - readonly, validación, #[\NoDiscard] en generate()
- ✅ `IpAddress.php` - readonly, validación IPv4/IPv6
- ❌ NO usa Property Hooks de PHP 8.5

#### Events
- ✅ `UserLoggedIn.php` - readonly, estructura correcta
- ✅ `PasswordChanged.php` - readonly
- ✅ `OtpGenerated.php` - readonly
- ⚠️ Faltan eventos: UserCreated, UserUpdated, UserDeleted

#### Exceptions
- ✅ `InvalidCredentialsException.php`
- ✅ `InvalidOtpException.php`
- ✅ `UserNotFoundException.php`
- ✅ Todas extienden de excepciones del dominio compartido

#### Ports (Interfaces)
- ✅ `UserRepositoryPort.php`
- ✅ `SocialiteRepositoryPort.php`
- ✅ `OtpServicePort.php`

#### Enums
- ✅ `AuthProvider.php`
- ✅ `OtpStatus.php`
- ⚠️ NO tienen métodos helper (label(), description())

**Calificación Domain:** 7/10

---

### 1.2 Application Layer ✅ PARCIAL

#### Commands
- ✅ `LoginWithSocialiteCommand` + Handler
- ✅ `SendOtpCommand` + Handler
- ✅ `VerifyOtpCommand` + Handler
- ❌ Faltan: RegisterUserCommand, UpdateUserCommand, ChangePasswordCommand
- ❌ NO usa Pipe Operator en handlers

#### Queries
- ❌ Carpeta vacía (.gitkeep)
- ❌ Faltan: GetUserQuery, ListUsersQuery

#### DTOs
- ❌ Carpeta vacía (.gitkeep)
- ❌ Faltan: UserReadModel, UserListReadModel

#### Services
- ❌ Carpeta vacía (.gitkeep)

**Calificación Application:** 4/10

---

### 1.3 Infrastructure Layer ✅ PARCIAL

#### Persistence
- ✅ `UserMapper.php` - Convierte Carbon a ISO8601 correctamente
- ✅ `SocialiteProviderMapper.php` - Convierte fechas correctamente
- ✅ `EloquentUserRepository.php` - Implementa port correctamente
- ✅ `EloquentSocialiteRepository.php` - Implementa port correctamente
- ❌ Mappers NO usan Pipe Operator

#### HTTP Controllers
- ⚠️ Estructura existe pero no auditada aún

#### Routes
- ✅ `api.php` existe
- ✅ `web.php` existe

#### External Services
- ✅ Estructura para OAuth, OTP, Notifications, Security, Analytics

**Calificación Infrastructure:** 6/10

---

## 2. PHP 8.5 Features

### 2.1 Property Hooks ❌ NO IMPLEMENTADO

**Oportunidades:**
```php
// UserEmail.php - ACTUAL
readonly class UserEmail extends StringValueObject
{
    public function __construct(string $value)
    {
        parent::__construct(strtolower(trim($value)));
    }
}

// UserEmail.php - PROPUESTO CON PROPERTY HOOKS
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
```

**Archivos a modificar:**
- `UserEmail.php` - Agregar property hooks
- `IpAddress.php` - Agregar property hooks para validación
- `OtpCode.php` - Agregar property hooks para validación

**Calificación:** 0/10

---

### 2.2 Pipe Operator (`|>`) ❌ NO IMPLEMENTADO

**Oportunidades:**

```php
// UserMapper.php - ACTUAL
public static function toDomain(UserEloquentModel $eloquent): User
{
    return new User(
        id: $eloquent->id,
        uuid: $eloquent->uuid,
        // ... resto de propiedades
    );
}

// UserMapper.php - PROPUESTO CON PIPE OPERATOR
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
        'lastName' => $model->last_name,
        'email' => $model->email,
        'username' => $model->username,
        'profilePhotoPath' => $model->profile_photo_path,
        'phone' => $model->phone,
        'isEmailVerified' => $model->email_verified_at !== null,
    ];
}

private static function addTimestamps(array $data, UserEloquentModel $model): array
{
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

**Archivos a modificar:**
- `UserMapper.php` - Refactorizar con pipe operator
- `SocialiteProviderMapper.php` - Refactorizar con pipe operator
- Handlers de queries (cuando se implementen)

**Calificación:** 0/10

---

### 2.3 Clone With ❌ NO IMPLEMENTADO

**Oportunidades:**

```php
// User.php - PROPUESTO
final class User extends AggregateRoot
{
    // ... constructor

    public function updateProfile(
        string $name,
        ?string $lastName = null,
        ?string $phone = null
    ): self {
        $updated = clone $this with [
            'name' => $name,
            'lastName' => $lastName,
            'phone' => $phone,
            'updatedAt' => date('c'),
        ];

        $updated->recordDomainEvent(new UserUpdated(
            userId: $this->id,
            changes: ['name', 'lastName', 'phone'],
            occurredAt: date('c'),
        ));

        return $updated;
    }

    public function changeEmail(string $email): self
    {
        $updated = clone $this with [
            'email' => $email,
            'isEmailVerified' => false,
            'updatedAt' => date('c'),
        ];

        $updated->recordDomainEvent(new UserEmailChanged(
            userId: $this->id,
            oldEmail: $this->email,
            newEmail: $email,
            occurredAt: date('c'),
        ));

        return $updated;
    }

    public function verifyEmail(): self
    {
        return clone $this with [
            'isEmailVerified' => true,
            'updatedAt' => date('c'),
        ];
    }

    public function updateAvatar(string $path): self
    {
        return clone $this with [
            'profilePhotoPath' => $path,
            'updatedAt' => date('c'),
        ];
    }
}
```

**Archivos a modificar:**
- `User.php` - Agregar métodos de negocio con clone with
- Crear eventos faltantes: `UserUpdated`, `UserEmailChanged`

**Calificación:** 0/10

---

### 2.4 #[\NoDiscard] Attribute ✅ PARCIALMENTE IMPLEMENTADO

**Implementado:**
- ✅ `OtpCode::generate()` - Tiene #[\NoDiscard]
- ✅ `OtpCode::equals()` - Tiene #[\NoDiscard]

**Falta implementar:**
```php
// UserMapper.php
#[\NoDiscard]
public static function toDomain(UserEloquentModel $eloquent): User

// Enums
#[\NoDiscard]
public function label(): string

#[\NoDiscard]
public function description(): string
```

**Calificación:** 3/10

---

### 2.5 Enums con Métodos ⚠️ PARCIAL

**Implementado:**
- ✅ `AuthProvider.php` - Enum básico
- ✅ `OtpStatus.php` - Enum básico

**Falta implementar:**
```php
// AuthProvider.php - PROPUESTO
enum AuthProvider: string
{
    case EMAIL = 'email';
    case GOOGLE = 'google';
    case FACEBOOK = 'facebook';
    case GITHUB = 'github';
    case OTP = 'otp';

    #[\NoDiscard]
    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Email/Password',
            self::GOOGLE => 'Google',
            self::FACEBOOK => 'Facebook',
            self::GITHUB => 'GitHub',
            self::OTP => 'One-Time Password',
        };
    }

    #[\NoDiscard]
    public function icon(): string
    {
        return match($this) {
            self::EMAIL => 'envelope',
            self::GOOGLE => 'google',
            self::FACEBOOK => 'facebook',
            self::GITHUB => 'github',
            self::OTP => 'key',
        };
    }

    public function requiresPassword(): bool
    {
        return $this === self::EMAIL;
    }

    public function isOAuth(): bool
    {
        return in_array($this, [self::GOOGLE, self::FACEBOOK, self::GITHUB]);
    }
}

// OtpStatus.php - PROPUESTO
enum OtpStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case EXPIRED = 'expired';
    case INVALID = 'invalid';

    #[\NoDiscard]
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::EXPIRED => 'Expired',
            self::INVALID => 'Invalid',
        };
    }

    public function isValid(): bool
    {
        return $this === self::PENDING;
    }

    public function canResend(): bool
    {
        return in_array($this, [self::EXPIRED, self::INVALID]);
    }
}
```

**Calificación:** 4/10

---

## 3. Arquitectura Hexagonal - Checklist Detallado

### 3.1 Separación de Capas ✅ CORRECTO

- ✅ Domain NO depende de Infrastructure
- ✅ Application depende solo de Domain
- ✅ Infrastructure implementa ports del Domain
- ✅ Uso correcto de interfaces (Ports)

---

### 3.2 Naming Conventions ✅ CORRECTO

- ✅ Namespace plural: `Modules\Auth`
- ✅ Domain entities en camelCase
- ✅ Eloquent models en snake_case
- ✅ Mappers convierten correctamente entre capas

---

### 3.3 Date Handling ✅ CORRECTO

- ✅ Eloquent models tienen Carbon instances
- ✅ Mappers convierten a ISO8601 strings con `toIso8601String()`
- ✅ Domain entities almacenan strings
- ✅ NO hay conversiones adicionales en handlers

---

### 3.4 Inmutabilidad ⚠️ PARCIAL

- ✅ Value Objects son readonly
- ✅ Events son readonly
- ❌ User entity NO es inmutable (debería usar clone with)
- ❌ SocialiteProvider NO es inmutable

---

### 3.5 Domain Events ⚠️ PARCIAL

- ✅ User extiende AggregateRoot
- ✅ Método `logIn()` emite UserLoggedIn
- ❌ Faltan eventos: UserCreated, UserUpdated, UserDeleted, UserEmailChanged
- ❌ NO hay métodos de negocio que emitan eventos

---

## 4. Funcionalidad Faltante

### 4.1 Commands Faltantes ❌

```php
// RegisterUserCommand + Handler
// UpdateUserCommand + Handler
// ChangePasswordCommand + Handler
// DeleteUserCommand + Handler
// VerifyEmailCommand + Handler
// ResetPasswordCommand + Handler
```

### 4.2 Queries Faltantes ❌

```php
// GetUserQuery + Handler + UserReadModel
// ListUsersQuery + Handler + UserListReadModel
// GetUserByEmailQuery + Handler
// SearchUsersQuery + Handler
```

### 4.3 Value Objects Faltantes ⚠️

```php
// Password.php - Para validación de contraseñas
// Username.php - Para validación de usernames
// PhoneNumber.php - Para validación de teléfonos (existe en Shared pero no se usa)
```

### 4.4 Domain Services Faltantes ⚠️

```php
// PasswordHashingService
// UsernameSuggestionService
// EmailVerificationService
```

---

## 5. Cache Management ❌ NO IMPLEMENTADO

**Falta implementar:**
```php
// En ListUsersHandler (cuando se cree)
try {
    return Cache::tags(['users_list'])->remember($cacheKey, $ttl, function () {
        return $this->fetchData();
    });
} catch (\Exception $e) {
    return Cache::remember($cacheKey, $ttl, function () {
        return $this->fetchData();
    });
}

// En mutation handlers
Cache::forget("user_{$uuid}");
try {
    Cache::tags(['users_list'])->flush();
} catch (\Exception $e) {
    // Tags not supported
}
```

**Calificación:** 0/10

---

## 6. Testing ⚠️ ESTRUCTURA EXISTE

- ✅ Carpetas Unit/Feature/Integration existen
- ⚠️ Contenido no auditado en este documento

---

## 7. Calificación Final por Categoría

| Categoría | Calificación | Estado |
|-----------|--------------|--------|
| **1. Estructura Hexagonal** | 6/10 | ⚠️ Parcial |
| **2. PHP 8.5 Features** | 1.4/10 | ❌ Crítico |
| **3. Domain Layer** | 7/10 | ⚠️ Mejorable |
| **4. Application Layer** | 4/10 | ❌ Incompleto |
| **5. Infrastructure Layer** | 6/10 | ⚠️ Parcial |
| **6. Inmutabilidad** | 4/10 | ❌ Incompleto |
| **7. Domain Events** | 5/10 | ⚠️ Parcial |
| **8. Cache Management** | 0/10 | ❌ No implementado |
| **9. Naming Conventions** | 10/10 | ✅ Perfecto |
| **10. Date Handling** | 10/10 | ✅ Perfecto |

---

## 8. Calificación Global

**CALIFICACIÓN ACTUAL: 5.3/10** ⚠️

### Desglose:
- ✅ **Fortalezas:**
  - Estructura hexagonal básica correcta
  - Separación de capas adecuada
  - Naming conventions perfectas
  - Date handling correcto
  - Value Objects bien implementados

- ❌ **Debilidades Críticas:**
  - NO usa Property Hooks de PHP 8.5
  - NO usa Pipe Operator
  - NO usa Clone With para inmutabilidad
  - Faltan Commands y Queries principales
  - NO hay cache management
  - Faltan eventos de dominio importantes
  - Entity User no tiene métodos de negocio

---

## 9. Plan de Acción Recomendado

### Fase 1: PHP 8.5 Features (Prioridad ALTA) 🔴
1. ✅ Implementar Property Hooks en Value Objects
2. ✅ Implementar Pipe Operator en Mappers
3. ✅ Implementar Clone With en User entity
4. ✅ Agregar #[\NoDiscard] donde corresponda
5. ✅ Mejorar Enums con métodos helper

### Fase 2: Domain Layer (Prioridad ALTA) 🔴
1. ✅ Agregar métodos de negocio a User entity
2. ✅ Crear eventos faltantes (UserCreated, UserUpdated, etc.)
3. ✅ Implementar Value Objects faltantes (Password, Username)
4. ✅ Crear Domain Services necesarios

### Fase 3: Application Layer (Prioridad MEDIA) 🟡
1. ✅ Implementar Commands faltantes
2. ✅ Implementar Queries + ReadModels
3. ✅ Agregar cache management
4. ✅ Implementar DTOs

### Fase 4: Testing (Prioridad MEDIA) 🟡
1. ✅ Tests unitarios para Value Objects
2. ✅ Tests unitarios para Entities
3. ✅ Tests de integración para Repositories
4. ✅ Tests de feature para Commands/Queries

---

## 10. Archivos a Modificar/Crear

### Modificar:
- `src/Modules/Auth/Domain/ValueObjects/UserEmail.php` - Property hooks
- `src/Modules/Auth/Domain/ValueObjects/IpAddress.php` - Property hooks
- `src/Modules/Auth/Domain/ValueObjects/OtpCode.php` - Property hooks
- `src/Modules/Auth/Domain/Entities/User.php` - Métodos de negocio + clone with
- `src/Modules/Auth/Domain/Enums/AuthProvider.php` - Métodos helper
- `src/Modules/Auth/Domain/Enums/OtpStatus.php` - Métodos helper
- `src/Modules/Auth/Infrastructure/Persistence/Mappers/UserMapper.php` - Pipe operator
- `src/Modules/Auth/Infrastructure/Persistence/Mappers/SocialiteProviderMapper.php` - Pipe operator

### Crear:
- `src/Modules/Auth/Domain/Events/UserCreated.php`
- `src/Modules/Auth/Domain/Events/UserUpdated.php`
- `src/Modules/Auth/Domain/Events/UserDeleted.php`
- `src/Modules/Auth/Domain/Events/UserEmailChanged.php`
- `src/Modules/Auth/Domain/ValueObjects/Password.php`
- `src/Modules/Auth/Domain/ValueObjects/Username.php`
- `src/Modules/Auth/Application/Commands/RegisterUser/` (Command + Handler)
- `src/Modules/Auth/Application/Commands/UpdateUser/` (Command + Handler)
- `src/Modules/Auth/Application/Commands/ChangePassword/` (Command + Handler)
- `src/Modules/Auth/Application/Queries/GetUser/` (Query + Handler + ReadModel)
- `src/Modules/Auth/Application/Queries/ListUsers/` (Query + Handler + ReadModel)
- `src/Modules/Auth/Contracts/DTOs/UserReadModel.php`
- `src/Modules/Auth/Contracts/DTOs/UserListReadModel.php`

---

**Elaborado por:** Kiro AI Assistant  
**Fecha:** 2 de marzo de 2026  
**Próxima revisión:** Después de implementar Fase 1  
**Objetivo:** 10/10 🎯
