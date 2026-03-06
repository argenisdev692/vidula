<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\CompanyData\Domain\Entities\CompanyData;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\Coordinates;
use Modules\CompanyData\Domain\ValueObjects\SocialLinks;
use Modules\CompanyData\Domain\ValueObjects\UserId;

it('creates a new company data entity', function (): void {
    $id = new CompanyDataId(Str::uuid()->toString());
    $userId = new UserId(Str::uuid()->toString());

    $company = CompanyData::create(
        id: $id,
        userId: $userId,
        companyName: 'Acme Corp',
        email: 'info@acme.com',
        phone: '123456789',
        address: '123 Acme Street',
    );

    expect($company->id->value)->toBe($id->value)
        ->and($company->companyName)->toBe('Acme Corp')
        ->and($company->email)->toBe('info@acme.com')
        ->and($company->status->value)->toBe('active')
        ->and($company->deletedAt)->toBeNull();
});

it('updates company data using clone', function (): void {
    $company = CompanyData::create(
        id: new CompanyDataId(Str::uuid()->toString()),
        userId: new UserId(Str::uuid()->toString()),
        companyName: 'Old Name',
    );

    $updated = $company->update(
        companyName: 'New Name',
        name: 'Jane Doe',
        email: 'new@email.com',
        phone: '987654321',
        address: '456 New Street',
        socialLinks: new SocialLinks(website: 'https://new.com'),
        coordinates: new Coordinates(10.0, 20.0),
    );

    expect($company->companyName)->toBe('Old Name') // Original is unchanged
        ->and($updated->companyName)->toBe('New Name')
        ->and($updated->name)->toBe('Jane Doe')
        ->and($updated->socialLinks->website)->toBe('https://new.com')
        ->and($updated->coordinates->latitude)->toBe(10.0)
        ->and($updated->updatedAt)->not->toBeNull();
});

it('soft deletes company data using clone', function (): void {
    $company = CompanyData::create(
        id: new CompanyDataId(Str::uuid()->toString()),
        userId: new UserId(Str::uuid()->toString()),
        companyName: 'Acme Corp',
    );

    $deleted = $company->softDelete();

    expect($company->deletedAt)->toBeNull()
        ->and($deleted->deletedAt)->not->toBeNull();
});

it('validates coordinate boundaries', function (): void {
    expect(fn() => new Coordinates(95.0, 0.0))->toThrow(\InvalidArgumentException::class)
        ->and(fn() => new Coordinates(-95.0, 0.0))->toThrow(\InvalidArgumentException::class)
        ->and(fn() => new Coordinates(0.0, 185.0))->toThrow(\InvalidArgumentException::class)
        ->and(fn() => new Coordinates(0.0, -185.0))->toThrow(\InvalidArgumentException::class);
});

it('validates social link URLs', function (): void {
    expect(fn() => new SocialLinks(website: 'invalid-url'))->toThrow(\InvalidArgumentException::class)
        ->and(fn() => new SocialLinks(facebook: 'not-a-url'))->toThrow(\InvalidArgumentException::class);
});
