<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Queries;

use Illuminate\Support\Collection;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

/**
 * Feed for the navbar notification bell: the unread count plus the most
 * recent submissions (read or not), so the dropdown still shows recent
 * activity after everything has been read.
 */
final readonly class GetContactSupportNotificationsHandler
{
    private const int RECENT_LIMIT = 8;

    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    /**
     * @return array{unread_count: int, items: Collection<int, ContactSupportEloquentModel>}
     */
    public function handle(): array
    {
        return [
            'unread_count' => $this->contactSupports->countUnread(),
            'items' => $this->contactSupports->recent(self::RECENT_LIMIT),
        ];
    }
}
