<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Cvs\Domain\Enums\CvFileType;
use Modules\Cvs\Domain\Enums\CvNiche;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;

/**
 * @extends Factory<CvEloquentModel>
 */
final class CvFactory extends Factory
{
    /**
     * @var class-string<CvEloquentModel>
     */
    protected $model = CvEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'title' => 'Fullstack CV '.$this->faker->year(),
            'niche' => CvNiche::Fullstack->value,
            'is_primary' => false,
            'file_path' => 'cvs/'.$this->faker->uuid().'.md',
            'file_type' => CvFileType::Md->value,
            'original_filename' => 'resume.md',
            'raw_text' => "# Resume\n\nFull stack developer experience.",
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => ['is_primary' => true]);
    }

    public function otherNiche(): static
    {
        return $this->state(fn (): array => [
            'niche' => CvNiche::Other->value,
            'title' => 'Other niche CV',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (): array => [
            'file_type' => CvFileType::Pdf->value,
            'file_path' => 'cvs/'.$this->faker->uuid().'.pdf',
            'original_filename' => 'resume.pdf',
            'raw_text' => null,
        ]);
    }
}
