<?php

namespace Tests\Feature;

use App\Domains\Users\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

class OrderStateTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    #[DataProvider('transitions')]
    public function test_order_transitions_preserve_inventory_and_terminal_states(string $from, string $to, bool $allowed): void
    {
        $restaurant = $this->restaurant();
        $admin = $this->restaurantUser($restaurant);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $product = $this->product($restaurant, 6);
        $order = $this->order($restaurant, $customer, $product, $from);
        $this->actingAs($admin);

        $response = $this->patchJson("/api/restaurant/orders/$order->id/status", ['status' => $to]);
        if ($allowed) {
            $response->assertOk()->assertJsonPath('data.status', $to);
        } else {
            $response->assertUnprocessable()->assertJsonPath('message', 'Transição de estado do pedido inválida.');
        }
        $this->assertSame($allowed ? $to : $from, $order->fresh()->payment_status);
        $this->assertSame($allowed && $to === 'cancelled' ? 8 : 6, $product->fresh()->quantity);
        $this->assertSame('7.00', $order->fresh()->payment_value);
    }

    public static function transitions(): array
    {
        $cases = [];
        foreach (['pending', 'in_progress', 'completed', 'cancelled'] as $from) {
            foreach (['pending', 'in_progress', 'completed', 'cancelled'] as $to) {
                $allowed = ($from === 'pending' && in_array($to, ['in_progress', 'cancelled'], true))
                    || ($from === 'in_progress' && in_array($to, ['completed', 'cancelled'], true));
                $cases["$from to $to"] = [$from, $to, $allowed];
            }
        }

        return $cases;
    }

    public function test_repeated_customer_cancellation_cannot_restore_stock_twice(): void
    {
        $restaurant = $this->restaurant();
        $product = $this->product($restaurant, 6);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $order = $this->order($restaurant, $customer, $product);
        $this->actingAs($customer);
        $this->postJson("/api/orders/$order->id/cancel")->assertOk();
        $this->postJson("/api/orders/$order->id/cancel")->assertNotFound();
        $this->assertSame(8, $product->fresh()->quantity);
    }

    public function test_insufficient_stock_on_second_item_rolls_back_the_whole_order(): void
    {
        $restaurant = $this->restaurant();
        $first = $this->product($restaurant, 8);
        $second = $this->product($restaurant, 1);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $this->actingAs($customer)->postJson('/api/orders', [
            'restaurant_id' => $restaurant->id, 'address' => 'Rua Cliente',
            'items' => [['product_id' => $first->id, 'quantity' => 2], ['product_id' => $second->id, 'quantity' => 2]],
        ])->assertUnprocessable();
        $this->assertSame(8, $first->fresh()->quantity);
        $this->assertSame(1, $second->fresh()->quantity);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_itens', 0);
    }

    public function test_restaurant_cannot_manage_another_restaurants_orders(): void
    {
        $restaurant = $this->restaurant();
        $other = $this->restaurant();
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $product = $this->product($restaurant, 6);
        $order = $this->order($restaurant, $customer, $product);
        $this->actingAs($this->restaurantUser($other));
        $this->getJson("/api/restaurant/orders/$order->id")->assertNotFound();
        $this->patchJson("/api/restaurant/orders/$order->id/status", ['status' => 'cancelled'])->assertNotFound();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(6, $product->fresh()->quantity);
    }
}
