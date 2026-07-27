<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Commands;

use Modules\AiResumeStudio\Domain\Ports\JobSearchConfigRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;

final readonly class RunDailyResumeStudioHandler
{
    public function __construct(
        private JobSearchConfigRepositoryPort $configs,
        private StartStudioRunHandler $startRun,
    ) {}

    public function handle(): int
    {
        $count = 0;

        foreach ($this->configs->findScheduledEnabled() as $config) {
            if ($config instanceof JobSearchConfigEloquentModel) {
                $this->startRun->handleFromConfig($config);
                $count++;
            }
        }

        return $count;
    }
}
