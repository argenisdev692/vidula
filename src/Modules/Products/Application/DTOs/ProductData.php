<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Domain\Enums\ProductModality;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Enums\VideoPlatform;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Fused create/update DTO for a billable product (Store/Update share the same
 * fields). The catalog `status` is the domain lifecycle and is distinct from
 * the soft-delete tombstone the list filters expose as active|suspended.
 *
 * The 1:1 detail is type-driven: `classroom` products carry `classroom.*`,
 * video products carry `video_course.*`. Only the block matching `type` is
 * persisted — the other is ignored by the handlers.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ProductData extends Data
{
    public function __construct(
        public string $type,
        public string $title,
        public float $price = 0.0,
        public string $currency = 'EUR',
        public string $status = 'draft',
        public string $level = 'beginner',
        public string $language = 'es',
        public ?string $description = null,
        public ?string $thumbnail = null,
        public ?string $clientUuid = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?float $totalHours = null,
        public ?int $totalSessions = null,
        public ?string $modality = null,
        public ?string $notes = null,
        public ?ClassroomDetailData $classroom = null,
        public ?VideoCourseDetailData $videoCourse = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.self::enumValues(ProductType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'status' => ['required', 'string', 'in:'.self::enumValues(ProductStatus::class)],
            'thumbnail' => ['nullable', 'string', 'max:2048', 'url'],
            'level' => ['required', 'string', 'max:50'],
            'language' => ['required', 'string', 'max:10'],
            'client_uuid' => ['nullable', 'uuid', 'exists:clients,uuid'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'total_sessions' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'modality' => ['nullable', 'string', 'in:'.self::enumValues(ProductModality::class)],
            'notes' => ['nullable', 'string', 'max:20000'],

            'classroom' => ['nullable', 'array'],
            'classroom.max_students' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'classroom.meet_url' => ['nullable', 'string', 'max:2048', 'url'],
            'classroom.objectives' => ['nullable', 'string', 'max:20000'],
            'classroom.requirements' => ['nullable', 'string', 'max:20000'],

            'video_course' => ['nullable', 'array'],
            'video_course.platform' => ['nullable', 'string', 'in:'.self::enumValues(VideoPlatform::class)],
            'video_course.playlist_url' => ['nullable', 'string', 'max:2048', 'url'],
            'video_course.total_videos' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'video_course.total_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'video_course.target_audience' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function productType(): ProductType
    {
        return ProductType::from($this->type);
    }

    /**
     * Everything that maps straight onto the `products` row. `slug`, `user_id`
     * and `client_id` are resolved by the handlers, not by the DTO.
     *
     * @return array<string, mixed>
     */
    public function toProductAttributes(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => strtoupper($this->currency),
            'status' => $this->status,
            'thumbnail' => $this->thumbnail,
            'level' => $this->level,
            'language' => $this->language,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'total_hours' => $this->totalHours,
            'total_sessions' => $this->totalSessions,
            'modality' => $this->modality,
            'notes' => $this->notes,
        ];
    }

    /**
     * Detail attributes for the 1:1 row implied by `type`, or null when the
     * type has no detail block (or the client sent none).
     *
     * @return array<string, mixed>|null
     */
    public function toDetailAttributes(): ?array
    {
        return $this->productType()->isVideo()
            ? ($this->videoCourse ?? new VideoCourseDetailData)->toAttributes()
            : $this->classroom?->toAttributes();
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     */
    private static function enumValues(string $enum): string
    {
        return implode(',', array_map(
            static fn (\BackedEnum $case): string => (string) $case->value,
            $enum::cases(),
        ));
    }
}
