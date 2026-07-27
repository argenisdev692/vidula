<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

interface JobPageScraperPort
{
    /**
     * @return array{markdown: string|null, title: string|null}
     */
    public function scrape(string $url): array;
}
