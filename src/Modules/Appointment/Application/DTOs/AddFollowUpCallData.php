<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\DTOs;

use Spatie\LaravelData\Data;

final class AddFollowUpCallData extends Data
{
    public function __construct(
        public string $note,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:1000'],
        ];
    }
}
