<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\PortfolioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Portfolio\Application\DTOs\PortfolioFilterData;
use Shared\Domain\Ports\StoragePort;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Throwable;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string $client_name
 * @property string $project_type
 * @property list<string>|null $tech_stack
 * @property string|null $live_url
 * @property Carbon|null $published_at
 * @property bool $is_public
 * @property string|null $cover_path
 * @property string|null $video_path
 * @property string|null $description
 * @property int $sort_order
 * @property int $user_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $cover_url
 * @property-read string|null $video_url
 * @property-read User $user
 * @property-read Collection<int, PortfolioMediaEloquentModel> $gallery
 *
 * @mixin \Eloquent
 */
#[Table('portfolios')]
#[Fillable([
    'uuid', 'title', 'client_name', 'project_type', 'tech_stack', 'live_url', 'published_at',
    'is_public', 'cover_path', 'video_path', 'description', 'sort_order', 'user_id',
])]
final class PortfolioEloquentModel extends Model
{
    /** @use HasFactory<PortfolioFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    /**
     * Resolve the public asset URLs alongside the raw columns so the frontend
     * never concatenates R2 keys by hand.
     *
     * @var list<string>
     */
    protected $appends = ['cover_url', 'video_url'];

    protected static function booted(): void
    {
        self::creating(function (PortfolioEloquentModel $model): void {
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
     * Gallery images, ordered for rendering. Explicit `orderBy` here (rather than
     * only at the repository) so `$portfolio->gallery` is always render-ready,
     * whichever code path touches the relation.
     *
     * @return HasMany<PortfolioMediaEloquentModel, $this>
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(PortfolioMediaEloquentModel::class)->orderBy('sort_order');
    }

    /**
     * Reusable list filter (BACKEND-PHP §4.1 — single scope shared by
     * ListPortfoliosHandler, no duplicated `when()` chains). The `suspended`
     * status is applied at the repository via `onlyTrashed()`.
     *
     * @param  Builder<PortfolioEloquentModel>  $query
     * @return Builder<PortfolioEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, PortfolioFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('title', 'like', $term)
                    ->orWhere('client_name', 'like', $term);
            }))
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
     * Only the projects meant for public consumption (landing page feed).
     *
     * @param  Builder<PortfolioEloquentModel>  $query
     * @return Builder<PortfolioEloquentModel>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Permanent public URL for the stored R2 cover object key, resolved through
     * the StoragePort adapter. Degrades to null on any R2 failure rather than
     * breaking the whole list/card render.
     *
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->resolveAssetUrl($this->cover_path));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function videoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->resolveAssetUrl($this->video_path));
    }

    private function resolveAssetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        try {
            return app(StoragePort::class)->publicUrl($path);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_public' => 'boolean',
            'tech_stack' => 'array',
            'sort_order' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'client_name',
                'project_type',
                'tech_stack',
                'live_url',
                'published_at',
                'is_public',
                'cover_path',
                'video_path',
                'description',
                'sort_order',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('portfolio');
    }

    protected static function newFactory(): PortfolioFactory
    {
        return PortfolioFactory::new();
    }
}
