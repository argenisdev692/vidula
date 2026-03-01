<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Audit;

use Spatie\Activitylog\Facades\Activity;

final class SpatieAuditAdapter implements AuditInterface
{
    public function log(string $logName, string $description, array $properties = [], mixed $subject = null): void
    {
        $logger = activity($logName)
            ->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}
