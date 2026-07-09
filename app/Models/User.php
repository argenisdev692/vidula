<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonImmutable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\LinkedSocialAccountEloquentModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\PasswordHistoryEloquentModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\TrustedDeviceEloquentModel;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
use Modules\Users\Application\DTOs\UserFilterData;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\OneTimePasswords\Models\OneTimePassword;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $uuid
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $username
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $date_of_birth
 * @property string|null $address
 * @property string|null $address_2
 * @property string|null $zip_code
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $gender
 * @property string|null $profile_photo_path
 * @property bool $terms_and_conditions
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $password_changed_at
 * @property bool $must_change_password
 * @property Carbon|null $invited_at
 * @property string|null $invited_by
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Collection<int, CompanyData> $companyData
 * @property-read int|null $company_data_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, OneTimePassword> $oneTimePasswords
 * @property-read int|null $one_time_passwords_count
 * @property-read Collection<int, PasswordHistoryEloquentModel> $passwordHistories
 * @property-read int|null $password_histories_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, LinkedSocialAccountEloquentModel> $socialAccounts
 * @property-read int|null $social_accounts_count
 * @property-read Collection<int, Permission> $teams
 * @property-read int|null $teams_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read Collection<int, TrustedDeviceEloquentModel> $trustedDevices
 * @property-read int|null $trusted_devices_count
 *
 * @method static Builder<static>|User applyFilters(\Modules\Users\Application\DTOs\UserFilterData $filters)
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User onlyTrashed()
 * @method static Builder<static>|User permission($permissions, bool $without = false)
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static Builder<static>|User team($teams, bool $without = false)
 * @method static Builder<static>|User whereAddress($value)
 * @method static Builder<static>|User whereAddress2($value)
 * @method static Builder<static>|User whereCity($value)
 * @method static Builder<static>|User whereCountry($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereDateOfBirth($value)
 * @method static Builder<static>|User whereDeletedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereFirstName($value)
 * @method static Builder<static>|User whereGender($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereInvitedAt($value)
 * @method static Builder<static>|User whereInvitedBy($value)
 * @method static Builder<static>|User whereLastName($value)
 * @method static Builder<static>|User whereLatitude($value)
 * @method static Builder<static>|User whereLongitude($value)
 * @method static Builder<static>|User whereMustChangePassword($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User wherePasswordChangedAt($value)
 * @method static Builder<static>|User wherePhone($value)
 * @method static Builder<static>|User whereProfilePhotoPath($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereState($value)
 * @method static Builder<static>|User whereTermsAndConditions($value)
 * @method static Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static Builder<static>|User whereTwoFactorSecret($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User whereUsername($value)
 * @method static Builder<static>|User whereUuid($value)
 * @method static Builder<static>|User whereZipCode($value)
 * @method static Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|User withoutPermission($permissions)
 * @method static Builder<static>|User withoutRole($roles, ?string $guard = null)
 * @method static Builder<static>|User withoutTeam($teams)
 * @method static Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasOneTimePasswords, HasRoles, HasUuids, LogsActivity, MustVerifyEmail, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

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
        'address_2',
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

    /**
     * Generate a UUID for the `uuid` column (NOT the integer `id` primary key)
     * on create. Overriding uniqueIds() keeps the auto-increment PK intact.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
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
            ->when($filters->dateFrom !== null && $filters->dateTo !== null, fn ($q) => $q->whereBetween('created_at', [
                CarbonImmutable::parse($filters->dateFrom)->startOfDay(),
                CarbonImmutable::parse($filters->dateTo)->endOfDay(),
            ]))
            ->when($filters->dateFrom !== null && $filters->dateTo === null, fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($filters->dateFrom)->startOfDay()))
            ->when($filters->dateFrom === null && $filters->dateTo !== null, fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($filters->dateTo)->endOfDay()));
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
     * Blog categories authored by this user.
     *
     * @return HasMany<BlogCategoryEloquentModel, $this>
     */
    public function blogCategories(): HasMany
    {
        return $this->hasMany(BlogCategoryEloquentModel::class);
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
