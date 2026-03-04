<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Products\Domain\Entities\Product;
use Modules\Products\Domain\Enums\ProductLevel;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\ValueObjects\Money;
use Modules\Products\Domain\ValueObjects\ProductId;
use Modules\Products\Domain\ValueObjects\UserId;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Mappers\ProductMapper;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─── ProductMapper roundtrip ─────────────────────────────────────────────────

it('maps Eloquent model to domain entity and back via repository', function (): void {
    $user = User::factory()->create();

    $model = ProductEloquentModel::factory()->create([
        'user_id' => $user->id,
        'type' => 'classroom',
        'title' => 'Mapper Test Product',
        'slug' => 'mapper-test-product',
        'description' => 'Roundtrip description',
        'price' => 49.99,
        'currency' => 'USD',
        'status' => 'draft',
        'level' => 'beginner',
        'language' => 'en',
    ]);

    $product = ProductMapper::toDomain($model);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id->value)->toBe($model->uuid)
        ->and($product->title)->toBe('Mapper Test Product')
        ->and($product->price->amount)->toBe(49.99)
        ->and($product->price->currency)->toBe('USD');
});

// ─── EloquentProductRepository ────────────────────────────────────────────────

it('saves and retrieves a product by id', function (): void {
    $user = User::factory()->create();

    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $product = Product::create(
        id: new ProductId($uuid),
        userId: new UserId($user->uuid),
        type: ProductType::Classroom,
        title: 'Integration Test Product',
        slug: 'integration-test-product',
        description: null,
        price: new Money(19.99, 'USD'),
        level: ProductLevel::Beginner,
        language: 'en',
    );

    $repo = app(EloquentProductRepository::class);
    $repo->save($product);

    $found = $repo->findById(new ProductId($uuid));

    expect($found)->not->toBeNull()
        ->and($found->id->value)->toBe($uuid)
        ->and($found->title)->toBe('Integration Test Product')
        ->and($found->price->amount)->toBe(19.99);
});

it('soft-deletes a product', function (): void {
    $user = User::factory()->create();
    $uuid = '550e8400-e29b-41d4-a716-446655440002';
    $product = Product::create(
        id: new ProductId($uuid),
        userId: new UserId($user->uuid),
        type: ProductType::Classroom,
        title: 'To Be Deleted',
        slug: 'to-be-deleted',
        description: null,
        price: new Money(0.0, 'USD'),
        level: ProductLevel::Beginner,
        language: 'en',
    );

    $repo = app(EloquentProductRepository::class);
    $repo->save($product);
    $repo->delete(new ProductId($uuid));

    $this->assertDatabaseHas('products', ['uuid' => $uuid]);
    expect(ProductEloquentModel::withTrashed()->where('uuid', $uuid)->first()->deleted_at)
        ->not->toBeNull();
});

it('restores a soft-deleted product', function (): void {
    $user = User::factory()->create();
    $uuid = '550e8400-e29b-41d4-a716-446655440003';

    ProductEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'deleted_at' => now(),
    ]);

    $repo = app(EloquentProductRepository::class);
    $repo->restore(new ProductId($uuid));

    expect(ProductEloquentModel::where('uuid', $uuid)->first()->deleted_at)->toBeNull();
});

it('returns paginated results', function (): void {
    $user = User::factory()->create();
    ProductEloquentModel::factory()->count(5)->create(['user_id' => $user->id]);

    $repo = app(EloquentProductRepository::class);
    $result = $repo->findAllPaginated([], 1, 3);

    expect($result['data'])->toHaveCount(3)
        ->and($result['total'])->toBeGreaterThanOrEqual(5)
        ->and($result['perPage'])->toBe(3)
        ->and($result['currentPage'])->toBe(1);
});
