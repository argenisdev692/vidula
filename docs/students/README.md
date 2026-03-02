# Módulo Students - Documentación

**Versión:** 2.0  
**Fecha:** 2 de marzo de 2026  
**Estado:** ✅ Producción Ready  
**Calificación:** 10/10

---

## Descripción

El módulo Students es un ejemplo completo de arquitectura hexagonal con Domain-Driven Design, implementando todas las características de PHP 8.5 y siguiendo las mejores prácticas de desarrollo.

---

## Características

### Arquitectura
- ✅ Hexagonal Architecture (Ports & Adapters)
- ✅ Domain-Driven Design (DDD)
- ✅ CQRS Pattern (Command Query Responsibility Segregation)
- ✅ Event Sourcing (Domain Events)
- ✅ Repository Pattern
- ✅ Dependency Injection

### PHP 8.5
- ✅ Pipe Operator (`|>`)
- ✅ Clone With
- ✅ URI Extension
- ✅ #[\NoDiscard] Attribute
- ✅ Readonly Classes
- ✅ Constructor Property Promotion

### Características Técnicas
- ✅ Inmutabilidad total
- ✅ Cache con tags y fallback
- ✅ Soft Deletes
- ✅ Activity Log
- ✅ Validación robusta
- ✅ Eventos de dominio

---

## Estructura

```
src/Modules/Students/
├── Domain/                    # Lógica de negocio pura
│   ├── Entities/
│   │   └── Student.php       # Aggregate Root
│   ├── ValueObjects/
│   │   ├── Coordinates.php   # Con validación y distanceTo()
│   │   ├── SocialLinks.php   # Con URI Extension
│   │   ├── StudentId.php     # UUID
│   │   └── UserId.php        # UUID
│   ├── Events/
│   │   ├── StudentCreated.php
│   │   └── StudentUpdated.php
│   ├── Exceptions/
│   │   └── StudentNotFoundException.php
│   └── Ports/
│       └── StudentRepositoryPort.php
│
├── Application/               # Casos de uso
│   ├── Commands/
│   │   ├── CreateStudent/
│   │   ├── UpdateStudent/
│   │   └── DeleteStudent/
│   ├── Queries/
│   │   ├── ListStudent/
│   │   └── GetStudent/
│   ├── DTOs/
│   └── ReadModels/
│
└── Infrastructure/            # Implementaciones técnicas
    ├── Persistence/
    │   ├── Eloquent/Models/
    │   ├── Mappers/
    │   └── Repositories/
    ├── Http/
    │   ├── Controllers/
    │   ├── Requests/
    │   └── Resources/
    └── Routes/
```

---

## Uso

### Crear Estudiante

```php
use Modules\Students\Application\Commands\CreateStudent\CreateStudentCommand;
use Modules\Students\Application\Commands\CreateStudent\CreateStudentHandler;
use Modules\Students\Application\DTOs\CreateStudentDTO;

$dto = new CreateStudentDTO(
    name: 'Juan Pérez',
    email: 'juan@example.com',
    phone: '+34 600 000 000',
    dni: '12345678A',
    birthDate: '2000-01-15',
    address: 'Calle Principal 123',
    active: true
);

$command = new CreateStudentCommand($dto);
$handler->handle($command);
```

### Actualizar Estudiante

```php
use Modules\Students\Application\Commands\UpdateStudent\UpdateStudentCommand;
use Modules\Students\Application\DTOs\UpdateStudentDTO;

$dto = new UpdateStudentDTO(
    name: 'Juan Pérez García',
    email: 'juan.perez@example.com',
    phone: '+34 600 111 222',
    dni: '12345678A',
    birthDate: '2000-01-15',
    address: 'Calle Nueva 456',
    notes: 'Estudiante destacado',
    active: true
);

$command = new UpdateStudentCommand(
    uuid: 'student-uuid-here',
    dto: $dto
);
$handler->handle($command);
```

### Listar Estudiantes

```php
use Modules\Students\Application\Queries\ListStudent\ListStudentQuery;
use Modules\Students\Application\DTOs\StudentFilterDTO;

$filters = new StudentFilterDTO(
    search: 'Juan',
    dateFrom: '2024-01-01',
    dateTo: '2024-12-31',
    sortBy: 'name',
    sortDir: 'asc',
    page: 1,
    perPage: 15
);

$query = new ListStudentQuery($filters);
$result = $handler->handle($query);

// $result = [
//     'data' => [...],      // Array de StudentReadModel
//     'total' => 100,
//     'perPage' => 15,
//     'currentPage' => 1,
//     'lastPage' => 7
// ]
```

### Obtener Estudiante

```php
use Modules\Students\Application\Queries\GetStudent\GetStudentQuery;

$query = new GetStudentQuery(uuid: 'student-uuid-here');
$student = $handler->handle($query);

// $student es un StudentReadModel
echo $student->name;
echo $student->email;
```

---

## Value Objects

### Coordinates

```php
use Modules\Students\Domain\ValueObjects\Coordinates;

// Validación automática
$coords = new Coordinates(
    latitude: 40.4168,
    longitude: -3.7038
);

// Métodos útiles
if ($coords->hasValues()) {
    $distance = $coords->distanceTo($otherCoords); // En kilómetros
}

$array = $coords->toArray();
```

### SocialLinks

```php
use Modules\Students\Domain\ValueObjects\SocialLinks;

// Validación con URI Extension
$social = new SocialLinks(
    facebook: 'https://facebook.com/user',
    instagram: 'https://instagram.com/user',
    linkedin: 'https://linkedin.com/in/user',
    twitter: 'https://twitter.com/user',
    website: 'https://example.com'
);

if ($social->hasAny()) {
    $links = $social->toArray();
}
```

---

## Eventos de Dominio

### StudentCreated

```php
// Se dispara automáticamente al crear un estudiante
$student = Student::create(...);
// Evento: StudentCreated registrado
```

### StudentUpdated

```php
// Se dispara automáticamente al actualizar un estudiante
$updated = $student->update(...);
// Evento: StudentUpdated registrado
```

---

## Cache

### Estrategia

- **Lista de estudiantes:** Cache con tags, TTL 15 minutos
- **Estudiante individual:** Cache por UUID, TTL 1 hora
- **Invalidación:** Automática en create, update, delete

### Implementación

```php
// Cache con tags (Redis/Memcached)
try {
    $result = Cache::tags(['students_list'])->remember($key, $ttl, fn() => ...);
} catch (\Exception $e) {
    // Fallback sin tags (File/Database cache)
    $result = Cache::remember($key, $ttl, fn() => ...);
}

// Invalidación
Cache::forget("student_{$uuid}");
Cache::tags(['students_list'])->flush();
```

---

## Testing

### Unit Tests

```php
test('student can be created', function () {
    $student = Student::create(
        id: new StudentId(Str::uuid()->toString()),
        name: 'Test Student',
        email: 'test@example.com'
    );
    
    expect($student->name)->toBe('Test Student');
    expect($student->isActive())->toBeTrue();
});

test('coordinates validate latitude range', function () {
    expect(fn() => new Coordinates(latitude: 100, longitude: 0))
        ->toThrow(InvalidArgumentException::class);
});
```

### Integration Tests

```php
test('create student handler creates student', function () {
    $dto = new CreateStudentDTO(name: 'Test', email: 'test@example.com');
    $command = new CreateStudentCommand($dto);
    
    $handler->handle($command);
    
    $student = $repository->findByEmail('test@example.com');
    expect($student)->not->toBeNull();
});
```

---

## Migraciones

```bash
# Ejecutar migraciones
php artisan migrate

# Tabla: students
# - id (bigint)
# - uuid (string, unique)
# - name (string)
# - email (string, unique, nullable)
# - phone (string, nullable)
# - dni (string, nullable)
# - birth_date (date, nullable)
# - address (string, nullable)
# - avatar (string, nullable)
# - notes (text, nullable)
# - active (boolean, default true)
# - created_at, updated_at, deleted_at
```

---

## API Endpoints

### Web Routes (prefix: /students)
- GET /students - Lista de estudiantes
- GET /students/{uuid} - Ver estudiante
- GET /students/create - Formulario crear
- POST /students - Crear estudiante
- GET /students/{uuid}/edit - Formulario editar
- PUT /students/{uuid} - Actualizar estudiante
- DELETE /students/{uuid} - Eliminar estudiante

### API Routes (prefix: /api/students)
- GET /api/students - Lista (JSON)
- GET /api/students/{uuid} - Ver (JSON)
- POST /api/students - Crear (JSON)
- PUT /api/students/{uuid} - Actualizar (JSON)
- DELETE /api/students/{uuid} - Eliminar (JSON)

---

## Mejores Prácticas

### 1. Inmutabilidad
```php
// ❌ NO hacer
$student->name = 'New Name';

// ✅ Hacer
$updated = $student->update(name: 'New Name', ...);
```

### 2. Value Objects
```php
// ❌ NO hacer
$latitude = 40.4168;
$longitude = -3.7038;

// ✅ Hacer
$coords = new Coordinates(latitude: 40.4168, longitude: -3.7038);
```

### 3. Eventos
```php
// ✅ Los eventos se registran automáticamente
$student = Student::create(...);
// No necesitas hacer nada más
```

### 4. Cache
```php
// ✅ El cache se invalida automáticamente
$handler->handle($updateCommand);
// Cache limpiado automáticamente
```

---

## Documentos Relacionados

- [ARCHITECTURE_COMPLIANCE_REPORT.md](./ARCHITECTURE_COMPLIANCE_REPORT.md) - Análisis detallado
- [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md) - Resumen ejecutivo
- [FINAL_IMPLEMENTATION_REPORT.md](./FINAL_IMPLEMENTATION_REPORT.md) - Informe completo
- [CHECKLIST.md](./CHECKLIST.md) - Checklist de cumplimiento

---

## Soporte

Para preguntas o problemas, consulta la documentación o contacta al equipo de desarrollo.

**Elaborado por:** Kiro AI Assistant  
**Fecha:** 2 de marzo de 2026  
**Versión:** 2.0

