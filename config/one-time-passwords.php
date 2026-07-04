<?php

use Modules\Auth\Infrastructure\Notifications\QueuedOneTimePasswordNotification;
use Spatie\OneTimePasswords\Actions\ConsumeOneTimePasswordAction;
use Spatie\OneTimePasswords\Actions\CreateOneTimePasswordAction;
use Spatie\OneTimePasswords\Models\OneTimePassword;
use Spatie\OneTimePasswords\Support\OriginInspector\DefaultOriginEnforcer;
use Spatie\OneTimePasswords\Support\PasswordGenerators\NumericOneTimePasswordGenerator;

return [
    /*
     * Default validity (minutes) for a one-time password. This is the SHORT
     * window used by passwordless LOGIN codes. Activation and password-reset
     * codes override it per-call with the longer security.otp.email_minutes.
     */
    'default_expires_in_minutes' => (int) env('AUTH_OTP_EXPIRES_MINUTES', 2),

    /*
     * When this setting is active, we'll delete all previous one-time passwords for
     * a user when generating a new one
     */
    'only_one_active_one_time_password_per_user' => true,

    /*
     * When this option is active, we'll try to ensure that the one-time password can only
     * be consumed on the platform where it was requested on
     */
    'enforce_same_origin' => true,

    /*
     * This class is responsible to enforce that the one-time password can only be consumed on
     * the platform it was requested on.
     *
     * If you do not wish to enforce this, set this value to
     * Spatie\OneTimePasswords\Support\OriginInspector\DoNotEnforceOrigin
     */
    'origin_enforcer' => DefaultOriginEnforcer::class,

    /*
     * This class generates a random password
     */
    'password_generator' => NumericOneTimePasswordGenerator::class,

    /*
     * By default, the password generator will create a password with
     * this number of digits (numeric 0-9, per AUTH_OTP_VALID_CHARS).
     */
    'password_length' => (int) env('AUTH_OTP_LENGTH', 6),

    /*
     * The Livewire component will redirect successfully authenticated users
     * to this URL.
     */
    'redirect_successful_authentication_to' => '/dashboard',

    /*
     * These values are used to rate limit the number of attempts
     * that may be made to consume a one-time password.
     */
    'rate_limit_attempts' => [
        'max_attempts_per_user' => (int) env('AUTH_OTP_MAX_ATTEMPTS', 3),
        'time_window_in_seconds' => (int) env('AUTH_OTP_RESEND_INTERVAL_SECONDS', 60),
    ],

    /*
     * The model uses to store one-time passwords
     */
    'model' => OneTimePassword::class,

    /*
     * The notification used to send a one-time password to a user. Uses the
     * queued subclass so OTP mail (login / activation / reset) is dispatched to
     * the Redis queue instead of blocking the request.
     */
    'notification' => QueuedOneTimePasswordNotification::class,

    /*
     * These class are responsible for performing core tasks regarding one-time passwords.
     * You can customize them by creating a class that extends the default, and
     * by specifying your custom class name here.
     */
    'actions' => [
        'create_one_time_password' => CreateOneTimePasswordAction::class,
        'consume_one_time_password' => ConsumeOneTimePasswordAction::class,
    ],
];
