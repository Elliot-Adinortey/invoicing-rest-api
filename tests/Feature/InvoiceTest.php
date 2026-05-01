<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const API_INVOICES = '/api/v1/invoices';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function actingAsInvoiceUser(): User
{
    $user = User::factory()->create();
    test()->actingAs($user, 'sanctum');

    return $user;
}

/**
 * Build a valid store payload with one line item.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function invoicePayload(array $overrides = []): array
{
    $product = Product::factory()->create(['stock_quantity' => 50, 'unit_price' => 100.00]);
    $customer = Customer::factory()->create();

    return array_merge([
        'customer_id' => $customer->id,
        'issue_date' => '2026-04-01',
        'due_date' => '2026-04-30',
        'items' => [
            [
                'product_id' => $product->id,
                'description' => 'Test item',
                'unit_price' => 100.00,
                'quantity' => 2,
            ],
        ],
    ], $overrides);
}

// ─── Index ────────────────────────────────────────────────────────────────────

describe('GET /invoices', function () {
    it('returns a paginated list of invoices', function () {
        $user = actingAsInvoiceUser();
        Invoice::factory()->count(3)->create(['user_id' => $user->id]);

        $this->getJson(API_INVOICES)
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'status',
                'status_code',
                'data' => [
                    'data' => [['id', 'invoice_number', 'status', 'issue_date', 'due_date', 'subtotal', 'total']],
                    'meta' => ['current_page', 'total'],
                ],
            ])
            ->assertJson(['success' => true, 'status_code' => 200]);
    });

    it('filters invoices by status', function () {
        $user = actingAsInvoiceUser();
        Invoice::factory()->create(['user_id' => $user->id, 'status' => 'issued']);
        Invoice::factory()->create(['user_id' => $user->id, 'status' => 'paid']);
        Invoice::factory()->create(['user_id' => $user->id, 'status' => 'paid']);

        $response = $this->getJson(API_INVOICES.'?status=paid')->assertStatus(200);

        expect($response->json('data.meta.total'))->toBe(2);

        collect($response->json('data.data'))->each(
            fn ($invoice) => expect($invoice['status'])->toBe('paid')
        );
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(API_INVOICES)->assertStatus(401);
    });
});

// ─── Store ────────────────────────────────────────────────────────────────────

describe('POST /invoices', function () {
    it('creates an invoice with items and returns 201', function () {
        $user = actingAsInvoiceUser();
        $payload = invoicePayload();

        $this->postJson(API_INVOICES, $payload)
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'invoice_number', 'status', 'subtotal', 'total',
                    'customer' => ['id', 'customer_name'],
                    'items' => [['id', 'quantity', 'unit_price', 'amount']],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Invoice created successfully.',
                'data' => [
                    'status' => 'issued',
                    'subtotal' => '200.00',
                    'total' => '200.00',
                ],
            ]);

        $this->assertDatabaseHas('invoices', ['user_id' => $user->id, 'status' => 'issued']);
        $this->assertDatabaseHas('invoice_items', ['quantity' => 2, 'amount' => 200.00]);
    });

    it('auto-generates the invoice_number server-side', function () {
        actingAsInvoiceUser();

        $response = $this->postJson(API_INVOICES, invoicePayload())->assertStatus(201);

        $invoiceNumber = $response->json('data.invoice_number');
        expect($invoiceNumber)->toStartWith('INV-');
        $this->assertDatabaseHas('invoices', ['invoice_number' => $invoiceNumber]);
    });

    it('decrements product stock when invoice is created', function () {
        actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 10, 'unit_price' => 50.00]);
        $customer = Customer::factory()->create();

        $this->postJson(API_INVOICES, [
            'customer_id' => $customer->id,
            'issue_date' => '2026-04-01',
            'due_date' => '2026-04-30',
            'items' => [
                ['product_id' => $product->id, 'unit_price' => 50.00, 'quantity' => 3],
            ],
        ])->assertStatus(201);

        expect($product->fresh()->stock_quantity)->toBe(7);
    });

    it('records a stock movement for each item', function () {
        actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 20, 'unit_price' => 75.00]);
        $customer = Customer::factory()->create();

        $this->postJson(API_INVOICES, [
            'customer_id' => $customer->id,
            'issue_date' => '2026-04-01',
            'due_date' => '2026-04-30',
            'items' => [
                ['product_id' => $product->id, 'unit_price' => 75.00, 'quantity' => 5],
            ],
        ])->assertStatus(201);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => 5,
            'stock_before' => 20,
            'stock_after' => 15,
        ]);
    });

    it('rejects an invoice when requested quantity exceeds available stock', function () {
        actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 2, 'unit_price' => 50.00]);
        $customer = Customer::factory()->create();

        $this->postJson(API_INVOICES, [
            'customer_id' => $customer->id,
            'issue_date' => '2026-04-01',
            'due_date' => '2026-04-30',
            'items' => [
                ['product_id' => $product->id, 'unit_price' => 50.00, 'quantity' => 10],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        // Stock must remain unchanged
        expect($product->fresh()->stock_quantity)->toBe(2);
    });

    it('returns 401 when unauthenticated', function () {
        $this->postJson(API_INVOICES, invoicePayload())->assertStatus(401);
    });

    it('fails when customer_id does not exist', function () {
        actingAsInvoiceUser();

        $this->postJson(API_INVOICES, invoicePayload(['customer_id' => fake()->uuid()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    });

    it('fails when due_date is before issue_date', function () {
        actingAsInvoiceUser();

        $this->postJson(API_INVOICES, invoicePayload([
            'issue_date' => '2026-04-30',
            'due_date' => '2026-04-01',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    });

    it('fails when items array is empty', function () {
        actingAsInvoiceUser();

        $this->postJson(API_INVOICES, invoicePayload(['items' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    });

    it('fails when an item product_id does not exist', function () {
        actingAsInvoiceUser();

        $payload = invoicePayload();
        $payload['items'][0]['product_id'] = fake()->uuid();

        $this->postJson(API_INVOICES, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    });

    it('fails when an item quantity is zero', function () {
        actingAsInvoiceUser();

        $payload = invoicePayload();
        $payload['items'][0]['quantity'] = 0;

        $this->postJson(API_INVOICES, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    });
});

// ─── Show ─────────────────────────────────────────────────────────────────────

describe('GET /invoices/{id}', function () {
    it('returns a single invoice with items and customer', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $this->getJson(API_INVOICES."/{$invoice->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'invoice_number', 'status', 'customer', 'items'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['id' => $invoice->id, 'invoice_number' => $invoice->invoice_number],
            ]);
    });

    it('returns 404 for an unknown invoice', function () {
        actingAsInvoiceUser();

        $this->getJson(API_INVOICES.'/non-existent-uuid')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $invoice = Invoice::factory()->create();

        $this->getJson(API_INVOICES."/{$invoice->id}")->assertStatus(401);
    });
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

describe('DELETE /invoices/{id}', function () {
    it('soft-deletes an invoice', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $this->deleteJson(API_INVOICES."/{$invoice->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Invoice deleted successfully.']);

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    });

    it('returns 404 when invoice does not exist', function () {
        actingAsInvoiceUser();

        $this->deleteJson(API_INVOICES.'/non-existent-uuid')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $invoice = Invoice::factory()->create();

        $this->deleteJson(API_INVOICES."/{$invoice->id}")->assertStatus(401);
    });
});

// ─── Mark as Paid ─────────────────────────────────────────────────────────────

describe('POST /invoices/{id}/mark-paid', function () {
    it('marks an issued invoice as paid', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'issued']);

        $this->postJson(API_INVOICES."/{$invoice->id}/mark-paid")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice marked as paid.',
                'data' => ['id' => $invoice->id, 'status' => 'paid'],
            ]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    });

    it('returns 404 for an unknown invoice', function () {
        actingAsInvoiceUser();

        $this->postJson(API_INVOICES.'/non-existent-uuid/mark-paid')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $invoice = Invoice::factory()->create();

        $this->postJson(API_INVOICES."/{$invoice->id}/mark-paid")->assertStatus(401);
    });
});
