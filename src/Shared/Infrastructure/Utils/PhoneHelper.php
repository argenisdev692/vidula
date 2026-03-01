<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Utils;

/**
 * Format phone numbers to USA standard: (XXX) XXX-XXXX
 */
class PhoneHelper
{
    /**
     * Format a phone number to (XXX) XXX-XXXX.
     * Accepts raw digits, +1 prefixed, or already formatted numbers.
     */
    public static function format(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        // Remove everything except digits
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        // 10-digit US number
        if (strlen($digits) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 4),
            );
        }

        // 11-digit with leading country code "1"
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 4),
            );
        }

        // Unrecognized length — return as-is
        return $phone;
    }
}