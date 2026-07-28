<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Services;

use Shared\Domain\Ports\DocsVerificationPort;

/**
 * Decides WHICH libraries a topic is actually about, so the pipeline only
 * spends {@see DocsVerificationPort} lookups on topics that have official
 * docs to verify against.
 *
 * Deliberately a curated alias table rather than an LLM call or a
 * capitalised-word heuristic: the catalog covers a known technology surface
 * (AI dev tooling + web stacks), and a wrong guess costs a wasted HTTP
 * round-trip plus irrelevant snippets inside the prompt.
 */
final readonly class LibraryNameDetector
{
    private const int MAX_LIBRARIES_PER_TOPIC = 3;

    /**
     * Alias (lowercase, matched as a whole word) => canonical library name
     * sent to Context7.
     *
     * @var array<string, string>
     */
    private const array ALIASES = [
        'github copilot' => 'github copilot',
        'copilot' => 'github copilot',
        'microsoft 365 copilot' => 'microsoft 365 copilot',
        'ms365 copilot' => 'microsoft 365 copilot',
        'claude code' => 'claude code',
        'claude' => 'claude',
        'anthropic' => 'claude',
        'chatgpt' => 'openai',
        'openai' => 'openai',
        'gemini' => 'gemini',
        'cursor' => 'cursor',
        'tabnine' => 'tabnine',
        'langchain' => 'langchain',
        'mcp' => 'model context protocol',
        'model context protocol' => 'model context protocol',
        '.net' => 'dotnet',
        'dotnet' => 'dotnet',
        'asp.net' => 'asp.net core',
        'entity framework' => 'entity framework core',
        'c#' => 'csharp',
        'angular' => 'angular',
        'react' => 'react',
        'next.js' => 'next.js',
        'nextjs' => 'next.js',
        'vue' => 'vue',
        'nuxt' => 'nuxt',
        'node' => 'node.js',
        'node.js' => 'node.js',
        'nestjs' => 'nestjs',
        'express' => 'express',
        'typescript' => 'typescript',
        'javascript' => 'javascript',
        'laravel' => 'laravel',
        'php' => 'php',
        'symfony' => 'symfony',
        'python' => 'python',
        'django' => 'django',
        'fastapi' => 'fastapi',
        'tailwind' => 'tailwind css',
        'docker' => 'docker',
        'kubernetes' => 'kubernetes',
        'terraform' => 'terraform',
        'azure devops' => 'azure devops',
        'azure' => 'azure',
        'aws' => 'aws',
        'github actions' => 'github actions',
        'git' => 'git',
        'postgresql' => 'postgresql',
        'mysql' => 'mysql',
        'mongodb' => 'mongodb',
        'redis' => 'redis',
        'graphql' => 'graphql',
        'playwright' => 'playwright',
        'cypress' => 'cypress',
        'jest' => 'jest',
        'vitest' => 'vitest',
        'phpunit' => 'phpunit',
    ];

    /**
     * Longest alias first so "github copilot" wins over "copilot" and the
     * canonical name is not added twice.
     *
     * @return list<string>
     */
    #[\NoDiscard]
    public function detect(string ...$texts): array
    {
        $haystack = mb_strtolower(implode(' ', $texts));

        if (trim($haystack) === '') {
            return [];
        }

        $aliases = self::ALIASES;
        uksort($aliases, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $found = [];

        foreach ($aliases as $alias => $canonical) {
            if (in_array($canonical, $found, true)) {
                continue;
            }

            if (preg_match('/(?<![\w.#+])'.preg_quote($alias, '/').'(?![\w+])/u', $haystack) === 1) {
                $found[] = $canonical;
            }

            if (count($found) === self::MAX_LIBRARIES_PER_TOPIC) {
                break;
            }
        }

        return $found;
    }
}
