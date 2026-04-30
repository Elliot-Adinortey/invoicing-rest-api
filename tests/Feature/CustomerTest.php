<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const API_CUSTOMERS = '/api/v1/customers';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function actingAsApiUser(): User
{
    $user = User::factory()->create();
    test()->actingAs($user, 'sanctum');

    return $user;
}

// ─── Index ────────────────────────────────────────────────────────────────────

describe('GET /customers', function () {
    it('returns a paginated list of customers', function () {
        actingAsApiUser();
        Customer::factory()->count(3)->create();

        $this->getJson(API_CUSTOMERS)
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'status',
                'status_code',
                'data' => [
                    'data' => [['id', 'customer_name', 'email', 'phone', 'address']],
                    'meta' => ['current_page', 'total'],
                ],
            ])
            ->assertJson(['success' => true, 'status_code' => 200]);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(API_CUSTOMERS)->assertStatus(401);
    });
});

// ─── Store ────────────────────────────────────────────────────────────────────

describe('POST /customers', function () {
    it('creates a customer and returns 201', function () {
        actingAsApiUser();

        $this->postJson(API_CUSTOMERS, [
            'customer_name' => 'Acme Corp',
            'email' => 'acme@example.com',
            'phone' => '+1 555-0100',
            'address' => '123 Main St',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'customer_name', 'email', 'phone', 'address']])
            ->assertJson([
                'success' => true,
                'message' => 'Customer created successfully.',
                'data' => ['customer_name' => 'Acme Corp', 'email' => 'acme@example.com'],
            ]);

        $this->assertDatabaseHas('customers', ['email' => 'acme@example.com']);
    });

    it('fails when customer_name is missing', function () {
        actingAsApiUser();

        $this->postJson(API_CUSTOMERS, ['email' => 'acme@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name']);
    });

    it('fails when email is a duplicate', function () {
        actingAsApiUser();
        Customer::factory()->create(['email' => 'dup@example.com']);

        $this->postJson(API_CUSTOMERS, [
            'customer_name' => 'Dupe Co',
            'email' => 'dup@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

// ─── Show ─────────────────────────────────────────────────────────────────────

describe('GET /customers/{id}', function () {
    it('returns a single customer', function () {
        actingAsApiUser();
        $customer = Customer::factory()->create();

        $this->getJson(API_CUSTOMERS."/{$customer->id}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'customer_name' => $customer->customer_name,
                ],
            ]);
    });

    it('returns 404 for an unknown customer', function () {
        actingAsApiUser();

        $this->getJson(API_CUSTOMERS.'/non-existent-uuid')->assertStatus(404);
    });
});

// ─── Update ───────────────────────────────────────────────────────────────────

describe('PUT /customers/{id}', function () {
    it('updates an existing customer', function () {
        actingAsApiUser();
        $customer = Customer::factory()->create();

        $this->putJson(API_CUSTOMERS."/{$customer->id}", [
            'customer_name' => 'Updated Name',
            'phone' => '+44 20 7946 0958',
        ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'data' => ['customer_name' => 'Updated Name'],
            ]);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'customer_name' => 'Updated Name']);
    });

    it('allows updating email to the same value (ignores self in unique check)', function () {
        actingAsApiUser();
        $customer = Customer::factory()->create(['email' => 'same@example.com']);

        $this->putJson(API_CUSTOMERS."/{$customer->id}", [
            'email' => 'same@example.com',
        ])->assertStatus(200);
    });

    it('returns 404 when customer does not exist', function () {
        actingAsApiUser();

        $this->putJson(API_CUSTOMERS.'/non-existent-uuid', ['customer_name' => 'X'])
            ->assertStatus(404);
    });
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

describe('DELETE /customers/{id}', function () {
    it('soft-deletes a customer', function () {
        actingAsApiUser();
        $customer = Customer::factory()->create();

        $this->deleteJson(API_CUSTOMERS."/{$customer->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Customer deleted successfully.']);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    });

    it('returns 404 when customer does not exist', function () {
        actingAsApiUser();

        $this->deleteJson(API_CUSTOMERS.'/non-existent-uuid')->assertStatus(404);
    });
});
