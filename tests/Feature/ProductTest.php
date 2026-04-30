<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const API_PRODUCTS = '/api/v1/products';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function actingAsProductUser(): User
{
    $user = User::factory()->create();
    test()->actingAs($user, 'sanctum');

    return $user;
}

// ─── Index ────────────────────────────────────────────────────────────────────

describe('GET /products', function () {
    it('returns a paginated list of products', function () {
        actingAsProductUser();
        Product::factory()->count(3)->create();

        $this->getJson(API_PRODUCTS)
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'status',
                'status_code',
                'data' => [
                    'data' => [['id', 'product_name', 'description', 'unit_price', 'stock_quantity']],
                    'meta' => ['current_page', 'total'],
                ],
            ])
            ->assertJson(['success' => true, 'status_code' => 200]);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(API_PRODUCTS)->assertStatus(401);
    });
});

// ─── Store ────────────────────────────────────────────────────────────────────

describe('POST /products', function () {
    it('creates a product and returns 201', function () {
        actingAsProductUser();

        $this->postJson(API_PRODUCTS, [
            'product_name' => 'Wireless Keyboard',
            'description' => 'A compact wireless keyboard.',
            'unit_price' => 49.99,
            'stock_quantity' => 100,
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'product_name', 'description', 'unit_price', 'stock_quantity']])
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => [
                    'product_name' => 'Wireless Keyboard',
                    'unit_price' => '49.99',
                    'stock_quantity' => 100,
                ],
            ]);

        $this->assertDatabaseHas('products', ['product_name' => 'Wireless Keyboard']);
    });

    it('defaults stock_quantity to 0 when not provided', function () {
        actingAsProductUser();

        $this->postJson(API_PRODUCTS, [
            'product_name' => 'USB Hub',
            'unit_price' => 19.99,
        ])
            ->assertStatus(201)
            ->assertJson(['data' => ['stock_quantity' => 0]]);
    });

    it('returns 401 when unauthenticated', function () {
        $this->postJson(API_PRODUCTS, [])->assertStatus(401);
    });

    it('fails when product_name is missing', function () {
        actingAsProductUser();

        $this->postJson(API_PRODUCTS, ['unit_price' => 10.00])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_name']);
    });

    it('fails when unit_price is missing', function () {
        actingAsProductUser();

        $this->postJson(API_PRODUCTS, ['product_name' => 'Widget'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_price']);
    });

    it('fails when unit_price is negative', function () {
        actingAsProductUser();

        $this->postJson(API_PRODUCTS, ['product_name' => 'Widget', 'unit_price' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_price']);
    });

    it('fails when stock_quantity is negative', function () {
        actingAsProductUser();

        $this->postJson(API_PRODUCTS, ['product_name' => 'Widget', 'unit_price' => 10.00, 'stock_quantity' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stock_quantity']);
    });
});

// ─── Show ─────────────────────────────────────────────────────────────────────

describe('GET /products/{id}', function () {
    it('returns a single product', function () {
        actingAsProductUser();
        $product = Product::factory()->create();

        $this->getJson(API_PRODUCTS."/{$product->id}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                ],
            ]);
    });

    it('returns 404 for an unknown product', function () {
        actingAsProductUser();

        $this->getJson(API_PRODUCTS.'/non-existent-uuid')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $product = Product::factory()->create();

        $this->getJson(API_PRODUCTS."/{$product->id}")->assertStatus(401);
    });
});

// ─── Update ───────────────────────────────────────────────────────────────────

describe('PUT /products/{id}', function () {
    it('updates an existing product', function () {
        actingAsProductUser();
        $product = Product::factory()->create(['unit_price' => 50.00]);

        $this->putJson(API_PRODUCTS."/{$product->id}", [
            'product_name' => 'Updated Name',
            'unit_price' => 75.00,
        ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data' => ['product_name' => 'Updated Name', 'unit_price' => '75.00'],
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'product_name' => 'Updated Name']);
    });

    it('allows partial updates', function () {
        actingAsProductUser();
        $product = Product::factory()->create(['product_name' => 'Original Name']);

        $this->patchJson(API_PRODUCTS."/{$product->id}", ['unit_price' => 99.99])
            ->assertStatus(200)
            ->assertJson(['data' => ['product_name' => 'Original Name', 'unit_price' => '99.99']]);
    });

    it('fails when unit_price is negative', function () {
        actingAsProductUser();
        $product = Product::factory()->create();

        $this->putJson(API_PRODUCTS."/{$product->id}", ['unit_price' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_price']);
    });

    it('returns 404 when product does not exist', function () {
        actingAsProductUser();

        $this->putJson(API_PRODUCTS.'/non-existent-uuid', ['product_name' => 'X'])
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $product = Product::factory()->create();

        $this->putJson(API_PRODUCTS."/{$product->id}", [])->assertStatus(401);
    });
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

describe('DELETE /products/{id}', function () {
    it('soft-deletes a product', function () {
        actingAsProductUser();
        $product = Product::factory()->create();

        $this->deleteJson(API_PRODUCTS."/{$product->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Product deleted successfully.']);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    });

    it('returns 404 when product does not exist', function () {
        actingAsProductUser();

        $this->deleteJson(API_PRODUCTS.'/non-existent-uuid')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $product = Product::factory()->create();

        $this->deleteJson(API_PRODUCTS."/{$product->id}")->assertStatus(401);
    });
});

// ─── Restock ──────────────────────────────────────────────────────────────────

describe('POST /products/{id}/restock', function () {
    it('increments stock and records a stock movement', function () {
        actingAsProductUser();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->postJson(API_PRODUCTS."/{$product->id}/restock", [
            'quantity' => 25,
            'description' => 'Monthly restock',
        ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product restocked successfully.',
                'data' => ['stock_quantity' => 35],
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 35]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'restock',
            'quantity' => 25,
            'stock_before' => 10,
            'stock_after' => 35,
            'description' => 'Monthly restock',
        ]);
    });

    it('uses a default description when none is provided', function () {
        actingAsProductUser();
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $this->postJson(API_PRODUCTS."/{$product->id}/restock", ['quantity' => 10])
            ->assertStatus(200);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'restock',
            'quantity' => 10,
        ]);
    });

    it('fails when quantity is zero', function () {
        actingAsProductUser();
        $product = Product::factory()->create();

        $this->postJson(API_PRODUCTS."/{$product->id}/restock", ['quantity' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    });

    it('fails when quantity is missing', function () {
        actingAsProductUser();
        $product = Product::factory()->create();

        $this->postJson(API_PRODUCTS."/{$product->id}/restock", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    });

    it('returns 404 for an unknown product', function () {
        actingAsProductUser();

        $this->postJson(API_PRODUCTS.'/non-existent-uuid/restock', ['quantity' => 5])
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $product = Product::factory()->create();

        $this->postJson(API_PRODUCTS."/{$product->id}/restock", ['quantity' => 5])->assertStatus(401);
    });
});
