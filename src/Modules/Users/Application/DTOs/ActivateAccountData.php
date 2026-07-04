<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Payload the invitee submits on the activation page: the password they choose
 * (confirmed). Validated with Laravel's default password policy.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class ActivateAccountData extends Data
{
    public function __construct(
        public string $password,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
