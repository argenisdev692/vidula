<?php

declare(strict_types=1);

namespace Modules\Products\Application\Services;

/**
 * Slug normalisation shared by the create/update handlers.
 *
 * Kept as a tiny static helper (rather than an injected service) so it can sit
 * inside the `|>` pipeline that turns a raw title into a unique slug.
 */
final readonly class ProductSlugFactory
{
    public const int MAX_SUFFIX_ATTEMPTS = 50;

    private const string BLANK_FALLBACK = 'product';

    /**
     * `Str::slug()` returns an empty string for titles made only of punctuation
     * or non-transliterable glyphs, which would break the unique index.
     */
    #[\NoDiscard]
    public static function fallbackWhenBlank(string $slug): string
    {
        return $slug === '' ? self::BLANK_FALLBACK : $slug;
    }
}
