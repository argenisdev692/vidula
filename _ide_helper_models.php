<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property string|null $name
 * @property string $company_name
 * @property string|null $signature_path
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $address_2
 * @property string|null $website
 * @property string|null $facebook_link
 * @property string|null $instagram_link
 * @property string|null $linkedin_link
 * @property string|null $twitter_link
 * @property string|null $tiktok_link
 * @property string|null $logo_path
 * @property string|null $logo_white_path
 * @property string|null $mark_path
 * @property int $user_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereFacebookLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereInstagramLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereLinkedinLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereLogoWhitePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereMarkPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereSignaturePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereTiktokLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereTwitterLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyData withoutTrashed()
 */
	class CompanyData extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $username
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $password_changed_at
 * @property bool $must_change_password
 * @property \Illuminate\Support\Carbon|null $invited_at
 * @property string|null $invited_by
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompanyData> $companyData
 * @property-read int|null $company_data_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\OneTimePasswords\Models\OneTimePassword> $oneTimePasswords
 * @property-read int|null $one_time_passwords_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Auth\Infrastructure\Persistence\Eloquent\Models\PasswordHistoryEloquentModel> $passwordHistories
 * @property-read int|null $password_histories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Auth\Infrastructure\Persistence\Eloquent\Models\LinkedSocialAccountEloquentModel> $socialAccounts
 * @property-read int|null $social_accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $teams
 * @property-read int|null $teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Auth\Infrastructure\Persistence\Eloquent\Models\TrustedDeviceEloquentModel> $trustedDevices
 * @property-read int|null $trusted_devices_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User applyFilters(\Modules\Users\Application\DTOs\UserFilterData $filters)
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User team($teams, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereInvitedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMustChangePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTermsAndConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTeam($teams)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {}
}

