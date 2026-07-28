<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

/**
 * How a classroom product is delivered. Null on video products.
 */
enum ProductModality: string
{
    case Online = 'online';
    case Presential = 'presential';
    case Hybrid = 'hybrid';
}
