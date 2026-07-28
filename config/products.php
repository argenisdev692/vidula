<?php

declare(strict_types=1);

/*
|| Products (Module 4) — content-generation caps, default model and packaging.
|| Consumed by SeedOutlineParser, StartContentGenerationHandler, the generation
|| job and the ZIP package adapter.
||
|| The caps are a cost/DoS control as much as a data-shape one: a pasted index
|| is untrusted input that fans out into one LLM call per topic.
*/
return [
    'default_model' => env('PRODUCTS_AI_MODEL', 'gpt-4.1-mini'),
    'package_ttl_minutes' => (int) env('PRODUCTS_PACKAGE_TTL_MINUTES', 60),
    'generation_throttle_per_minute' => 5,

    'generation' => [
        'max_markdown_bytes' => 1_048_576,
        'max_sessions' => 200,
        'max_topics' => 2000,
    ],
];
