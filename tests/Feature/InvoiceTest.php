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

    it('filters invoices by customer_id', function () {
        $user = actingAsInvoiceUser();
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();
        Invoice::factory()->count(2)->create(['user_id' => $user->id, 'customer_id' => $customerA->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'customer_id' => $customerB->id]);

        $response = $this->getJson(API_INVOICES."?customer_id={$customerA->id}")->assertStatus(200);

        expect($response->json('data.meta.total'))->toBe(2);
        collect($response->json('data.data'))->each(
            fn ($invoice) => expect($invoice['customer']['id'])->toBe($customerA->id)
        );
    });

    it('filters invoices by invoice number search', function () {
        $user = actingAsInvoiceUser();
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_number' => 'INV-20260501-0001']);
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_number' => 'INV-20260601-0001']);

        $response = $this->getJson(API_INVOICES.'?search=20260501')->assertStatus(200);

        expect($response->json('data.meta.total'))->toBe(1);
        expect($response->json('data.data.0.invoice_number'))->toBe('INV-20260501-0001');
    });

    it('filters invoices by issue_date range', function () {
        $user = actingAsInvoiceUser();
        Invoice::factory()->create(['user_id' => $user->id, 'issue_date' => '2026-01-15']);
        Invoice::factory()->create(['user_id' => $user->id, 'issue_date' => '2026-03-10']);
        Invoice::factory()->create(['user_id' => $user->id, 'issue_date' => '2026-06-01']);

        $response = $this->getJson(API_INVOICES.'?issue_date_from=2026-02-01&issue_date_to=2026-04-30')
            ->assertStatus(200);

        expect($response->json('data.meta.total'))->toBe(1);
        expect($response->json('data.data.0.issue_date'))->toStartWith('2026-03-10');
    });

    it('filters invoices by due_date range', function () {
        $user = actingAsInvoiceUser();
        Invoice::factory()->create(['user_id' => $user->id, 'due_date' => '2026-02-28']);
        Invoice::factory()->create(['user_id' => $user->id, 'due_date' => '2026-05-15']);

        $response = $this->getJson(API_INVOICES.'?due_date_from=2026-05-01')->assertStatus(200);

        expect($response->json('data.meta.total'))->toBe(1);
        expect($response->json('data.data.0.due_date'))->toStartWith('2026-05-15');
    });

    it('respects per_page parameter', function () {
        $user = actingAsInvoiceUser();
        Invoice::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->getJson(API_INVOICES.'?per_page=2')->assertStatus(200);

        expect($response->json('data.data'))->toHaveCount(2);
        expect($response->json('data.meta.per_page'))->toBe(2);
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

    it('creates a draft invoice without deducting stock', function () {
        actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 10, 'unit_price' => 50.00]);
        $customer = Customer::factory()->create();

        $this->postJson(API_INVOICES, [
            'customer_id' => $customer->id,
            'issue_date' => '2026-04-01',
            'due_date' => '2026-04-30',
            'status' => 'draft',
            'items' => [
                ['product_id' => $product->id, 'unit_price' => 50.00, 'quantity' => 3],
            ],
        ])
            ->assertStatus(201)
            ->assertJson(['data' => ['status' => 'draft']]);

        // Stock must not be touched for a draft
        expect($product->fresh()->stock_quantity)->toBe(10);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id]);
    });

    it('defaults to issued when status is omitted', function () {
        actingAsInvoiceUser();

        $response = $this->postJson(API_INVOICES, invoicePayload())->assertStatus(201);

        expect($response->json('data.status'))->toBe('issued');
    });

    it('rejects an invalid status value', function () {
        actingAsInvoiceUser();

        $this->postJson(API_INVOICES, invoicePayload(['status' => 'paid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
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

// ─── Update (draft only) ──────────────────────────────────────────────────────

describe('PUT /invoices/{id}', function () {
    it('updates a draft invoice fields', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $newCustomer = Customer::factory()->create();

        $this->putJson(API_INVOICES."/{$invoice->id}", [
            'customer_id' => $newCustomer->id,
            'due_date' => '2026-12-31',
        ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice updated successfully.',
                'data' => ['customer' => ['id' => $newCustomer->id]],
            ]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'customer_id' => $newCustomer->id]);
    });

    it('replaces line items when items are provided', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $newProduct = Product::factory()->create(['stock_quantity' => 20, 'unit_price' => 30.00]);

        $this->putJson(API_INVOICES."/{$invoice->id}", [
            'items' => [
                ['product_id' => $newProduct->id, 'unit_price' => 30.00, 'quantity' => 4],
            ],
        ])
            ->assertStatus(200)
            ->assertJson([
                'data' => ['subtotal' => '120.00', 'total' => '120.00'],
            ]);

        // Stock must still be untouched — draft has not been issued
        expect($newProduct->fresh()->stock_quantity)->toBe(20);
    });

    it('rejects updating a non-draft invoice', function () {
        $user = actingAsInvoiceUser();

        foreach (['issued', 'paid', 'cancelled'] as $status) {
            $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => $status]);

            $this->putJson(API_INVOICES."/{$invoice->id}", ['due_date' => '2026-12-31'])
                ->assertStatus(422)
                ->assertJson(['message' => 'Only draft invoices can be updated.']);
        }
    });

    it('returns 404 when invoice does not exist', function () {
        actingAsInvoiceUser();

        $this->putJson(API_INVOICES.'/non-existent-uuid', ['due_date' => '2026-12-31'])
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $this->putJson(API_INVOICES."/{$invoice->id}", [])->assertStatus(401);
    });
});

// ─── Issue ────────────────────────────────────────────────────────────────────

describe('POST /invoices/{id}/issue', function () {
    it('transitions a draft invoice to issued and deducts stock', function () {
        $user = actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 15, 'unit_price' => 40.00]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $invoice->items()->create([
            'product_id' => $product->id,
            'unit_price' => 40.00,
            'quantity' => 5,
            'amount' => 200.00,
        ]);

        $this->postJson(API_INVOICES."/{$invoice->id}/issue")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice issued successfully.',
                'data' => ['id' => $invoice->id, 'status' => 'issued'],
            ]);

        expect($product->fresh()->stock_quantity)->toBe(10);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => 5,
            'stock_before' => 15,
            'stock_after' => 10,
        ]);
    });

    it('rejects issuing a non-draft invoice', function () {
        $user = actingAsInvoiceUser();

        foreach (['issued', 'paid', 'cancelled'] as $status) {
            $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => $status]);

            $this->postJson(API_INVOICES."/{$invoice->id}/issue")
                ->assertStatus(422)
                ->assertJson(['message' => 'Only draft invoices can be issued.']);
        }
    });

    it('rejects issuing when a product has insufficient stock', function () {
        $user = actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 1, 'unit_price' => 10.00]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $invoice->items()->create([
            'product_id' => $product->id,
            'unit_price' => 10.00,
            'quantity' => 5,
            'amount' => 50.00,
        ]);

        $this->postJson(API_INVOICES."/{$invoice->id}/issue")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        // Invoice must remain draft, stock unchanged
        expect($invoice->fresh()->status)->toBe('draft');
        expect($product->fresh()->stock_quantity)->toBe(1);
    });

    it('returns 404 for an unknown invoice', function () {
        actingAsInvoiceUser();

        $this->postJson(API_INVOICES.'/non-existent-uuid/issue')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $this->postJson(API_INVOICES."/{$invoice->id}/issue")->assertStatus(401);
    });
});

// ─── Cancel ───────────────────────────────────────────────────────────────────

describe('POST /invoices/{id}/cancel', function () {
    it('cancels a draft invoice', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $this->postJson(API_INVOICES."/{$invoice->id}/cancel")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice cancelled successfully.',
                'data' => ['id' => $invoice->id, 'status' => 'cancelled'],
            ]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'cancelled']);
    });

    it('cancels an issued invoice', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'issued']);

        $this->postJson(API_INVOICES."/{$invoice->id}/cancel")
            ->assertStatus(200)
            ->assertJson(['data' => ['status' => 'cancelled']]);
    });

    it('restores stock when cancelling an issued invoice', function () {
        $user = actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 8, 'unit_price' => 50.00]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'issued']);
        $invoice->items()->create([
            'product_id' => $product->id,
            'unit_price' => 50.00,
            'quantity' => 3,
            'amount' => 150.00,
        ]);

        $this->postJson(API_INVOICES."/{$invoice->id}/cancel")->assertStatus(200);

        expect($product->fresh()->stock_quantity)->toBe(11);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'cancellation',
            'quantity' => 3,
            'stock_before' => 8,
            'stock_after' => 11,
        ]);
    });

    it('does not restore stock when cancelling a draft invoice', function () {
        $user = actingAsInvoiceUser();
        $product = Product::factory()->create(['stock_quantity' => 10, 'unit_price' => 50.00]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $invoice->items()->create([
            'product_id' => $product->id,
            'unit_price' => 50.00,
            'quantity' => 3,
            'amount' => 150.00,
        ]);

        $this->postJson(API_INVOICES."/{$invoice->id}/cancel")->assertStatus(200);

        // Draft never had stock deducted, so nothing to restore
        expect($product->fresh()->stock_quantity)->toBe(10);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'type' => 'cancellation',
        ]);
    });

    it('rejects cancelling a paid invoice', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'paid']);

        $this->postJson(API_INVOICES."/{$invoice->id}/cancel")
            ->assertStatus(422)
            ->assertJson(['message' => 'Paid invoices cannot be cancelled.']);
    });

    it('rejects cancelling an already-cancelled invoice', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'cancelled']);

        $this->postJson(API_INVOICES."/{$invoice->id}/cancel")
            ->assertStatus(422)
            ->assertJson(['message' => 'Invoice is already cancelled.']);
    });

    it('returns 404 for an unknown invoice', function () {
        actingAsInvoiceUser();

        $this->postJson(API_INVOICES.'/non-existent-uuid/cancel')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $invoice = Invoice::factory()->create(['status' => 'issued']);

        $this->postJson(API_INVOICES."/{$invoice->id}/cancel")->assertStatus(401);
    });
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

describe('DELETE /invoices/{id}', function () {
    it('soft-deletes an invoice', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'issued']);

        $this->deleteJson(API_INVOICES."/{$invoice->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Invoice deleted successfully.']);

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    });

    it('prevents deleting a paid invoice', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'paid']);

        $this->deleteJson(API_INVOICES."/{$invoice->id}")
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Paid invoices cannot be deleted.',
            ]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
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

    it('rejects marking an already-paid invoice as paid', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'paid']);

        $this->postJson(API_INVOICES."/{$invoice->id}/mark-paid")
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invoice is already paid.',
            ]);
    });

    it('rejects marking a cancelled invoice as paid', function () {
        $user = actingAsInvoiceUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'cancelled']);

        $this->postJson(API_INVOICES."/{$invoice->id}/mark-paid")
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cancelled invoices cannot be marked as paid.',
            ]);
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
