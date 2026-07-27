<?php

declare(strict_types=1);

/*
| AiResumeStudio (Module 2) — schedule, scraping limits, and integration keys.
| Consumed by ProcessStudioRunJob, RunDailyResumeStudioCommand, and adapters.
*/
return [
    'schedule' => [
        'time' => (string) env('CV_STUDIO_SCHEDULE_TIME', '09:00'),
        'timezone' => (string) env('CV_STUDIO_SCHEDULE_TIMEZONE', 'Europe/Lisbon'),
    ],

    'deep_extract_top_n' => (int) env('CV_STUDIO_DEEP_EXTRACT_TOP_N', 3),

    'tavily' => [
        'query_template' => (string) env(
            'CV_STUDIO_TAVILY_QUERY_TEMPLATE',
            '{keywords} job openings hiring',
        ),
    ],

    'firecrawl' => [
        'api_key' => (string) env('FIRECRAWL_API_KEY', ''),
        'base_url' => (string) env('FIRECRAWL_BASE_URL', 'https://api.firecrawl.dev/v1'),
        'timeout_seconds' => (int) env('FIRECRAWL_TIMEOUT_SECONDS', 30),
    ],

    'github' => [
        'token' => (string) env('GITHUB_TOKEN', ''),
        'api_url' => (string) env('GITHUB_API_URL', 'https://api.github.com'),
        'timeout_seconds' => (int) env('GITHUB_API_TIMEOUT_SECONDS', 15),
    ],
];
