<?php

declare(strict_types=1);

namespace Modules\Services\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Create/update payload for a service (e.g. "Landing Page", "Web App"). Store
 * and Update share 100% of the fields and rules (DTO Fusion Rule), so a single
 * fused DTO is used. `slug` is the stable machine key consumed by the landing
 * page select input — validated unique the same way `BlogCategoryData->name`
 * is (`Rule::unique()->ignore($uuid, 'uuid')`), not a DB-level unique index.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ServiceData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public bool $isActive = true,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $uuid = request()->route('uuid');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('services', 'slug')->ignore($uuid, 'uuid'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
