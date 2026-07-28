<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Mobile/API social login payload: the client posts the provider access token
 * obtained from the native SDK. Scramble reads the validation attributes.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class SocialTokenExchangeData extends Data
{
    public function __construct(
        #[Required, Max(4096)]
        public string $accessToken,
    ) {}
}
