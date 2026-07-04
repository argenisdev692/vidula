<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Shared\Infrastructure\Company\CompanyProfile;

class CompanyData extends Model
{
    use SoftDeletes;

    protected $table = 'company_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'company_name',
        'signature_path',
        'email',
        'phone',
        'address',
        'address_2',
        'website',
        'facebook_link',
        'instagram_link',
        'linkedin_link',
        'twitter_link',
        'user_id',
        'latitude',
        'longitude',
    ];

    protected static function booted(): void
    {
        static::creating(function (CompanyData $company): void {
            if (empty($company->uuid)) {
                $company->uuid = (string) Str::uuid();
            }
        });

        // Keep the email-branding cache fresh.
        static::saved(static function (): void {
            CompanyProfile::forget();
        });
        static::deleted(static function (): void {
            CompanyProfile::forget();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
