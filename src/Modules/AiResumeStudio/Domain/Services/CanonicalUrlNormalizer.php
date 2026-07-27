<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Services;

use Uri\Rfc3986\Uri;

/**
 * Normalizes job posting URLs for per-user deduplication.
 */
final readonly class CanonicalUrlNormalizer
{
    /** @var list<string> */
    private const array TRACKING_KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'ref',
        'fbclid',
        'gclid',
    ];

    #[\NoDiscard]
    public static function normalize(string $url): string
    {
        return $url
          |> trim(...)
          |> self::stripFragment(...)
          |> self::stripTrackingParams(...)
          |> self::recomposeNormalized(...)
          |> (fn (string $normalized): string => rtrim($normalized, '/'));
    }

    private static function stripFragment(string $url): string
    {
        $uri = Uri::parse($url);

        if ($uri === null) {
            return $url;
        }

        return $uri->withFragment(null)->toString();
    }

    private static function stripTrackingParams(string $url): string
    {
        $uri = Uri::parse($url);

        if ($uri === null) {
            return $url;
        }

        $query = $uri->getQuery();

        if ($query === null || $query === '') {
            return $uri->toString();
        }

        parse_str($query, $params);

        foreach (self::TRACKING_KEYS as $key) {
            unset($params[$key]);
        }

        return $uri
            ->withQuery($params !== [] ? http_build_query($params) : null)
            ->toString();
    }

    private static function recomposeNormalized(string $url): string
    {
        $uri = Uri::parse($url);

        return $uri?->toString() ?? $url;
    }
}
