<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\PortfolioMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Shared\Domain\Ports\StoragePort;
use Throwable;

/**
 * One gallery image belonging to a {@see PortfolioEloquentModel}. A lean child
 * entity (no LogsActivity — the parent portfolio's own audit trail already
 * captures cover/video changes; per-photo gallery churn is not audit-worthy).
 *
 * @property int $id
 * @property string $uuid
 * @property int $portfolio_id
 * @property string $path
 * @property int $sort_order
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $url
 * @property-read PortfolioEloquentModel $portfolio
 *
 * @mixin \Eloquent
 */
#[Table('portfolio_media')]
#[Fillable(['uuid', 'portfolio_id', 'path', 'sort_order'])]
final class PortfolioMediaEloquentModel extends Model
{
    /** @use HasFactory<PortfolioMediaFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    /**
     * Resolve the public gallery image URL alongside the raw column so the
     * frontend never concatenates R2 keys by hand.
     *
     * @var list<string>
     */
    protected $appends = ['url'];

    protected static function booted(): void
    {
        self::creating(function (PortfolioMediaEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<PortfolioEloquentModel, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(PortfolioEloquentModel::class);
    }

    /**
     * Permanent public URL for the stored R2 object key, resolved through the
     * StoragePort adapter. Null when no path is set; degrades to null on any R2
     * failure rather than breaking the whole gallery render.
     *
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->path === null || $this->path === '') {
                return null;
            }

            try {
                return app(StoragePort::class)->publicUrl($this->path);
            } catch (Throwable) {
                return null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'portfolio_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): PortfolioMediaFactory
    {
        return PortfolioMediaFactory::new();
    }
}
