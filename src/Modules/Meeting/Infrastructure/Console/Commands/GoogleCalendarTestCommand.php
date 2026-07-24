<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Spatie\GoogleCalendar\Event;

final class GoogleCalendarTestCommand extends Command
{
    protected $signature = 'google:calendar-test
        {--meet : Add a Google Meet link to the test event}
        {--keep : Do not delete the event after creation}';

    protected $description = 'Smoke-test Google Calendar connectivity (and optionally Meet).';

    public function handle(): int
    {
        if (! filled(config('google-calendar.calendar_id'))) {
            $this->error('GOOGLE_CALENDAR_ID is not configured.');

            return self::FAILURE;
        }

        try {
            $startsAt = CarbonImmutable::now()->addHour()->startOfHour();
            $endsAt = $startsAt->addHour();

            $event = new Event;
            $event->name = 'VIDULA calendar smoke test';
            $event->description = 'Created by google:calendar-test — safe to delete.';
            $event->startDateTime = $startsAt;
            $event->endDateTime = $endsAt;

            if ($this->option('meet')) {
                $event->addMeetLink();
            }

            $saved = $event->save();

            $this->info('Evento creado correctamente.');
            $this->line('Event ID: '.$saved->id);

            $meetLink = $saved->hangoutLink ?? null;
            if (is_string($meetLink) && $meetLink !== '') {
                $this->line('Meet OK:   '.$meetLink);
            } elseif ($this->option('meet')) {
                $this->warn('Meet link was not returned — verify OAuth profile and token.');
            }

            if (! $this->option('keep')) {
                $saved->delete();
                $this->line('Test event deleted.');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
