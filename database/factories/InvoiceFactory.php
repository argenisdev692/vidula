<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;

/**
 * @extends Factory<InvoiceEloquentModel>
 */
final class InvoiceFactory extends Factory
{
    /**
     * @var class-string<InvoiceEloquentModel>
     */
    protected $model = InvoiceEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->year;
        $sequence = $this->faker->numberBetween(1, 9000) + ((int) (microtime(true) * 1000) % 99);

        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'client_id' => ClientEloquentModel::factory(),
            'invoice_number' => sprintf('%03d/%d', $sequence, $year),
            'sequence' => $sequence,
            'year' => $year,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'currency' => 'USD',
            'tax_mode' => 'EXEMPT',
            'tax_rate' => 0,
            'tax_label' => 'IVA',
            'subtotal' => 100.00,
            'tax_amount' => 0.00,
            'total' => 100.00,
            'is_paid' => false,
            'payment_method' => null,
            'transfer_number' => null,
            'payment_date' => null,
            'amount_received' => null,
            'notes' => null,
            'additional_notes' => null,
        ];
    }
}
