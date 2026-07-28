<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class InvoiceItemData extends Data
{
    public function __construct(
        public string $title,
        public float $quantity,
        public float $unitPrice,
        public ?string $serviceUuid = null,
        public ?string $description = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'service_uuid' => ['nullable', 'uuid', 'exists:services,uuid'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
