<?php

namespace Tests\Concerns;

use App\Domains\Users\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Produt;
use App\Models\Restaurant;
use App\Models\User;

trait BuildsMarketplace
{
    private function restaurant(string $status = 'active'): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'Restaurante Teste', 'address' => 'Rua Teste', 'phone_number' => '910000000',
            'nif' => fake()->unique()->numerify('#########'), 'status' => $status,
        ]);
    }

    private function restaurantUser(Restaurant $restaurant, UserRole $role = UserRole::RESTAURANT_ADMIN): User
    {
        return User::factory()->create(['restaurant_id' => $restaurant->id, 'role' => $role->value]);
    }

    private function category(Restaurant $restaurant): Category
    {
        return Category::query()->create(['restaurant_id' => $restaurant->id, 'name' => 'Pratos', 'status' => 'active']);
    }

    private function product(Restaurant $restaurant, int $quantity = 8): Produt
    {
        return Produt::query()->create([
            'restaurant_id' => $restaurant->id, 'category_id' => $this->category($restaurant)->id,
            'name' => 'Sopa', 'type' => 'comida', 'price' => '3.50', 'quantity' => $quantity, 'status' => 'active',
        ]);
    }

    private function order(Restaurant $restaurant, User $customer, Produt $product, string $status = 'pending'): Order
    {
        $order = Order::query()->create([
            'restaurant_id' => $restaurant->id, 'customer_id' => $customer->id, 'user_id' => $customer->id,
            'address' => 'Rua Cliente', 'delivery_date' => now()->toDateString(),
            'payment_status' => $status, 'payment_value' => '7.00',
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'value' => '3.50']);

        return $order;
    }
}
