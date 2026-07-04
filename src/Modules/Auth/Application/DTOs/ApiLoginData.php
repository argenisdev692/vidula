<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class ApiLoginData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public string $email,

        #[Required, Max(255)]
        public string $password,

        /** Human-readable device label used as the Sanctum token name. */
        #[Max(255)]
        public string $deviceName = 'api',
    ) {}
}
