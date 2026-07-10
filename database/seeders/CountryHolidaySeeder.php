<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Availability\Domain\Services\HolidayMaterializer;

final class CountryHolidaySeeder extends Seeder
{
    public function __construct(private readonly HolidayMaterializer $materializer) {}

    public function run(): void
    {
        foreach ([now()->year, now()->year + 1] as $year) {
            $this->materializer->materialize($year);
        }
    }
}
