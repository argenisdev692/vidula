<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Ports;

/**
 * OtpServicePort — Port for OTP generation, storage, and verification.
 *
 * Decouples domain from cache/notification infrastructure.
 */
interface OtpServicePort
{
    /**
     * Generate, store, and send an OTP to the given identifier.
     */
    public function send(string $identifier): void;

    /**
     * Verify an OTP code against the stored value.
     *
     * @return bool True if valid, false otherwise.
     */
    #[\NoDiscard]
    public function verify(string $identifier, string $code): bool;

    /**
     * Invalidate all pending OTPs for the given identifier.
     */
    public function invalidate(string $identifier): void;
}
