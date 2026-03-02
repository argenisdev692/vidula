# Informe de Cumplimiento: Módulo Products

**Fecha:** 2 de marzo de 2026  
**Módulo:** `src/Modules/Products`  
**Arquitectura de referencia:** `.agents/skills/ARCHITECTURE-INTERMEDIATE-PHP.md`  
**Versión PHP:** 8.5

---

## Resumen Ejecutivo

El módulo Products presenta **problemas críticos idénticos** a los que tenía Students antes de la corrección. Requiere refactorización completa.

### Puntuación General: 3/10 ❌

- ❌ Arquitectura hexagonal: **3/10** (entity anémica, handlers desconectados)
- ❌ PHP 8.5 features: **1/10** (no usa ninguna característica)
- ⚠️ Manejo de fechas: **8/10** (mapper correcto, pero entity incompleta)
- ❌ Convenciones de nombres: **2/10** (inconsistencias críticas)
- ❌ Cache management: **4/10** (sin tags, sin invalidación)
- ❌ Domain logic: **2/10** (entity anémica, sin métodos)
- ❌ Value Objects: **2/10** (Coordinates y SocialLinks NO existen en tabla)

---

## 🔴 PROBLEMAS CRÍTICOS

### 1. Value Objects Inexistentes en la Tabla

**CRÍTICO:** El módulo tiene `Coordinates.php` y `SocialLinks.php` pero la tabla `products` NO tiene estas columnas.

**Tabla Real:**
```sql
CREATE TABLE products (
    id BIGINT,
    uuid VARCHAR(255),
    user_id BIGINT,
    type VARCHAR(255),        -- classroom, video
    title VARCHAR(255),
    slug VARCHAR(255),
    description TEXT,
    price DECIMAL(10,2),
    currency VARCHAR(3),
    status VARCHAR(255),
    thumbnail VARCHAR(255),
    level VARCHAR(255),
    language VARCHAR(255),
    created_at, updated_at, deleted_at
);
```

**Columnas que NO existen:**
- ❌ `latitude`, `longitude` (Coordinates)
- ❌ `facebook`, `instagram`, `linkedin`, `twitter`, `website` (SocialLinks)
- ❌ `company_name`, `email`, `phone`, `address` (datos de empresa)
- ❌ `signature_path`


### 2. Entity Anémica Sin Lógica

```php
// ❌ ACTUAL - Product.php
class Product
{
    public function __construct(
        public readonly ProductId $id,
        public readonly UserId $userId,
        public readonly string $type,
        public readonly string $title,
        // ... solo propiedades
    ) {}
}
```

**Problemas:**
- No extiende `AggregateRoot`
- No tiene método `create()` estático
- No tiene método `update()` para inmutabilidad
- No registra eventos de dominio
- No valida invariantes (precio > 0, type válido, etc.)

### 3. CreateProductHandler Completamente Desconectado

```php
// ❌ ACTUAL - CreateProductHandler.php
$product = Product::create(
    id: new ProductId($uuid),
    userId: new UserId($dto->userUuid),
    companyName: $dto->companyName,  // ❌ NO existe en Product ni en tabla
    email: $dto->email,              // ❌ NO existe
    phone: $dto->phone,              // ❌ NO existe
    address: $dto->address,          // ❌ NO existe
    status: CompanyStatus::Active    // ❌ Enum incorrecto
);
```

**Realidad de la tabla:**
- ✅ `user_id`, `type`, `title`, `slug`, `description`
- ✅ `price`, `currency`, `status`, `thumbnail`
- ✅ `level`, `language`

### 4. ListProductHandler Usa Propiedades Inexistentes

```php
// ❌ ACTUAL
new ProductReadModel(
    uuid: $product->id->value,
    userUuid: $product->userId->value,
    companyName: $product->companyName,  // ❌ NO existe
    email: $product->email,              // ❌ NO existe
    socialLinks: $product->socialLinks->toArray(),  // ❌ NO existe
    coordinates: $product->coordinates->toArray(),  // ❌ NO existe
    signatureUrl: $product->signaturePath  // ❌ NO existe
);
```

### 5. Repository Intenta Guardar Columnas Inexistentes

```php
// ❌ ACTUAL - EloquentProductRepository::save()
$model->fill([
    'company_name' => $product->companyName,  // ❌ NO existe en tabla
    'email' => $product->email,               // ❌ NO existe
    'website' => $socialLinks['website'],     // ❌ NO existe
    'latitude' => $coords['latitude'],        // ❌ NO existe
    // ...
]);
```

**Resultado:** Errores fatales al intentar guardar.


### 6. DTOs Desalineados

**CreateProductDTO:**
```php
// ✅ CORRECTO - Alineado con tabla
public function __construct(
    public int $user_id,      // ✅ snake_case (debería ser camelCase)
    public string $type,
    public string $title,
    public string $slug,
    public ?string $description,
    public float $price,
    public string $currency,
    public string $status,
    public ?string $thumbnail,
    public string $level,
    public string $language
) {}
```

**ProductReadModel:**
```php
// ⚠️ PARCIALMENTE CORRECTO
public function __construct(
    public string $id,
    public int $user_id,      // ❌ Debería ser camelCase: userId
    public string $type,
    // ...
    public ?string $created_at,  // ❌ Debería ser camelCase: createdAt
    public ?string $updated_at,  // ❌ Debería ser camelCase: updatedAt
    public ?string $deleted_at   // ❌ Debería ser camelCase: deletedAt
) {}
```

### 7. Namespace Inconsistente

```php
// ❌ INCORRECTO - Usa Product (singular)
namespace Modules\Product\Application\Commands\CreateProduct;
namespace Modules\Product\Domain\Entities;

// ✅ DEBERÍA SER Products (plural)
namespace Modules\Products\Application\Commands\CreateProduct;
namespace Modules\Products\Domain\Entities;
```

### 8. Enum Incorrecto

```php
// ❌ CompanyStatus.php - Nombre incorrecto para Products
enum CompanyStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}

// ✅ DEBERÍA SER ProductStatus
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
```

---

## Comparación con Tabla Real

| Propiedad en Código | ¿Existe en Tabla? | Tipo Real |
|---------------------|-------------------|-----------|
| `companyName` | ❌ NO | - |
| `email` | ❌ NO | - |
| `phone` | ❌ NO | - |
| `address` | ❌ NO | - |
| `socialLinks` | ❌ NO | - |
| `coordinates` | ❌ NO | - |
| `signaturePath` | ❌ NO | - |
| `type` | ✅ SÍ | string |
| `title` | ✅ SÍ | string |
| `slug` | ✅ SÍ | string |
| `description` | ✅ SÍ | text |
| `price` | ✅ SÍ | decimal(10,2) |
| `currency` | ✅ SÍ | string(3) |
| `status` | ✅ SÍ | string |
| `thumbnail` | ✅ SÍ | string |
| `level` | ✅ SÍ | string |
| `language` | ✅ SÍ | string |

---

## PHP 8.5 Features

### ❌ NO USA NINGUNA

- ❌ Pipe Operator
- ❌ Clone With
- ❌ URI Extension
- ❌ #[\NoDiscard]
- ❌ Validación en Value Objects

---

## Plan de Corrección

### 🔴 Fase 1: Eliminar Code Innecesario (30 min)

1. ❌ Eliminar `Coordinates.php`
2. ❌ Eliminar `SocialLinks.php`
3. ❌ Eliminar `CompanyStatus.php`
4. ✅ Crear `ProductStatus.php`
5. ✅ Crear `ProductType.php`
6. ✅ Crear `ProductLevel.php`

### 🔴 Fase 2: Refactorizar Entity (1 hora)

```php
// ✅ CORRECTO
final class Product extends AggregateRoot
{
    private function __construct(
        public readonly ProductId $id,
        public readonly UserId $userId,
        public readonly ProductType $type,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly Money $price,
        public readonly ProductStatus $status,
        public readonly ?string $thumbnail,
        public readonly ProductLevel $level,
        public readonly string $language,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $deletedAt = null
    ) {}

    public static function create(
        ProductId $id,
        UserId $userId,
        ProductType $type,
        string $title,
        string $slug,
        ?string $description,
        Money $price,
        ProductLevel $level,
        string $language,
        ?string $thumbnail = null
    ): self {
        $product = new self(
            id: $id,
            userId: $userId,
            type: $type,
            title: $title,
            slug: $slug,
            description: $description,
            price: $price,
            status: ProductStatus::Draft,
            thumbnail: $thumbnail,
            level: $level,
            language: $language,
            createdAt: now()->toIso8601String()
        );

        $product->recordEvent(new ProductCreated(
            aggregateId: $id->value,
            title: $title,
            occurredOn: now()->toDateTimeString()
        ));

        return $product;
    }

    public function update(
        string $title,
        string $slug,
        ?string $description,
        Money $price,
        ProductLevel $level,
        string $language,
        ?string $thumbnail
    ): self {
        return clone($this, [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'level' => $level,
            'language' => $language,
            'thumbnail' => $thumbnail,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function publish(): self
    {
        if ($this->status === ProductStatus::Published) {
            return $this;
        }

        return clone($this, [
            'status' => ProductStatus::Published,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function archive(): self
    {
        return clone($this, [
            'status' => ProductStatus::Archived,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === ProductStatus::Draft;
    }
}
```


### 🔴 Fase 3: Value Objects Necesarios (1 hora)

#### ProductType Enum
```php
enum ProductType: string
{
    case Classroom = 'classroom';
    case Video = 'video';
}
```

#### ProductStatus Enum
```php
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
```

#### ProductLevel Enum
```php
enum ProductLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
}
```

#### Money Value Object
```php
final readonly class Money
{
    public function __construct(
        public float $amount,
        public string $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                "Price cannot be negative, got: {$amount}"
            );
        }
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException(
                "Currency must be 3 characters (ISO 4217), got: {$currency}"
            );
        }
    }

    #[\NoDiscard]
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    #[\NoDiscard]
    public function multiply(float $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    #[\NoDiscard]
    public function format(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
```

### 🔴 Fase 4: Corregir Handlers (1 hora)

#### CreateProductHandler
```php
final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository
    ) {}

    public function handle(CreateProductCommand $command): void
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $product = Product::create(
            id: new ProductId($uuid),
            userId: new UserId($dto->userId),
            type: ProductType::from($dto->type),
            title: $dto->title,
            slug: $dto->slug,
            description: $dto->description,
            price: new Money($dto->price, $dto->currency),
            level: ProductLevel::from($dto->level),
            language: $dto->language,
            thumbnail: $dto->thumbnail
        );

        $this->repository->save($product);

        // Clear cache
        try {
            Cache::tags(['products_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
```

#### ListProductHandler con Pipe Operator
```php
public function handle(ListProductQuery $query): array
{
    $filters = $query->filters;
    $cacheKey = "products_list_" . md5(serialize($filters->toArray()));
    $ttl = 60 * 15;

    try {
        return Cache::tags(['products_list'])->remember($cacheKey, $ttl, function () use ($filters) {
            return $this->fetchData($filters);
        });
    } catch (\Exception $e) {
        return Cache::remember($cacheKey, $ttl, function () use ($filters) {
            return $this->fetchData($filters);
        });
    }
}

private function fetchData($filters): array
{
    $result = $this->repository->findAllPaginated(
        filters: $filters->toArray(),
        page: $filters->page,
        perPage: $filters->perPage
    );

    $result['data'] = $result['data']
        |> (fn($products) => array_map(
            fn($product) => new ProductReadModel(
                id: $product->id->value,
                userId: $product->userId->value,
                type: $product->type->value,
                title: $product->title,
                slug: $product->slug,
                description: $product->description,
                price: $product->price->amount,
                currency: $product->price->currency,
                status: $product->status->value,
                thumbnail: $product->thumbnail,
                level: $product->level->value,
                language: $product->language,
                createdAt: $product->createdAt,
                updatedAt: $product->updatedAt,
                deletedAt: $product->deletedAt
            ),
            $products
        ));

    return $result;
}
```

### 🔴 Fase 5: Corregir Repository (30 min)

```php
public function save(Product $product): void
{
    $model = ProductEloquentModel::withTrashed()
        ->where('uuid', $product->id->value)
        ->first() ?? new ProductEloquentModel();

    $user = UserEloquentModel::where('uuid', $product->userId->value)->firstOrFail();

    $model->fill([
        'uuid' => $product->id->value,
        'user_id' => $user->id,
        'type' => $product->type->value,
        'title' => $product->title,
        'slug' => $product->slug,
        'description' => $product->description,
        'price' => $product->price->amount,
        'currency' => $product->price->currency,
        'status' => $product->status->value,
        'thumbnail' => $product->thumbnail,
        'level' => $product->level->value,
        'language' => $product->language,
        'deleted_at' => $product->deletedAt,
    ]);

    $model->save();
}
```

### 🔴 Fase 6: Corregir Mapper con Pipe Operator (30 min)

```php
#[\NoDiscard]
public static function toDomain(ProductEloquentModel $model): Product
{
    return $model
        |> (fn($m) => [
            'id' => new ProductId($m->uuid),
            'userId' => new UserId($m->user?->uuid ?? ''),
            'type' => ProductType::from($m->type),
            'title' => $m->title,
            'slug' => $m->slug,
            'description' => $m->description,
            'price' => new Money((float) $m->price, $m->currency),
            'status' => ProductStatus::from($m->status),
            'thumbnail' => $m->thumbnail,
            'level' => ProductLevel::from($m->level),
            'language' => $m->language,
            'createdAt' => $m->created_at?->toIso8601String(),
            'updatedAt' => $m->updated_at?->toIso8601String(),
            'deletedAt' => $m->deleted_at?->toIso8601String()
        ])
        |> (fn($data) => new Product(...$data));
}
```

---

## Checklist de Correcciones

### Archivos a Eliminar
- [ ] `Domain/ValueObjects/Coordinates.php`
- [ ] `Domain/ValueObjects/SocialLinks.php`
- [ ] `Domain/Enums/CompanyStatus.php`

### Archivos a Crear
- [ ] `Domain/Enums/ProductType.php`
- [ ] `Domain/Enums/ProductStatus.php`
- [ ] `Domain/Enums/ProductLevel.php`
- [ ] `Domain/ValueObjects/Money.php`
- [ ] `Domain/Events/ProductCreated.php`

### Archivos a Refactorizar
- [ ] `Domain/Entities/Product.php` - Extender AggregateRoot, agregar métodos
- [ ] `Application/Commands/CreateProduct/CreateProductHandler.php`
- [ ] `Application/Commands/UpdateProduct/UpdateProductHandler.php`
- [ ] `Application/Queries/ListProduct/ListProductHandler.php`
- [ ] `Application/Queries/GetProduct/GetProductHandler.php`
- [ ] `Application/DTOs/CreateProductDTO.php` - camelCase
- [ ] `Application/DTOs/UpdateProductDTO.php` - camelCase
- [ ] `Application/Queries/ReadModels/ProductReadModel.php` - camelCase
- [ ] `Infrastructure/Persistence/Mappers/ProductMapper.php` - Pipe operator
- [ ] `Infrastructure/Persistence/Repositories/EloquentProductRepository.php`
- [ ] `Providers/ProductServiceProvider.php` - Namespace correcto

### Namespaces a Corregir
- [ ] Cambiar todos de `Modules\Product` a `Modules\Products`

---

## Conclusión

**Calificación Actual: 3/10** ❌

El módulo Products tiene los mismos problemas que tenía Students:
- Entity anémica
- Handlers desconectados de la realidad
- Value Objects inexistentes en la tabla
- Sin características PHP 8.5
- Namespace inconsistente

**Tiempo Estimado de Corrección: 4-5 horas**

**Calificación Objetivo: 10/10** ✅

---

**Elaborado por:** Kiro AI Assistant  
**Fecha:** 2 de marzo de 2026  
**Próxima acción:** Aplicar correcciones siguiendo el plan

