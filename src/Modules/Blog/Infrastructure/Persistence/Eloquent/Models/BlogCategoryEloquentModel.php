<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @internal
 */
final class BlogCategoryEloquentModel extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'blog_categories';

    protected $fillable = [
        'uuid',
        'blog_category_name',
        'blog_category_description',
        'blog_category_image',
        'user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['blog_category_name', 'blog_category_description', 'blog_category_image'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('blog.categories');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }
}
