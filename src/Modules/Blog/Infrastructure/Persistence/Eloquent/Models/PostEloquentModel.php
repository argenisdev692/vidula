<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PostEloquentModel extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'posts';

    protected $fillable = [
        'uuid',
        'post_title',
        'post_title_slug',
        'post_content',
        'post_excerpt',
        'post_cover_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'category_id',
        'user_id',
        'post_status',
        'published_at',
        'scheduled_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['post_title', 'post_title_slug', 'post_status', 'category_id', 'published_at', 'scheduled_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('blog.posts');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategoryEloquentModel::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }

    public function scopeInDateRange(Builder $query, ?string $from, ?string $to): void
    {
        $query
            ->when($from, fn(Builder $q): Builder => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn(Builder $q): Builder => $q->whereDate('created_at', '<=', $to));
    }
}
