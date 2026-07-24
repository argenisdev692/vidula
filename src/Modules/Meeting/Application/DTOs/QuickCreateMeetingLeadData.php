<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Minimal lead capture from the meeting attendee picker when the person is not
 * already a User / Lead / Contact. Creates an Appointment (lead) record so
 * history stays on the lead row — never denormalized onto `meetings`.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class QuickCreateMeetingLeadData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        if (isset($properties['email']) && is_string($properties['email'])) {
            $properties['email'] = mb_strtolower(trim($properties['email']));
        }

        return $properties;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('appointments', 'email')->whereNull('deleted_at'),
            ],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
