<?php

declare(strict_types=1);

namespace Shared\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * BaseDTO — Base class for all DTOs (Data Transfer Objects).
 * 
 * Uses spatie/laravel-data for validation and serialization.
 */
abstract class BaseDTO extends Data
{
    // Common DTO logic
}
