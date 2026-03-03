```
src/
│
├── Shared/
│   ├── Domain/
│   │   ├── Exceptions/
│   │   │   ├── DomainException.php
│   │   │   ├── EntityNotFoundException.php
│   │   │   ├── ValidationException.php
│   │   │   ├── UnauthorizedException.php
│   │   │   ├── BusinessRuleViolationException.php
│   │   │   ├── ConcurrencyException.php
│   │   │   ├── InvariantViolationException.php
│   │   │   └── IntegrationException.php
│   │   ├── ValueObjects/
│   │   │   ├── Uuid.php
│   │   │   ├── Email.php
│   │   │   ├── PhoneNumber.php
│   │   │   ├── Money.php
│   │   │   ├── DateRange.php
│   │   │   ├── Timestamp.php
│   │   │   └── Url.php
│   │   ├── Entities/
│   │   │   └── AggregateRoot.php
│   │   └── Ports/
│   │       ├── CachePort.php
│   │       ├── QueuePort.php
│   │       ├── LoggerPort.php
│   │       ├── StoragePort.php
│   │       ├── NotificationPort.php
│   │       └── ExportPort.php
│   │
│   ├── Application/
│   │   ├── DTOs/
│   │   │   ├── BaseDTO.php
│   │   │   ├── PaginationDTO.php
│   │   │   └── FilterDTO.php
│   │   └── Transactions/
│   │       ├── TransactionInterface.php
│   │       └── TransactionalHandler.php
│   │
│   └── Infrastructure/
│       ├── Cache/
│       │   ├── CacheInterface.php
│       │   ├── RedisAdapter.php
│       │   └── InMemoryCacheAdapter.php
│       ├── Queue/
│       │   ├── QueueInterface.php
│       │   ├── LaravelQueueAdapter.php
│       │   ├── RabbitMQAdapter.php
│       │   └── SqsAdapter.php
│       ├── Broadcasting/
│       │   ├── BroadcastingInterface.php
│       │   ├── ReverbAdapter.php
│       │   └── PusherAdapter.php
│       ├── Storage/
│       │   ├── StorageInterface.php
│       │   ├── S3StorageAdapter.php
│       │   ├── LocalStorageAdapter.php
│       │   └── SpatieMediaLibraryAdapter.php
│       ├── AI/
│       │   ├── AIClientInterface.php
│       │   ├── OpenAIAdapter.php
│       │   ├── AnthropicAdapter.php
│       │   └── PrismLLMAdapter.php
│       ├── Mail/
│       │   ├── MailInterface.php
│       │   ├── ResendAdapter.php
│       │   ├── MailgunAdapter.php
│       │   └── ReactEmailTemplateRenderer.php
│       ├── Export/
│       │   ├── ExportInterface.php
│       │   ├── ExcelAdapter.php
│       │   ├── PdfAdapter.php
│       │   └── PdfTemplateRenderer.php
│       ├── Logging/
│       │   ├── ApplicationLogger.php
│       │   ├── Handlers/
│       │   │   ├── OpenTelemetryMonologHandler.php
│       │   │   └── StructuredJsonHandler.php
│       │   └── Processors/
│       │       ├── TraceContextProcessor.php
│       │       ├── CorrelationIdProcessor.php
│       │       └── RequestContextProcessor.php
│       ├── Observability/
│       │   ├── Tracing/
│       │   │   ├── OpenTelemetryAdapter.php
│       │   │   ├── InstrumentationProvider.php
│       │   │   └── SpanEnricher.php
│       │   ├── Metrics/
│       │   │   ├── PrometheusAdapter.php
│       │   │   └── PrometheusController.php
│       │   └── HealthCheck/
│       │       ├── HealthCheckController.php
│       │       ├── HealthCheckAggregator.php
│       │       ├── DatabaseHealthCheck.php
│       │       ├── RedisHealthCheck.php
│       │       ├── QueueHealthCheck.php
│       │       ├── ReverbHealthCheck.php
│       │       ├── StorageHealthCheck.php
│       │       └── ExternalServiceHealthCheck.php
│       ├── Resilience/
│       │   ├── CircuitBreaker/
│       │   │   ├── CircuitBreaker.php
│       │   │   ├── CircuitBreakerInterface.php
│       │   │   ├── CircuitBreakerState.php
│       │   │   └── CircuitBreakerMetricsExporter.php
│       │   ├── RateLimiter/
│       │   │   ├── RateLimiter.php
│       │   │   └── RateLimiterInterface.php
│       │   └── Retry/
│       │       ├── RetryPolicy.php
│       │       └── ExponentialBackoff.php
│       ├── Persistence/
│       │   └── Transactions/
│       │       ├── DatabaseTransaction.php
│       │       └── UnitOfWork.php
│       ├── Audit/
│       │   ├── AuditInterface.php
│       │   ├── SpatieActivityLogAdapter.php
│       │   └── AuditableInterface.php
│       └── Utils/
│           ├── EmailHelper.php
│           └── ImageHelper.php
│
├── Middleware/
│   ├── AuthenticationMiddleware.php
│   ├── AuthorizationMiddleware.php
│   ├── CorrelationIdMiddleware.php
│   ├── TraceContextMiddleware.php
│   ├── RateLimitMiddleware.php
│   └── HandleInertiaRequests.php
│
├── Providers/
│   ├── SharedServiceProvider.php
│   ├── BusServiceProvider.php
│   └── EventServiceProvider.php
│
└── Modules/
    │
    ├── Auth/
    │   ├── Providers/
    │   │   └── AuthServiceProvider.php
    │   ├── Tests/
    │   ├── Domain/
    │   ├── Application/
    │   └── Infrastructure/
    │       ├── Http/
    │       │   ├── Controllers/
    │       │   │   ├── Api/
    │       │   │   └── Web/
    │       │   ├── Requests/
    │       │   └── Resources/
    │       ├── WebSocket/
    │       ├── Persistence/
    │       ├── Queue/
    │       ├── ExternalServices/
    │       └── Routes/
    │
    ├── Users/
    │   ├── Providers/
    │   │   └── UsersServiceProvider.php
    │   ├── Tests/
    │   ├── Domain/
    │   ├── Application/
    │   └── Infrastructure/
    │       ├── Http/
    │       ├── WebSocket/
    │       ├── Persistence/
    │       ├── Queue/
    │       ├── Storage/
    │       ├── Utils/
    │       └── Routes/
    │
    ├── Notifications/
    │   ├── Providers/
    │   ├── Tests/
    │   ├── Domain/
    │   ├── Application/
    │   └── Infrastructure/
    │       ├── Http/
    │       ├── WebSocket/
    │       ├── Persistence/
    │       ├── Queue/
    │       ├── ExternalServices/
    │       ├── Notifications/
    │       └── Routes/
    │
    ├── Blog/
    │   ├── Providers/
    │   │   └── BlogServiceProvider.php
    │   ├── Domain/
    │   ├── Application/
    │   └── Infrastructure/
    │       ├── Http/
    │       ├── Persistence/
    │       └── Routes/
    │
    └── {YourModule}/
        ├── Providers/
        ├── Tests/
        ├── Domain/
        ├── Application/
        └── Infrastructure/
            ├── Http/
            ├── WebSocket/
            ├── Persistence/
            ├── Queue/
            └── Routes/
```


---

## Architecture Rules & Best Practices

### Date Handling

**Rule**: Domain entities store dates as ISO8601 strings, not Carbon instances.

**Flow**:
1. **Eloquent Model** → Has Carbon instances (`created_at`, `updated_at`, `deleted_at`)
2. **Mapper** → Converts Carbon to ISO8601 string using `->toIso8601String()`
3. **Domain Entity** → Stores dates as `string` properties (camelCase: `createdAt`, `updatedAt`, `deletedAt`)
4. **Query Handler** → Passes strings directly to ReadModels/DTOs (NO additional conversion)
5. **Frontend** → Receives ISO8601 strings, parses with `new Date()`

**Example**:

```php
// ❌ WRONG - Trying to call toISOString() on a string
createdAt: $user->created_at?->toISOString() ?? '',

// ✅ CORRECT - Domain entity already has ISO8601 string
createdAt: $user->createdAt ?? '',
```

**Mapper Example**:
```php
// Infrastructure/Persistence/Mappers/UserMapper.php
public static function toDomain(UserEloquentModel $model): User
{
    return new User(
        // ... other properties
        createdAt: $model->created_at?->toIso8601String(),  // Carbon → string
        updatedAt: $model->updated_at?->toIso8601String(),  // Carbon → string
        deletedAt: $model->deleted_at?->toIso8601String(),  // Carbon → string
    );
}
```

**Query Handler Example**:
```php
// Application/Queries/ListUsers/ListUsersHandler.php
$result['data'] = array_map(
    fn($user) => new UserListReadModel(
        // ... other properties
        createdAt: $user->createdAt ?? '',      // Already a string
        updatedAt: $user->updatedAt ?? '',      // Already a string
        deletedAt: $user->deletedAt,            // Already a string or null
    ),
    $result['data']
);
```

### Property Naming Convention

**Rule**: Use camelCase in domain entities, snake_case only in Eloquent models.

- **Eloquent Model**: `created_at`, `updated_at`, `profile_photo_path`
- **Domain Entity**: `createdAt`, `updatedAt`, `profilePhotoPath`
- **ReadModel/DTO**: `createdAt`, `updatedAt`, `profilePhotoPath`
- **Frontend**: `created_at`, `updated_at`, `profile_photo_path` (matches API response)

### Cache Management

**Rule**: Use cache tags for list queries, clear tags on mutations.

**List Query**:
```php
// Try cache tags first (Redis/Memcached)
try {
    return Cache::tags(['users_list'])->remember($cacheKey, $ttl, function () {
        return $this->fetchData();
    });
} catch (\Exception $e) {
    // Fallback to regular cache
    return Cache::remember($cacheKey, $ttl, function () {
        return $this->fetchData();
    });
}
```

**Mutation Handler**:
```php
// Clear individual cache
Cache::forget("user_{$uuid}");

// Clear list cache tags
try {
    Cache::tags(['users_list'])->flush();
} catch (\Exception $e) {
    // Tags not supported, cache will expire naturally
}
```

### Readonly Classes

**Rule**: Only use `readonly` for truly immutable classes.

**✅ Use `readonly` for**:
- Value Objects
- Domain Events
- Standalone immutable entities

**❌ Do NOT use `readonly` for**:
- Classes extending `Spatie\LaravelData\Data`
- Classes extending `AggregateRoot`
- Classes with mutable state (like event arrays)
- Classes with default property values

See `READONLY-FIXES-SUMMARY.md` for detailed explanation.
