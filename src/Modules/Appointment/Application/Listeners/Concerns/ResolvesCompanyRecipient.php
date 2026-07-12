<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Listeners\Concerns;

use Modules\Appointment\Application\Listeners\SendNewLeadAdminEmailListener;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;

/**
 * Resolves the company / super-admin inbox address every appointment
 * notification is copied to. Falls back to the app's default From address when
 * the company singleton has not been seeded yet (mirrors the original
 * {@see SendNewLeadAdminEmailListener}
 * behaviour, now shared so every lifecycle listener stays DRY).
 */
trait ResolvesCompanyRecipient
{
    private function companyRecipient(CompanyRepositoryPort $companies): string
    {
        try {
            return $companies->getSingleton()->email ?: (string) config('mail.from.address');
        } catch (\Throwable) {
            return (string) config('mail.from.address');
        }
    }
}
