<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Modules\Products\Application\DTOs\ProductFilterData;
use Modules\Products\Domain\Enums\ProductModality;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $client_id
 * @property ProductType $type
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property ProductStatus $status
 * @property string|null $thumbnail
 * @property string $level
 * @property string $language
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $total_hours
 * @property int|null $total_sessions
 * @property ProductModality|null $modality
 * @property string|null $notes
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read ClientEloquentModel|null $client
 * @property-read ClassroomEloquentModel|null $classroom
 * @property-read VideoCourseEloquentModel|null $videoCourse
 * @property-read Collection<int, InvoiceEloquentModel> $invoices
 *
 * @mixin \Eloquent
 */
#[Table('products')]
#[Fillable([
    'uuid',
    'user_id',
    'client_id',
    'type',
    'title',
    'slug',
    'description',
    'price',
    'currency',
    'status',
    'thumbnail',
    'level',
    'language',
    'start_date',
    'end_date',
    'total_hours',
    'total_sessions',
    'modality',
    'notes',
])]
final class ProductEloquentModel extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ProductEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ClientEloquentModel, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientEloquentModel::class, 'client_id');
    }

    /**
     * @return HasOne<ClassroomEloquentModel, $this>
     */
    public function classroom(): HasOne
    {
        return $this->hasOne(ClassroomEloquentModel::class, 'product_id');
    }

    /**
     * @return HasOne<VideoCourseEloquentModel, $this>
     */
    public function videoCourse(): HasOne
    {
        return $this->hasOne(VideoCourseEloquentModel::class, 'product_id');
    }

    /**
     * @return HasMany<ProductSessionEloquentModel, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ProductSessionEloquentModel::class, 'product_id');
    }

    /**
     * @return HasMany<ProductMaterialEloquentModel, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(ProductMaterialEloquentModel::class, 'product_id');
    }

    /**
     * @return HasMany<ContentGenerationEloquentModel, $this>
     */
    public function contentGenerations(): HasMany
    {
        return $this->hasMany(ContentGenerationEloquentModel::class, 'product_id');
    }

    /**
     * @return HasMany<InvoiceEloquentModel, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(
            InvoiceEloquentModel::class,
            'product_id',
        );
    }

    public function productType(): ProductType
    {
        return $this->type instanceof ProductType
            ? $this->type
            : ProductType::from((string) $this->type);
    }

    /**
     * Shared list/export filter (BACKEND-PHP §5.2). Soft-delete `suspended` is
     * applied at the repository via `onlyTrashed()`.
     *
     * @param  Builder<ProductEloquentModel>  $query
     * @return Builder<ProductEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, ProductFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('description', 'like', $term);
            }))
            ->when(
                $filters->productStatus !== null,
                fn ($q) => $q->where('status', $filters->productStatus),
            )
            ->when(
                $filters->type !== null,
                fn ($q) => $q->where('type', $filters->type),
            )
            ->when(
                $filters->clientUuid !== null,
                fn ($q) => $q->whereHas('client', fn ($c) => $c->where('uuid', $filters->clientUuid)),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo !== null,
                fn ($q) => $q->whereBetween('created_at', [
                    CarbonImmutable::parse($filters->dateFrom)->startOfDay(),
                    CarbonImmutable::parse($filters->dateTo)->endOfDay(),
                ]),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo === null,
                fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($filters->dateFrom)->startOfDay()),
            )
            ->when(
                $filters->dateTo !== null && $filters->dateFrom === null,
                fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($filters->dateTo)->endOfDay()),
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'client_id' => 'integer',
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'modality' => ProductModality::class,
            'price' => 'decimal:2',
            'total_hours' => 'decimal:2',
            'total_sessions' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'type',
                'title',
                'slug',
                'price',
                'currency',
                'status',
                'level',
                'language',
                'start_date',
                'end_date',
                'total_hours',
                'total_sessions',
                'modality',
                'client_id',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('products');
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
