---
name: architecture-intermediate-php
description: Directory tree and file placement rules for the modular PHP and Laravel backend, including the project's Shared, Modules, Providers, and Middleware layers.
---

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

> **For architecture rules** (date handling, property naming, cache management, readonly classes) → see `BACKEND-PHP.md` §5.
> This file is the detailed directory tree ONLY.
