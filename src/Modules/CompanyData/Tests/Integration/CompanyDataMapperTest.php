<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models\CompanyDataEloquentModel;
use Modules\CompanyData\Infrastructure\Persistence\Mappers\CompanyDataMapper;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('maps Eloquent model to Domain entity correctly', function (): void {
    $user = User::factory()->create();
    $uuid = Str::uuid()->toString();

    $model = CompanyDataEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'company_name' => 'Mapper Corp',
        'latitude' => 12.34,
        'longitude' => 56.78,
        'website' => 'https://example.com',
    ]);

    // Manually load relation as Mapper expects it or we verify lazy load works
    $model->load('user');

    $entity = CompanyDataMapper::toDomain($model);

    expect($entity->id->value)->toBe($uuid)
        ->and($entity->userId->value)->toBe($user->uuid)
        ->and($entity->companyName)->toBe('Mapper Corp')
        ->and($entity->coordinates->latitude)->toBe(12.34)
        ->and($entity->coordinates->longitude)->toBe(56.78)
        ->and($entity->socialLinks->website)->toBe('https://example.com');
});
