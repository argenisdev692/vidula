# Client Module

## Overview
The Client module manages client/company information in the system using Clean Architecture principles and PHP 8.5 features.

## Architecture

```
Clients/
├── Domain/              # Business logic and rules
│   ├── Entities/       # Client aggregate root
│   ├── ValueObjects/   # Email, Coordinates, SocialLinks (with PHP 8.5 Property Hooks)
│   ├── Ports/          # Repository interfaces
│   └── Exceptions/     # Domain exceptions
├── Application/        # Use cases
│   ├── Commands/       # Create, Update, Delete, Restore
│   ├── Queries/        # Get, List
│   ├── DTOs/           # Data transfer objects
│   ├── ReadModels/     # Query response models
│   └── Services/       # ClientDataTransformer (with PHP 8.5 Pipe Operator)
└── Infrastructure/     # External concerns
    ├── Http/           # Controllers, Requests, Export
    ├── Persistence/    # Eloquent models, Repositories, Mappers
    └── Routes/         # API and Web routes
```

## Database Schema

```sql
CREATE TABLE clients (
    id BIGINT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE,
    user_id BIGINT FOREIGN KEY REFERENCES users(id),
    company VARCHAR(255),
    email VARCHAR(255) NULLABLE,
    phone VARCHAR(50) NULLABLE,
    address VARCHAR(1000) NULLABLE,
    tax_id VARCHAR(255) NULLABLE,
    
    -- Social Links
    website VARCHAR(255) NULLABLE,
    facebook_link VARCHAR(255) NULLABLE,
    instagram_link VARCHAR(255) NULLABLE,
    linkedin_link VARCHAR(255) NULLABLE,
    twitter_link VARCHAR(255) NULLABLE,
    
    -- Coordinates
    latitude DECIMAL(10,8) NULLABLE,
    longitude DECIMAL(11,8) NULLABLE,
    
    -- Other
    notes TEXT NULLABLE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE
);
```

## PHP 8.5 Features

### 1. Property Hooks

Value Objects use property hooks for automatic validation:

```php
// Coordinates with range validation
final readonly class Coordinates
{
    public function __construct(
        public ?float $latitude {
            set {
                if ($value !== null && ($value < -90 || $value > 90)) {
                    throw new \InvalidArgumentException('Latitude must be between -90 and 90');
                }
                $this->latitude = $value;
            }
        },
        public ?float $longitude {
            set {
                if ($value !== null && ($value < -180 || $value > 180)) {
                    throw new \InvalidArgumentException('Longitude must be between -180 and 180');
                }
                $this->longitude = $value;
            }
        }
    ) {}
}

// SocialLinks with URL validation
final readonly class SocialLinks
{
    public function __construct(
        public ?string $facebook {
            set => $this->facebook = $value !== null ? filter_var($value, FILTER_VALIDATE_URL) ? $value : null : null
        },
        // ... other properties
    ) {}
}

// Email with normalization
final readonly class Email
{
    public function __construct(
        public string $value {
            get => strtolower($this->value);
            set {
                $normalized = strtolower(trim($value));
                if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid email format");
                }
                $this->value = $normalized;
            }
        }
    ) {}
}
```

### 2. Pipe Operator

Data transformation using the pipe operator:

```php
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

### 3. `#[\NoDiscard]` Attribute

Prevents accidental ignoring of important return values:

```php
#[\NoDiscard]
public static function sanitizeInput(array $input): array
{
    // Warning if return value is not used
}
```

## API Endpoints

### Web Routes (Inertia)
- `GET /clients` - List clients page
- `GET /clients/create` - Create client page
- `GET /clients/{uuid}` - View client page
- `GET /clients/{uuid}/edit` - Edit client page

### API Routes (JSON)
- `GET /api/clients/admin` - List clients (paginated)
- `POST /api/clients/admin` - Create client
- `GET /api/clients/admin/{uuid}` - Get client details
- `PUT /api/clients/admin/{uuid}` - Update client
- `DELETE /api/clients/admin/{uuid}` - Soft delete client
- `PATCH /api/clients/admin/{uuid}/restore` - Restore deleted client
- `POST /api/clients/admin/bulk-delete` - Bulk delete clients
- `GET /api/clients/admin/export?format=pdf|excel` - Export clients

### Current User Routes
- `GET /api/clients/me` - Get current user's client data
- `PUT /api/clients/me` - Update current user's client data

## Commands

### CreateClient
Creates a new client record.

**Input:**
```php
CreateClientDTO(
    userUuid: string,
    companyName: string,
    email: ?string,
    phone: ?string,
    address: ?string,
    website: ?string,
    facebookLink: ?string,
    instagramLink: ?string,
    linkedinLink: ?string,
    twitterLink: ?string,
    latitude: ?float,
    longitude: ?float,
)
```

### UpdateClient
Updates an existing client.

**Input:**
```php
UpdateClientDTO(
    companyName: ?string,
    email: ?string,
    phone: ?string,
    address: ?string,
    website: ?string,
    facebookLink: ?string,
    instagramLink: ?string,
    linkedinLink: ?string,
    twitterLink: ?string,
    latitude: ?float,
    longitude: ?float,
)
```

### DeleteClient
Soft deletes a client.

**Input:** `uuid: string`

### RestoreClient
Restores a soft-deleted client.

**Input:** `uuid: string`

## Queries

### GetClient
Retrieves a single client by user UUID.

**Input:** `userUuid: string`

**Output:** `ClientReadModel`

### ListClient
Retrieves paginated list of clients with filtering.

**Input:**
```php
ClientFilterDTO(
    search: ?string,
    userUuid: ?string,
    dateFrom: ?string,
    dateTo: ?string,
    sortBy: ?string = 'created_at',
    sortDir: ?string = 'desc',
    page: int = 1,
    perPage: int = 15,
)
```

**Output:**
```php
[
    'data' => ClientReadModel[],
    'total' => int,
    'perPage' => int,
    'currentPage' => int,
    'lastPage' => int,
]
```

## Caching Strategy

### List Queries
- Cache key: `clients_list_{md5(filters)}`
- TTL: 15 minutes
- Tags: `['clients_list']`
- Fallback to regular cache if tags not supported

### Single Queries
- Cache key: `client_{userUuid}`
- TTL: 1 hour
- No tags

### Cache Invalidation
On mutations (create, update, delete, restore):
- Flush `clients_list` tag
- Forget individual client cache keys

## Export Functionality

### PDF Export
- Template: `resources/views/exports/pdf/client.blade.php`
- Includes: Company, Email, Phone, Website, Created At
- Styled with company branding

### Excel Export
- Multiple sheets support
- Formatted columns
- Date formatting

## Testing

```bash
# Run all Client module tests
php artisan test --filter=Client

# Run specific test suites
php artisan test tests/Feature/ApiClientCrudTest.php
php artisan test tests/Unit/Domain/ValueObjects/CoordinatesTest.php
```

## Factory & Seeder

```php
// Create 30 test clients
php artisan db:seed --class=ClientSeeder

// Create a single client
ClientFactory::new()->create([
    'company' => 'ACME Corp',
    'email' => 'info@acme.com',
]);
```

## Validation Rules

### Create Client
- `user_uuid`: required, uuid, exists in users table
- `company_name`: required, string, max 255
- `email`: nullable, email, max 255
- `phone`: nullable, string, max 50
- `address`: nullable, string, max 1000
- `website`: nullable, url, max 255
- Social links: nullable, url, max 255
- Coordinates: nullable, numeric

### Update Client
All fields optional except validation rules remain the same.

## Architecture Compliance

✅ Follows Clean Architecture principles
✅ Domain-driven design with aggregates and value objects
✅ CQRS pattern (Commands and Queries separated)
✅ Repository pattern with ports and adapters
✅ Proper date handling (ISO8601 strings in domain)
✅ Cache management with tags
✅ Readonly classes for immutable objects
✅ PHP 8.5 features (Property Hooks, Pipe Operator)

## Related Documentation

- [Architecture Guide](/.agents/skills/ARCHITECTURE-INTERMEDIATE-PHP.md)
- [PHP 8.5 Features](docs/PHP_8.5_FEATURES.md)
- [Date Handling Rules](/.agents/skills/ARCHITECTURE-INTERMEDIATE-PHP.md#date-handling)

## Notes

- No `name` field in clients table (only `company`)
- No `status` or `signature_path` fields
- All dates stored as ISO8601 strings in domain entities
- Eloquent models use Carbon, converted in mappers
- Property hooks provide automatic validation
- Pipe operator used for data transformation pipelines
