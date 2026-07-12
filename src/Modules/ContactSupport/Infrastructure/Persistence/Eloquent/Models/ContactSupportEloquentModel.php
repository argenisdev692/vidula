<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ContactSupportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string $subject
 * @property string $message
 * @property bool $sms_consent
 * @property bool $readed
 * @property bool $is_spam
 * @property int $spam_score
 * @property array<int, string>|null $spam_reasons
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
#[Table('contact_supports')]
#[Fillable(['uuid', 'first_name', 'last_name', 'email', 'phone', 'subject', 'message', 'sms_consent', 'readed', 'is_spam', 'spam_score', 'spam_reasons'])]
final class ContactSupportEloquentModel extends Model
{
    /** @use HasFactory<ContactSupportFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ContactSupportEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * Reusable list filter (BACKEND-PHP §4.1 — single scope shared by
     * ListContactSupportsHandler, no duplicated `when()` chains). The `suspended`
     * status is applied at the repository via `onlyTrashed()`.
     *
     * @param  Builder<ContactSupportEloquentModel>  $query
     * @return Builder<ContactSupportEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, ContactSupportFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('subject', 'like', $term);
            }))
            ->when($filters->read === 'read', fn ($q) => $q->where('readed', true))
            ->when($filters->read === 'unread', fn ($q) => $q->where('readed', false))
            ->when($filters->spam === 'spam', fn ($q) => $q->where('is_spam', true))
            ->when($filters->spam === 'ham', fn ($q) => $q->where('is_spam', false))
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
            'sms_consent' => 'boolean',
            'readed' => 'boolean',
            'is_spam' => 'boolean',
            'spam_reasons' => 'array',
        ];
    }

    /**
     * Audit only the fields an operator can act on (read status + consent).
     * Names, email, phone and message are PII and are deliberately NOT logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'readed',
                'sms_consent',
                'is_spam',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('contact-support');
    }

    protected static function newFactory(): ContactSupportFactory
    {
        return ContactSupportFactory::new();
    }
}
