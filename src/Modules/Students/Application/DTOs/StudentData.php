<?php

declare(strict_types=1);

namespace Modules\Students\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Fused create/update DTO for an LMS student (Store/Update share the same fields).
 * Lifecycle `status` is distinct from the soft-delete tombstone filter.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class StudentData extends Data
{
    public function __construct(
        public string $name,
        public string $status = 'DRAFT',
        public bool $active = true,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $dni = null,
        public ?string $address = null,
        public ?string $avatar = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        foreach (['email', 'phone', 'dni', 'address', 'avatar', 'notes'] as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] === '') {
                $properties[$field] = null;
            }
        }

        return $properties;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $uuid = request()->route('uuid');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($uuid, 'uuid'),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'phone:INTERNATIONAL'],
            'dni' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:2048', 'url'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', 'in:DRAFT,ACTIVE,ARCHIVED'],
            'active' => ['boolean'],
        ];
    }
}
