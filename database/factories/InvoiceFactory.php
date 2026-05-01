<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    private static int $sequence = 1;

    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'invoice_number' => 'INV-'.str_pad(self::$sequence++, 5, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'issue_date' => $issueDate,
            'due_date' => fake()->dateTimeBetween($issueDate, '+30 days'),
            'subtotal' => 0,
            'total' => 0,
            'status' => fake()->randomElement(['draft', 'issued', 'paid', 'cancelled']),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }
}
