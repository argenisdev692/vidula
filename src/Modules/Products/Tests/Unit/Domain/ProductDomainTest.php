<?php

declare(strict_types=1);

use Modules\Products\Domain\Entities\Product;
use Modules\Products\Domain\Enums\ProductLevel;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Events\ProductCreated;
use Modules\Products\Domain\Events\ProductUpdated;
use Modules\Products\Domain\ValueObjects\Money;
use Modules\Products\Domain\ValueObjects\ProductId;
use Modules\Products\Domain\ValueObjects\UserId;

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeProduct(
    string $title = 'PHP 8.5 Masterclass',
    float $amount = 99.99,
    string $currency = 'USD',
    string $status = 'Draft',
): Product {
    return Product::create(
        id: new ProductId('550e8400-e29b-41d4-a716-446655440000'),
        userId: new UserId('550e8400-e29b-41d4-a716-446655440001'),
        type: ProductType::Classroom,
        title: $title,
        slug: 'php-8-5-masterclass',
        description: null,
        price: new Money($amount, $currency),
        level: ProductLevel::Beginner,
        language: 'en',
    );
}

// ─── Money ValueObject ───────────────────────────────────────────────────────

test('Money rejects negative amount via property hook', function (): void {
    expect(fn() => new Money(-1.0, 'USD'))
        ->toThrow(InvalidArgumentException::class, 'negative');
});

test('Money rejects currency not exactly 3 chars via property hook', function (): void {
    expect(fn() => new Money(10.0, 'US'))
        ->toThrow(InvalidArgumentException::class, '3 characters');
});

test('Money uppercases currency in property hook', function (): void {
    $m = new Money(10.0, 'usd');
    expect($m->currency)->toBe('USD');
});

test('Money::add enforces same currency', function (): void {
    $usd = new Money(10.0, 'USD');
    $eur = new Money(5.0, 'EUR');
    expect(fn() => $usd->add($eur))
        ->toThrow(InvalidArgumentException::class, 'Cannot add');
});

test('Money::isZero detects zero amount', function (): void {
    expect((new Money(0.0, 'USD'))->isZero())->toBeTrue();
    expect((new Money(0.01, 'USD'))->isZero())->toBeFalse();
});

test('Money::multiply returns new instance with scaled amount', function (): void {
    $m = new Money(10.0, 'USD');
    $result = $m->multiply(2.5);
    expect($result->amount)->toBe(25.0)
        ->and($result)->not->toBe($m);         // new instance
});

// ─── Product Entity ──────────────────────────────────────────────────────────

test('Product::create records ProductCreated domain event', function (): void {
    $product = makeProduct();

    expect($product->pullDomainEvents())->toHaveCount(1)
        ->and($product->pullDomainEvents())->toBeEmpty(); // pulled = cleared
});

test('Product::create sets status to Draft', function (): void {
    $product = makeProduct();
    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($product->isDraft())->toBeTrue();
});

test('Product::update uses clone($this, [...]) and records ProductUpdated', function (): void {
    $product = makeProduct(title: 'Old Title');
    // drain the Created event
    $product->pullDomainEvents();

    $updated = $product->update(
        title: 'New Title',
        slug: 'new-title',
        description: null,
        price: new Money(199.0, 'USD'),
        level: ProductLevel::Advanced,
        language: 'es',
        thumbnail: null,
    );

    // Must be a NEW instance (clone)
    expect($updated)->not->toBe($product);
    expect($updated->title)->toBe('New Title');
    expect($product->title)->toBe('Old Title');   // original is immutable

    $events = $updated->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ProductUpdated::class);
});

test('Product::publish via clone returns Published product', function (): void {
    $product = makeProduct();
    $product->pullDomainEvents();

    $published = $product->publish();

    expect($published)->not->toBe($product);
    expect($published->isPublished())->toBeTrue();
    expect($product->isDraft())->toBeTrue(); // original unchanged
});

test('Product::publish is idempotent when already published', function (): void {
    $product = makeProduct();
    $product->pullDomainEvents();
    $published = $product->publish();

    // Publish again — must return same instance
    $publishedAgain = $published->publish();
    expect($publishedAgain)->toBe($published);
});

test('Product::changePrice returns clone with new price', function (): void {
    $product = makeProduct(amount: 50.0);
    $product->pullDomainEvents();

    $repriced = $product->changePrice(new Money(200.0, 'USD'));

    expect($repriced)->not->toBe($product);
    expect($repriced->price->amount)->toBe(200.0);
    expect($product->price->amount)->toBe(50.0); // original unchanged
});

test('Product::isFree returns true for zero-price product', function (): void {
    $product = makeProduct(amount: 0.0);
    expect($product->isFree())->toBeTrue();
});
