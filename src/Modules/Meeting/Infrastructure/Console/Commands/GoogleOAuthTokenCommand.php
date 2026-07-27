<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Modules\Meeting\Infrastructure\GoogleCalendar\GoogleCalendarOAuthService;
use Uri\Rfc3986\Uri;

final class GoogleOAuthTokenCommand extends Command
{
    protected $signature = 'google:oauth-token
        {--manual : Print the auth URL and prompt for the redirect URL manually}';

    protected $description = 'Generate or refresh the Google Calendar OAuth token file.';

    public function handle(GoogleCalendarOAuthService $oauth): int
    {
        if (! is_file((string) config('google-calendar.auth_profiles.oauth.credentials_json'))) {
            $this->error('Missing oauth-credentials.json — see docs/README-google-meet.md');

            return self::FAILURE;
        }

        if (! $this->option('manual')) {
            $this->line('Open this URL in your browser (while logged into the calendar account):');
            $this->line($oauth->authorizationUrl());
            $this->newLine();
            $this->line('After authorizing, Google redirects to your configured redirect URI.');
            $this->line('Copy the full redirect URL (including ?code=...) and run:');
            $this->line('  ./vendor/bin/sail artisan google:oauth-token --manual');

            return self::SUCCESS;
        }

        $redirectUrl = (string) $this->ask('Paste the full redirect URL');
        $uri = Uri::parse($redirectUrl);
        $queryString = $uri?->getQuery() ?? '';

        if ($queryString === '') {
            $this->error('No authorization code found in that URL.');

            return self::FAILURE;
        }

        parse_str($queryString, $query);

        if (! isset($query['code']) || ! is_string($query['code'])) {
            $this->error('No authorization code found in that URL.');

            return self::FAILURE;
        }

        try {
            $oauth->storeTokenFromCode($query['code']);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('oauth-token.json written successfully.');

        return self::SUCCESS;
    }
}
