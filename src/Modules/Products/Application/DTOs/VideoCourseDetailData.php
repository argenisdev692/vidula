<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Thin 1:1 detail carried inside {@see ProductData} when the product type is
 * `video_tutorial` or `video_pill`. Nested rules live on the parent DTO
 * (`video_course.*`).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class VideoCourseDetailData extends Data
{
    public function __construct(
        public ?string $platform = null,
        public ?string $playlistUrl = null,
        public int $totalVideos = 0,
        public ?int $totalDurationMinutes = null,
        public ?string $targetAudience = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'platform' => $this->platform,
            'playlist_url' => $this->playlistUrl,
            'total_videos' => $this->totalVideos,
            'total_duration_minutes' => $this->totalDurationMinutes,
            'target_audience' => $this->targetAudience,
        ];
    }
}
