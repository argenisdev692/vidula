<?php

declare(strict_types=1);

use Modules\Products\Application\Commands\CreateProduct\CreateProductCommand;
use Modules\Products\Application\Commands\CreateProduct\CreateProductHandler;
use Modules\Products\Application\Commands\DeleteProduct\DeleteProductCommand;
use Modules\Products\Application\Commands\DeleteProduct\DeleteProductHandler;
use Modules\Products\Application\Commands\UpdateProduct\UpdateProductCommand;
use Modules\Products\Application\Commands\UpdateProduct\UpdateProductHandler;
use Modules\Products\Application\DTOs\CreateProductDTO;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Application\DTOs\UpdateProductDTO;
use Modules\Products\Application\Queries\GetProduct\GetProductHandler;
use Modules\Products\Application\Queries\GetProduct\GetProductQuery;
use Modules\Products\Application\Queries\ListProduct\ListProductHandler;
use Modules\Products\Application\Queries\ListProduct\ListProductQuery;
use Modules\Products\Application\Queries\ReadModels\ProductReadModel;
use Modules\Products\Domain\Entities\Product;
use Modules\Products\Domain\Enums\ProductLevel;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Exceptions\ProductNotFoundException;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\Money;
use Modules\Products\Domain\ValueObjects\ProductId;
use Modules\Products\Domain\ValueObjects\UserId;
use Shared\Infrastructure\Audit\AuditInterface;

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeReadModel(string $uuid = '550e8400-e29b-41d4-a716-446655440000'): ProductReadModel
{
    return new ProductReadModel(
        id: $uuid,
        userId: '550e8400-e29b-41d4-a716-446655440001',
        type: 'classroom',
        title: 'PHP 8.5 Masterclass',
        slug: 'php-8-5-masterclass',
        description: null,
        price: 99.99,
        currency: 'USD',
        status: 'draft',
        thumbnail: null,
        level: 'beginner',
        language: 'en',
        createdAt: '2026-03-04T17:00:00+00:00',
        updatedAt: null,
        deletedAt: null,
    );
}

function makeDomainProduct(string $uuid = '550e8400-e29b-41d4-a716-446655440000'): Product
{
    return Product::create(
        id: new ProductId($uuid),
        userId: new UserId('550e8400-e29b-41d4-a716-446655440001'),
        type: ProductType::Classroom,
        title: 'PHP 8.5 Masterclass',
        slug: 'php-8-5-masterclass',
        description: null,
        price: new Money(99.99, 'USD'),
        level: ProductLevel::Beginner,
        language: 'en',
    );
}

// ─── CreateProductHandler ────────────────────────────────────────────────────

test('CreateProductHandler persists product and calls audit', function (): void {
    $repo = Mockery::mock(ProductRepositoryPort::class);
    $audit = Mockery::mock(AuditInterface::class);

    $repo->shouldReceive('save')->once();
    $audit->shouldReceive('log')
        ->once()
        ->with('products.product', 'Product created', Mockery::type('array'));

    $dto = new CreateProductDTO(
        userId: '550e8400-e29b-41d4-a716-446655440001',
        type: 'classroom',
        title: 'PHP 8.5 Masterclass',
        slug: 'php-8-5-masterclass',
        description: null,
        price: 99.99,
        currency: 'USD',
        level: 'beginner',
        language: 'en',
    );

    $handler = new CreateProductHandler($repo, $audit);
    $handler->handle(new CreateProductCommand($dto));
});

// ─── UpdateProductHandler ────────────────────────────────────────────────────

test('UpdateProductHandler updates existing product and calls audit', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $product = makeDomainProduct($uuid);
    $product->pullDomainEvents();

    $repo = Mockery::mock(ProductRepositoryPort::class);
    $audit = Mockery::mock(AuditInterface::class);

    $repo->shouldReceive('findById')->once()->andReturn($product);
    $repo->shouldReceive('save')->once();
    $audit->shouldReceive('log')
        ->once()
        ->with('products.product', 'Product updated', Mockery::type('array'));

    $dto = new UpdateProductDTO(
        title: 'Updated Title',
        slug: 'updated-title',
        description: null,
        price: 149.0,
        currency: 'USD',
        level: 'Advanced',
        language: 'en',
    );

    $handler = new UpdateProductHandler($repo, $audit);
    $handler->handle(new UpdateProductCommand($uuid, $dto));
});

test('UpdateProductHandler throws ProductNotFoundException when product does not exist', function (): void {
    $repo = Mockery::mock(ProductRepositoryPort::class);
    $audit = Mockery::mock(AuditInterface::class);

    $repo->shouldReceive('findById')->once()->andReturn(null);

    $dto = new UpdateProductDTO(
        title: 'x',
        slug: 'x',
        description: null,
        price: 0.0,
        currency: 'USD',
        level: 'Beginner',
        language: 'en',
    );

    $handler = new UpdateProductHandler($repo, $audit);
    expect(fn() => $handler->handle(new UpdateProductCommand('550e8400-e29b-41d4-a716-446655440000', $dto)))
        ->toThrow(ProductNotFoundException::class);
});

// ─── DeleteProductHandler ────────────────────────────────────────────────────

test('DeleteProductHandler soft-deletes product and calls audit', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $product = makeDomainProduct($uuid);

    $repo = Mockery::mock(ProductRepositoryPort::class);
    $audit = Mockery::mock(AuditInterface::class);

    $repo->shouldReceive('findById')->once()->andReturn($product);
    $repo->shouldReceive('delete')->once();
    $audit->shouldReceive('log')
        ->once()
        ->with('products.product', 'Product soft-deleted', Mockery::type('array'));

    $handler = new DeleteProductHandler($repo, $audit);
    $handler->handle(new DeleteProductCommand($uuid));
});

test('DeleteProductHandler throws when product not found', function (): void {
    $repo = Mockery::mock(ProductRepositoryPort::class);
    $audit = Mockery::mock(AuditInterface::class);

    $repo->shouldReceive('findById')->once()->andReturn(null);

    $handler = new DeleteProductHandler($repo, $audit);
    expect(fn() => $handler->handle(new DeleteProductCommand('550e8400-e29b-41d4-a716-446655440000')))
        ->toThrow(ProductNotFoundException::class);
});

// ─── GetProductHandler ────────────────────────────────────────────────────────

test('GetProductHandler returns ReadModel from cache or repository', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $product = makeDomainProduct($uuid);

    $repo = Mockery::mock(ProductRepositoryPort::class);
    $repo->shouldReceive('findById')->once()->andReturn($product);

    // Use real Cache (array driver) to avoid need for Redis
    config(['cache.default' => 'array']);

    $handler = new GetProductHandler($repo);
    $result = $handler->handle(new GetProductQuery($uuid));

    expect($result)->toBeInstanceOf(ProductReadModel::class)
        ->and($result->id)->toBe($uuid)
        ->and($result->title)->toBe('PHP 8.5 Masterclass');
});

test('GetProductHandler throws ProductNotFoundException when not found', function (): void {
    $repo = Mockery::mock(ProductRepositoryPort::class);
    $repo->shouldReceive('findById')->andReturn(null);

    config(['cache.default' => 'array']);

    $handler = new GetProductHandler($repo);
    expect(fn() => $handler->handle(new GetProductQuery('550e8400-e29b-41d4-a716-446655440000')))
        ->toThrow(ProductNotFoundException::class);
});
