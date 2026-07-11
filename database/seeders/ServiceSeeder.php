<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Ramsey\Uuid\Uuid;

/**
 * Foundation service catalog matching the landing page's `<select>` options.
 * Must run after {@see UserSeeder} (`user_id => 1` is the seeded super admin).
 */
final class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Landing Page',
                'slug' => 'landing_page',
                'description' => 'Single-page site optimized for a specific campaign or offer.',
                'sort_order' => 0,
            ],
            [
                'name' => 'Web App',
                'slug' => 'web_app',
                'description' => 'Multi-page web application with custom business logic.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Mobile App',
                'slug' => 'mobile_app',
                'description' => 'Native or cross-platform mobile application for iOS and Android.',
                'sort_order' => 2,
            ],
        ];

        foreach ($services as $service) {
            ServiceEloquentModel::query()->updateOrCreate(
                ['slug' => $service['slug']],
                [
                    'uuid' => Uuid::uuid7()->toString(),
                    'name' => $service['name'],
                    'description' => $service['description'],
                    'is_active' => true,
                    'sort_order' => $service['sort_order'],
                    'user_id' => 1,
                ],
            );
        }
    }
}
