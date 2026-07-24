<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\GoogleCalendar;

use Google\Client;
use Google\Service\Calendar;
use RuntimeException;

/**
 * OAuth2 token management for the shared Google Calendar account used by
 * `spatie/laravel-google-calendar` when `GOOGLE_CALENDAR_AUTH_PROFILE=oauth`.
 */
final readonly class GoogleCalendarOAuthService
{
    public function authorizationUrl(): string
    {
        return $this->buildClient()->createAuthUrl();
    }

    public function storeTokenFromCode(string $code): void
    {
        $client = $this->buildClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException((string) ($token['error_description'] ?? $token['error']));
        }

        $this->writeToken($token);
    }

    /**
     * @param  array<string, mixed>  $token
     */
    public function writeToken(array $token): void
    {
        $path = $this->tokenPath();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($token, JSON_THROW_ON_ERROR));
    }

    public function tokenExists(): bool
    {
        return is_file($this->tokenPath());
    }

    private function buildClient(): Client
    {
        $profile = config('google-calendar.auth_profiles.oauth');
        $client = new Client;
        $client->setAuthConfig($profile['credentials_json']);
        $client->setRedirectUri((string) config('google-calendar.oauth_redirect_uri'));
        $client->setScopes([Calendar::CALENDAR]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($this->tokenExists()) {
            /** @var array<string, mixed> $token */
            $token = json_decode((string) file_get_contents($this->tokenPath()), true, 512, JSON_THROW_ON_ERROR);
            $client->setAccessToken($token);
        }

        return $client;
    }

    private function tokenPath(): string
    {
        return (string) config('google-calendar.auth_profiles.oauth.token_json');
    }
}
