<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the primary test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Create products
        $products = Product::factory(15)->create();

        // Create customers
        $customers = Customer::factory(10)->create();

        // Create invoices belonging to the test user, each with 2-4 line items
        Invoice::factory(20)
            ->recycle($customers)
            ->create(['user_id' => $user->id])
            ->each(function (Invoice $invoice) use ($products): void {
                $items = InvoiceItem::factory(fake()->numberBetween(2, 4))
                    ->recycle($products)
                    ->create(['invoice_id' => $invoice->id]);

                // Compute and persist accurate subtotal / total
                $subtotal = $items->sum('amount');

                $invoice->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                ]);
            });
    }
}
