<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Modules\AiResumeStudio\Application\Commands\RunDailyResumeStudioHandler;

final class RunDailyResumeStudioCommand extends Command
{
    protected $signature = 'resume-studio:run-daily';

    protected $description = 'Dispatch studio runs for job-search configs with schedule_enabled=true.';

    public function handle(RunDailyResumeStudioHandler $handler): int
    {
        $started = $handler->handle();

        $this->info("Started {$started} scheduled studio run(s).");

        return self::SUCCESS;
    }
}
