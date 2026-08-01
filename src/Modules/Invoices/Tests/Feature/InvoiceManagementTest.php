<?php

declare(strict_types=1);

namespace Modules\Invoices\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceItemEloquentModel;
use Modules\Invoices\Infrastructure\Queue\GenerateInvoicePdfJob;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Tests\TestCase;

final class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(ClientEloquentModel $client, array $overrides = []): array
    {
        return [
            'client_uuid' => $client->uuid,
            'invoice_number' => '001/2026',
            'issue_date' => '2026-03-10',
            'due_date' => '2026-03-15',
            'currency' => 'USD',
            'tax_mode' => 'EXEMPT',
            'tax_rate' => null,
            'tax_label' => 'IVA',
            'is_paid' => false,
            'payment_method' => null,
            'transfer_number' => null,
            'payment_date' => null,
            'amount_received' => null,
            'notes' => 'VAT - Reverse Charge: International transaction exempt from VAT.',
            'items' => [
                [
                    'title' => 'Website development',
                    'description' => 'Corporate website',
                    'quantity' => 1,
                    'unit_price' => 150,
                    'service_uuid' => null,
                    'sort_order' => 0,
                ],
                [
                    'title' => 'Logo redesign',
                    'description' => null,
                    'quantity' => 1,
                    'unit_price' => 50,
                    'service_uuid' => null,
                    'sort_order' => 1,
                ],
            ],
            ...$overrides,
        ];
    }

    public function test_super_admin_creates_invoice_with_computed_totals(): void
    {
        Queue::fake();

        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create([
            'client_name' => 'AquaShield',
            'tax_id' => '36-5164436',
        ]);

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client))
            ->assertRedirect();

        $invoice = InvoiceEloquentModel::query()->where('invoice_number', '001/2026')->firstOrFail();

        $this->assertSame($admin->id, $invoice->user_id);
        $this->assertSame($client->id, $invoice->client_id);
        $this->assertSame('AquaShield', $invoice->client_name);
        $this->assertSame('36-5164436', $invoice->client_tax_id);
        $this->assertSame(1, $invoice->sequence);
        $this->assertSame(2026, $invoice->year);
        $this->assertSame('EXEMPT', $invoice->tax_mode);
        $this->assertSame('200.00', $invoice->subtotal);
        $this->assertSame('0.00', $invoice->tax_amount);
        $this->assertSame('200.00', $invoice->total);
        $this->assertCount(2, $invoice->items);

        Queue::assertPushed(GenerateInvoicePdfJob::class);
    }

    public function test_zero_percent_tax_keeps_total_equal_to_subtotal(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client, [
                'invoice_number' => '010/2026',
                'tax_mode' => 'PERCENT',
                'tax_rate' => 0,
                'items' => [
                    [
                        'title' => 'Consulting',
                        'description' => null,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'service_uuid' => null,
                        'sort_order' => 0,
                    ],
                ],
            ]))
            ->assertRedirect();

        $invoice = InvoiceEloquentModel::query()->where('invoice_number', '010/2026')->firstOrFail();
        $this->assertSame('PERCENT', $invoice->tax_mode);
        $this->assertSame('0.0000', $invoice->tax_rate);
        $this->assertSame('100.00', $invoice->subtotal);
        $this->assertSame('0.00', $invoice->tax_amount);
        $this->assertSame('100.00', $invoice->total);
    }

    public function test_paid_invoice_stores_payment_details(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client, [
                'invoice_number' => '011/2026',
                'is_paid' => true,
                'payment_method' => 'Remitly (International Transfer)',
                'transfer_number' => 'R20 386 959 937',
                'payment_date' => '2026-05-02',
                'amount_received' => 25,
                'items' => [
                    [
                        'title' => 'Fix',
                        'description' => null,
                        'quantity' => 1,
                        'unit_price' => 25,
                        'service_uuid' => null,
                        'sort_order' => 0,
                    ],
                ],
            ]))
            ->assertRedirect();

        $invoice = InvoiceEloquentModel::query()->where('invoice_number', '011/2026')->firstOrFail();
        $this->assertTrue($invoice->is_paid);
        $this->assertSame('Remitly (International Transfer)', $invoice->payment_method);
        $this->assertSame('R20 386 959 937', $invoice->transfer_number);
        $this->assertSame('2026-05-02', $invoice->payment_date?->toDateString());
        $this->assertSame('25.00', $invoice->amount_received);
    }

    public function test_paid_invoice_requires_payment_fields(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client, [
                'invoice_number' => '012/2026',
                'is_paid' => true,
                'payment_method' => null,
                'payment_date' => null,
                'amount_received' => null,
            ]))
            ->assertSessionHasErrors(['payment_method', 'payment_date', 'amount_received']);
    }

    public function test_percent_tax_is_applied_to_total(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client, [
                'invoice_number' => '002/2026',
                'tax_mode' => 'PERCENT',
                'tax_rate' => 23,
                'items' => [
                    [
                        'title' => 'Consulting',
                        'description' => null,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'service_uuid' => null,
                        'sort_order' => 0,
                    ],
                ],
            ]))
            ->assertRedirect();

        $invoice = InvoiceEloquentModel::query()->where('invoice_number', '002/2026')->firstOrFail();
        $this->assertSame('100.00', $invoice->subtotal);
        $this->assertSame('23.00', $invoice->tax_amount);
        $this->assertSame('123.00', $invoice->total);
    }

    public function test_next_invoice_number_suggests_sequence_after_last(): void
    {
        $admin = $this->superAdmin();
        InvoiceEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'invoice_number' => '007/2026',
            'sequence' => 7,
            'year' => 2026,
        ]);

        $this->actingAs($admin)
            ->getJson('/invoices/next-number?year=2026')
            ->assertOk()
            ->assertJson([
                'invoice_number' => '008/2026',
                'sequence' => 8,
                'year' => 2026,
            ]);
    }

    public function test_duplicate_invoice_number_is_rejected(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();
        InvoiceEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'client_id' => $client->id,
            'invoice_number' => '001/2026',
            'sequence' => 1,
            'year' => 2026,
        ]);

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client))
            ->assertSessionHasErrors('invoice_number');
    }

    public function test_update_recomputes_totals_and_replaces_items(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();
        $invoice = InvoiceEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'client_id' => $client->id,
            'invoice_number' => '003/2026',
            'sequence' => 3,
            'year' => 2026,
            'subtotal' => 10,
            'tax_amount' => 0,
            'total' => 10,
        ]);
        InvoiceItemEloquentModel::query()->create([
            'invoice_id' => $invoice->id,
            'sort_order' => 0,
            'title' => 'Old',
            'quantity' => 1,
            'unit_price' => 10,
            'amount' => 10,
        ]);

        $this->actingAs($admin)
            ->put("/invoices/{$invoice->uuid}", $this->validPayload($client, [
                'invoice_number' => '003/2026',
                'items' => [
                    [
                        'title' => 'New line',
                        'description' => 'Updated',
                        'quantity' => 2,
                        'unit_price' => 40,
                        'service_uuid' => null,
                        'sort_order' => 0,
                    ],
                ],
            ]))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame('80.00', $invoice->subtotal);
        $this->assertSame('80.00', $invoice->total);
        $this->assertCount(1, $invoice->items);
        $this->assertSame('New line', $invoice->items->first()->title);
    }

    public function test_pdf_download_returns_pdf_response(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();
        $invoice = InvoiceEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'client_id' => $client->id,
            'invoice_number' => '004/2026',
            'sequence' => 4,
            'year' => 2026,
        ]);
        InvoiceItemEloquentModel::query()->create([
            'invoice_id' => $invoice->id,
            'sort_order' => 0,
            'title' => 'Service',
            'quantity' => 1,
            'unit_price' => 100,
            'amount' => 100,
        ]);

        $response = $this->actingAs($admin)
            ->get("/invoices/{$invoice->uuid}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_pdf_download_filename_includes_client_sequence_and_issue_date(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();
        $invoice = InvoiceEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'client_id' => $client->id,
            'client_name' => 'Aquashield Restoration LLC',
            'invoice_number' => '015/2026',
            'sequence' => 15,
            'year' => 2026,
            'issue_date' => '2026-08-01',
        ]);
        InvoiceItemEloquentModel::query()->create([
            'invoice_id' => $invoice->id,
            'sort_order' => 0,
            'title' => 'Service',
            'quantity' => 1,
            'unit_price' => 100,
            'amount' => 100,
        ]);

        $this->actingAs($admin)
            ->get("/invoices/{$invoice->uuid}/pdf")
            ->assertOk()
            ->assertHeader(
                'content-disposition',
                'attachment; filename="Invoice-Aquashield-Restoration-LLC-015-01-08-2026.pdf"',
            );
    }

    public function test_delete_then_restore_invoice(): void
    {
        $admin = $this->superAdmin();
        $invoice = InvoiceEloquentModel::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->delete("/invoices/{$invoice->uuid}")->assertRedirect();
        $this->assertSoftDeleted('invoices', ['uuid' => $invoice->uuid]);

        $this->actingAs($admin)->post("/invoices/{$invoice->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('invoices', ['uuid' => $invoice->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = InvoiceEloquentModel::factory()->count(3)->create(['user_id' => $admin->id])->pluck('uuid')->all();

        $this->actingAs($admin)->post('/invoices/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('invoices', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/invoices/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('invoices', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_service_uuid_is_linked_on_line_item(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();
        $service = ServiceEloquentModel::factory()->create([
            'name' => 'Web Development',
            'description' => 'Remote web services',
            'is_active' => true,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client, [
                'invoice_number' => '005/2026',
                'items' => [
                    [
                        'title' => 'Web Development',
                        'description' => 'Remote web services',
                        'quantity' => 1,
                        'unit_price' => 200,
                        'service_uuid' => $service->uuid,
                        'sort_order' => 0,
                    ],
                ],
            ]))
            ->assertRedirect();

        $invoice = InvoiceEloquentModel::query()->where('invoice_number', '005/2026')->firstOrFail();
        $this->assertSame($service->id, $invoice->items->first()->service_id);
    }

    public function test_product_uuid_is_linked_on_invoice(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->active()->create();
        $product = ProductEloquentModel::factory()
            ->classroom()
            ->create([
                'title' => 'Copilot Classroom',
                'price' => 1200,
                'user_id' => $admin->id,
            ]);

        $this->actingAs($admin)
            ->post('/invoices', $this->validPayload($client, [
                'invoice_number' => '006/2026',
                'product_uuid' => $product->uuid,
                'items' => [
                    [
                        'title' => 'Copilot Classroom',
                        'description' => 'Classroom delivery',
                        'quantity' => 1,
                        'unit_price' => 1200,
                        'service_uuid' => null,
                        'sort_order' => 0,
                    ],
                ],
            ]))
            ->assertRedirect();

        $invoice = InvoiceEloquentModel::query()->where('invoice_number', '006/2026')->firstOrFail();
        $this->assertSame($product->id, $invoice->product_id);
    }

    public function test_user_role_cannot_create_invoices(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        $client = ClientEloquentModel::factory()->active()->create();

        $this->actingAs($user)
            ->post('/invoices', $this->validPayload($client))
            ->assertForbidden();
    }

    public function test_admin_role_can_view_invoices_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)->get('/invoices')->assertOk();
    }

    public function test_index_page_renders_for_authorized_user(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/invoices')
            ->assertOk();
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/invoices/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_export_csv_streams_successfully(): void
    {
        $admin = $this->superAdmin();
        InvoiceEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'invoice_number' => '099/2026',
            'sequence' => 99,
            'year' => 2026,
        ]);

        $this->actingAs($admin)
            ->get('/invoices/export?format=csv')
            ->assertOk();
    }

    public function test_export_rejects_invalid_format(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/invoices/export?format=docx')
            ->assertStatus(422);
    }
}
