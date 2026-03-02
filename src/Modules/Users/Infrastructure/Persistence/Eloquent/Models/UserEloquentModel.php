<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\SocialiteProviderEloquentModel;
use Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models\CompanyDataEloquentModel;

/**
 * UserEloquentModel
 * 
 * @internal — Infrastructure only. Use UserRepositoryPort.
 */
class UserEloquentModel extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasOneTimePasswords, HasRoles, LogsActivity, SoftDeletes;

    protected $table = 'users';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\UserFactory
    {
        return \Database\Factories\UserFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'last_name',
        'username',
        'date_of_birth',
        'email',
        'password',
        'phone',
        'address',
        'zip_code',
        'city',
        'state',
        'country',
        'gender',
        'profile_photo_path',
        'terms_and_conditions',
        'latitude',
        'longitude',
        'status',
        'setup_token',
        'setup_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'setup_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'setup_token_expires_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<CompanyDataEloquentModel, $this>
     */
    public function companyData(): HasOne
    {
        return $this->hasOne(CompanyDataEloquentModel::class, 'user_id');
    }

    /**
     * @return HasOne<UserProfileEloquentModel, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfileEloquentModel::class, 'user_id');
    }

    /**
     * @return HasMany<UserActivityEloquentModel, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivityEloquentModel::class, 'user_id');
    }

    /**
     * OAuth social connections (Google, GitHub, etc.).
     *
     * @return HasMany<SocialiteProviderEloquentModel, $this>
     */
    public function socialiteProviders(): HasMany
    {
        return $this->hasMany(SocialiteProviderEloquentModel::class, 'user_id');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeInDateRange($query, ?string $from, ?string $to): void
    {
        $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to));
    }
}
