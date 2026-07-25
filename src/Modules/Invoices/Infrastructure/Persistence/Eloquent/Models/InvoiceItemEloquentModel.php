<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $service_id
 * @property int $sort_order
 * @property string $title
 * @property string|null $description
 * @property string $quantity
 * @property string $unit_price
 * @property string $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InvoiceEloquentModel $invoice
 * @property-read ServiceEloquentModel|null $service
 *
 * @mixin \Eloquent
 */
#[Table('invoice_items')]
#[Fillable([
    'invoice_id',
    'service_id',
    'sort_order',
    'title',
    'description',
    'quantity',
    'unit_price',
    'amount',
])]
final class InvoiceItemEloquentModel extends Model
{
    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    /**
     * @return BelongsTo<InvoiceEloquentModel, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceEloquentModel::class, 'invoice_id');
    }

    /**
     * @return BelongsTo<ServiceEloquentModel, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceEloquentModel::class, 'service_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_id' => 'integer',
            'service_id' => 'integer',
            'sort_order' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }
}
