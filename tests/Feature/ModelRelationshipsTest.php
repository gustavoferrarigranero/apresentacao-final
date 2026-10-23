<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_catalog_team_and_order_relations_resolve_the_persisted_records(): void
    {
        $restaurant = $this->restaurant();
        $other = $this->restaurant();
        $admin = $this->restaurantUser($restaurant);
        $otherUser = $this->restaurantUser($other);
        $product = $this->product($restaurant);
        $otherProduct = $this->product($other);
        $category = $product->category;
        $customer = User::factory()->create();
        $order = $this->order($restaurant, $customer, $product);
        $item = $order->items->sole();

        $this->assertTrue($category->restaurant->is($restaurant));
        $this->assertTrue($category->products->sole()->is($product));
        $this->assertTrue($restaurant->users->sole()->is($admin));
        $this->assertFalse($restaurant->users->contains($otherUser));
        $this->assertTrue($restaurant->categories->sole()->is($category));
        $this->assertTrue($restaurant->products->sole()->is($product));
        $this->assertFalse($restaurant->products->contains($otherProduct));
        $this->assertTrue($restaurant->orders->sole()->is($order));
        $this->assertTrue($product->restaurant->is($restaurant));
        $this->assertTrue($product->orderItems->sole()->is($item));
        $this->assertTrue($item->order->is($order));
        $this->assertTrue($item->product->is($product));
        $this->assertTrue($order->user->is($customer));
        $this->assertTrue($order->customer->is($customer));
        $this->assertTrue($order->restaurant->is($restaurant));
    }
}
