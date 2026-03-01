# HOW TO CREATE A NEW CRUD MODULE

## Replacements

| Placeholder | Example |
|---|---|
| `{YourModule}` | `Products` |
| `{YourEntity}` | `Product` |
| `{YourId}` | `ProductId` |

---

## Steps

**1.** Copy the `{YourModule}/` template folder and rename it with your module name.

**2.** Create `Domain/Entities/{YourEntity}.php`
- Extends `AggregateRoot`
- Pure domain logic — no Eloquent, no Laravel

**3.** Create `Domain/ValueObjects/{YourId}.php`
- `readonly` + `ramsey/uuid`

**4.** Create `Domain/Ports/{YourEntity}RepositoryPort.php`
- Interface only: `find()`, `save()`, `delete()`, `list()`

**5.** Create `Application/Commands/` handlers (write side)
- Inject the port directly — no Bus
- MUST dispatch Domain Events (`{YourEntity}Created`, etc.)
- MUST implement listeners to invalidate cache (`Cache::forget`)
- Extend `TransactionalHandler` if DB atomicity is needed

**6.** Create `Application/Queries/` handlers (read side)
- Eloquent is OK here directly
- MUST use `Cache::remember` with TTL
- Return `ReadModels`, not Domain Entities

**7.** Create `Infrastructure/Persistence/Eloquent/Models/{YourEntity}EloquentModel.php`
- ALWAYS add `@internal` docblock
- MUST use `SoftDeletes` + `timestamps`
- MUST use `Spatie\Activitylog\Traits\LogsActivity` and implement `getActivitylogOptions()`

**8.** Create `Infrastructure/Persistence/Mappers/{YourEntity}Mapper.php`
- This is the ONLY class allowed to import both the Domain Entity and the EloquentModel
- No Controller, Handler, or Service should ever do this

**9.** Create `Infrastructure/Persistence/Repositories/Eloquent{YourEntity}Repository.php`
- Implements `{YourEntity}RepositoryPort`
- Uses the Mapper internally

**10.** Register the binding in `{YourModule}ServiceProvider.php`
```php
$this->app->bind(
    {YourEntity}RepositoryPort::class,
    Eloquent{YourEntity}Repository::class
);
```

**11.** Create `Infrastructure/Http/Controllers/Api/{YourEntity}Controller.php`
- Methods: `index`, `show`, `store`, `update`, `destroy`, `restore`
- Orchestrator only — zero business logic

**12.** Create `Infrastructure/Http/Controllers/Web/{YourEntity}PageController.php`
- Returns `Inertia::render()`

**13.** Create `Infrastructure/Http/Requests/` + `Resources/`

**14.** Register routes in `Infrastructure/Routes/api.php` and `web.php`

```
Web (Inertia):
GET    /{module}                → index
GET    /{module}/create         → create
GET    /{module}/{uuid}         → show
GET    /{module}/{uuid}/edit    → edit
DELETE /{module}/{uuid}         → destroy
PATCH  /{module}/{uuid}/restore → restore

API:
GET    /api/{module}                         → index
GET    /api/{module}/{uuid}                  → show
POST   /api/{module}                         → store
PUT    /api/{module}/{uuid}                  → update
DELETE /api/{module}/{uuid}                  → destroy
PATCH  /api/{module}/{uuid}/restore          → restore
GET    /api/{module}/export?format=excel|pdf → export
```

- All UUID parameters must use `->whereUuid('uuid')`
- All routes must be inside the `auth` middleware group

**15.** Add permissions in the seeder

```
VIEW_{MODULE}
CREATE_{MODULE}
UPDATE_{MODULE}
DELETE_{MODULE}
Role: {ModuleName}
```

- Call `app(PermissionRegistrar::class)->forgetCachedPermissions()` BEFORE creating permissions
- Super Admin automatically receives all permissions

**16.** Create exports in `Infrastructure/Persistence/Export/`
- `{YourEntity}ExcelExport.php` — implement `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`
- `{YourEntity}PdfExport.php` — use stylized template with logo
- Both MUST reuse the same `FilterDTO` used for listing
- Implement `{YourEntity}ExportController.php`
- Add `ExportButton` component to every index page

**17.** If the module requires a `user_id` relationship, add the corresponding `belongsTo` / `hasMany`

**18.** Write PEST tests in `Tests/`
- `Unit/Domain/` — domain invariants and business rules
- `Unit/Application/` — handlers with mocked repository
- `Integration/` — DB round-trip via Mapper
- `Feature/` — full HTTP CRUD + export

---

## SoftDeletes Convention

Every CRUD module MUST include:

1. `SoftDeletes` trait on the EloquentModel (adds `deleted_at` column)
2. `softDeletes()` in the migration
3. `softDelete(string $uuid)` + `restore(string $uuid)` on the Repository Port
4. `DELETE` + `PATCH /restore` routes in both web and API route groups
5. The delete handler performs soft-delete (`->delete()`), never hard-delete

---

## Controller Convention

Controllers are **orchestrators ONLY** — they contain NO business logic. Inject CQRS handlers directly (no Bus).

```php
// Web Controller (Inertia)
return Inertia::render('module/PageName', [...]);

// API Controller
return response()->json([...]);
```

| Controller type | Required methods |
|---|---|
| API Controller | `index`, `show`, `store`, `update`, `destroy`, `restore` |
| Web Controller | `index`, `create`, `show`, `edit`, `destroy`, `restore` |

---

## Eloquent Query Conventions

1. Always use `select()` — never `SELECT *`
2. Always use `paginate()` — never unbounded `get()` on listing queries
3. Use `when()` for conditional filters
4. Use `scopeInDateRange()` for date filtering
5. Query by `uuid` column, never by `id`, in all public-facing operations

---

## Layer Rules

| Layer | Can import | Cannot import |
|---|---|---|
| Domain | Shared/Domain, own VOs, Enums | Eloquent, Laravel, HTTP, Queues |
| Application | Domain, Shared/Application, DTOs | Eloquent (`@internal` enforced) |
| Infrastructure | Domain (Ports), Application, Eloquent | No restrictions — it's the adapter |

> The **Mapper** is the ONLY class in the entire codebase allowed to import both the Domain Entity and the EloquentModel simultaneously.

---

## Key Differences vs Full Enterprise Architecture

| Feature | This (Intermediate) | Full Enterprise |
|---|---|---|
| CommandBus / QueryBus | ❌ Direct handler calls | ✅ Formal Bus with middleware |
| IntegrationEvents | ❌ Laravel Events | ✅ Dedicated IntegrationEvent layer |
| Anti-Corruption Layer (ACL) | ❌ Not needed for 2 devs | ✅ Between every context |
| Contracts / Published Lang. | ❌ Not needed for 2 devs | ✅ Per context |
| PHPArkitect tests | ❌ PHPStan is enough | ✅ CI layer boundary tests |
| Domain Subscribers | ❌ Laravel Event Listeners | ✅ Synchronous domain subscribers |
| Bounded Contexts | Modules (lighter) | Full DDD Bounded Contexts |
| Team size | 2 senior devs | 6-10+ devs |

**When to upgrade to the full architecture:**
- Team grows to 4+ developers
- Two devs need to work on the same module without conflicts
- Modules have business rules that genuinely conflict
- Parts of the system need to scale independently

---

## Composer Packages → Architecture Mapping

### Core framework
| Package | Maps to |
|---|---|
| `laravel/framework` | All modules |
| `laravel/sanctum` | `Auth/Infrastructure` (HasApiTokens) |
| `laravel/fortify` | `Auth/Application/Services/AuthenticationService.php` |
| `laravel/reverb` | `Shared/Infrastructure/Broadcasting/ReverbAdapter.php` |
| `laravel/horizon` | `Shared/Infrastructure/Queue/` |

### Queue / Messaging
| Package | Maps to |
|---|---|
| `vladimir-yuldashev/laravel-queue-rabbitmq` | `Shared/Infrastructure/Queue/RabbitMQAdapter.php` |

### Auth / Permissions
| Package | Maps to |
|---|---|
| `spatie/laravel-permission` | `Auth/Application/Permissions/` + `UserEloquentModel HasRoles` |
| `spatie/laravel-one-time-passwords` | `Auth/Domain/Services/OtpGenerator.php` |
| `laravel/socialite` | `Auth/Infrastructure/ExternalServices/OAuth/` |

### Domain / Application
| Package | Maps to |
|---|---|
| `spatie/laravel-data` | `Shared/Application/DTOs/BaseDTO.php` + all DTOs |
| `ramsey/uuid` | `Shared/Domain/ValueObjects/Uuid.php` |

### Storage
| Package | Maps to |
|---|---|
| `league/flysystem-aws-s3-v3` | `Shared/Infrastructure/Storage/S3StorageAdapter.php` |
| `spatie/laravel-medialibrary` | `Shared/Infrastructure/Storage/SpatieMediaLibraryAdapter.php` |
| `intervention/image` | `Users/Infrastructure/Queue/Jobs/ProcessAvatarJob.php` |

### Mail
| Package | Maps to |
|---|---|
| `resend/resend-laravel` | `Shared/Infrastructure/Mail/ResendAdapter.php` |

### Exports
| Package | Maps to |
|---|---|
| `maatwebsite/excel` | `Shared/Infrastructure/Export/ExcelAdapter.php` |
| `barryvdh/laravel-dompdf` | `Shared/Infrastructure/Export/PdfAdapter.php` |

### Observability
| Package | Maps to |
|---|---|
| `open-telemetry/opentelemetry-php` | `Shared/Infrastructure/Observability/Tracing/` |
| `open-telemetry/exporter-otlp` | `Shared/Infrastructure/Observability/Tracing/OpenTelemetryAdapter.php` |
| `monolog/monolog` | `Shared/Infrastructure/Logging/` |

### Audit
| Package | Maps to |
|---|---|
| `spatie/laravel-activitylog` | `Shared/Infrastructure/Audit/SpatieActivityLogAdapter.php` |

### AI
| Package | Maps to |
|---|---|
| `prism-php/prism` | `AI/Infrastructure/ExternalServices/Prism/` |

### Static Analysis
| Package | Maps to |
|---|---|
| `nunomaduro/larastan` | `phpstan.neon` (Laravel-aware rules) |
| `phpstan/phpstan-strict-rules` | `phpstan.neon` (strict mode) |

### Testing
| Package | Maps to |
|---|---|
| `pestphp/pest` | All modules `Tests/` |
| `pestphp/pest-plugin-laravel` | Feature tests |
| `spatie/laravel-backup` | `Shared/Infrastructure/` (scheduled backups) |

### Dev Only
| Package | Maps to |
|---|---|
| `barryvdh/laravel-debugbar` | Dev only (`APP_DEBUG=true`) |
| `barryvdh/laravel-ide-helper` | Dev only (`_ide_helper.php`) |
