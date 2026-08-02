<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Clients\Application\DTOs\ClientFilterData;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $client_name
 * @property string|null $email
 * @property string $status
 * @property string $phone
 * @property string|null $address
 * @property string|null $country
 * @property string|null $country_code
 * @property string|null $tax_id
 * @property string|null $nif
 * @property string|null $website
 * @property string|null $facebook_link
 * @property string|null $instagram_link
 * @property string|null $linkedin_link
 * @property string|null $twitter_link
 * @property string|null $notes
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, InvoiceEloquentModel> $invoices
 * @property-read Collection<int, ProductEloquentModel> $products
 * @property-read int|null $invoices_count
 * @property-read int|null $products_count
 *
 * @mixin \Eloquent
 */
#[Table('clients')]
#[Fillable([
    'uuid',
    'user_id',
    'client_name',
    'email',
    'status',
    'phone',
    'address',
    'country',
    'country_code',
    'tax_id',
    'nif',
    'website',
    'facebook_link',
    'instagram_link',
    'linkedin_link',
    'twitter_link',
    'notes',
])]
final class ClientEloquentModel extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ClientEloquentModel $model): void {
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
     * @return HasMany<InvoiceEloquentModel, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceEloquentModel::class, 'client_id');
    }

    /**
     * Products optionally billed/linked to this client.
     *
     * @return HasMany<ProductEloquentModel, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductEloquentModel::class, 'client_id');
    }

    /**
     * Shared list/export filter (BACKEND-PHP §5.2). Soft-delete `suspended` is
     * applied at the repository via `onlyTrashed()`.
     *
     * @param  Builder<ClientEloquentModel>  $query
     * @return Builder<ClientEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, ClientFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('client_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('tax_id', 'like', $term)
                    ->orWhere('nif', 'like', $term);
            }))
            ->when(
                $filters->clientStatus !== null,
                fn ($q) => $q->where('status', $filters->clientStatus),
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
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'client_name',
                'email',
                'status',
                'phone',
                'address',
                'country',
                'country_code',
                'tax_id',
                'nif',
                'website',
                'facebook_link',
                'instagram_link',
                'linkedin_link',
                'twitter_link',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('clients');
    }

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }
}
