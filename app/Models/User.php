<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\LinkedSocialAccountEloquentModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\PasswordHistoryEloquentModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\TrustedDeviceEloquentModel;
use Modules\Users\Application\DTOs\UserFilterData;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasOneTimePasswords, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'username',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'date_of_birth',
        'address',
        'zip_code',
        'city',
        'state',
        'country',
        'gender',
        'profile_photo_path',
        'latitude',
        'longitude',
        'terms_and_conditions',
        'password_changed_at',
        'must_change_password',
        'invited_at',
        'invited_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

        // ANY password write (admin invite, Fortify update, reset) clears the
        // admin-forced "must change password" flag and stamps the change time.
        // Kept here so it is independent of which flow performed the update.
        static::updating(function (User $user): void {
            if ($user->isDirty('password') && $user->password !== null) {
                $user->must_change_password = false;
                $user->password_changed_at = now();
            }
        });
    }

    /**
     * Reusable list/export filter (BACKEND-PHP §4.1 — single scope shared by
     * ListUsersHandler and UserExportController, no duplicated `when()` chains).
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeApplyFilters($query, UserFilterData $filters)
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term);
            }))
            ->when($filters->status === 'pending', fn ($q) => $q->whereNull('password'))
            ->when($filters->status === 'active', fn ($q) => $q->whereNotNull('password'))
            ->when($filters->dateFrom !== null, fn ($q) => $q->whereDate('created_at', '>=', $filters->dateFrom))
            ->when($filters->dateTo !== null, fn ($q) => $q->whereDate('created_at', '<=', $filters->dateTo));
    }

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
            'terms_and_conditions' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'invited_at' => 'datetime',
        ];
    }

    /**
     * A user is "Pending" while they have been invited but have not yet set a
     * password through the activation link. Such a user cannot authenticate.
     */
    public function isPending(): bool
    {
        return $this->password === null;
    }

    /**
     * Account activation / email verification is a 6-digit code, not a signed
     * link: registration (Fortify's Registered event) and the "resend" endpoint
     * both funnel here. Uses the longer email-flow window (security.otp.email_minutes,
     * default 30 min) rather than the short login-code default. The code is
     * consumed on the OTP screen, which marks the email verified.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->sendOneTimePassword((int) config('security.otp.email_minutes', 30));
    }

    /**
     * @return HasMany<CompanyData, $this>
     */
    public function companyData(): HasMany
    {
        return $this->hasMany(CompanyData::class);
    }

    /**
     * 2FA trusted devices (30-day "remember this device" tokens).
     *
     * @return HasMany<TrustedDeviceEloquentModel, $this>
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDeviceEloquentModel::class);
    }

    /**
     * Linked OAuth identities (Google, GitHub).
     *
     * @return HasMany<LinkedSocialAccountEloquentModel, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(LinkedSocialAccountEloquentModel::class);
    }

    /**
     * Previously-used password hashes (no-reuse policy).
     *
     * @return HasMany<PasswordHistoryEloquentModel, $this>
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistoryEloquentModel::class);
    }

    /**
     * Audit trail (spatie/laravel-activitylog). Allowlist only — NEVER log
     * password, remember_token, or two-factor secrets/recovery codes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name',
                'last_name',
                'username',
                'email',
                'email_verified_at',
                'phone',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('auth.user');
    }
}
