<?php

namespace Tests\Feature;

use App\Domains\Users\Enums\UserRole;
use App\Models\Category;
use App\Models\Produt;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_update_and_deactivate_a_product(): void
    {
        $user = $this->tenant();
        $this->actingAs($user);
        $attributes = $this->attributes($user);
        $id = $this->postJson('/api/products', $attributes)->assertCreated()
            ->assertJsonPath('data.price', '3.50')->json('data.id');

        $this->getJson("/api/restaurants/$user->restaurant_id/products/$id")
            ->assertOk()->assertJsonPath('data.name', 'Sopa');
        $this->patchJson("/api/products/$id", ['name' => 'Sopa do dia', 'price' => 4.25])
            ->assertOk()->assertJsonPath('data.price', '4.25')
            ->assertJsonPath('data.quantity', 8)->assertJsonPath('data.name', 'Sopa do dia');
        $this->deleteJson("/api/products/$id")->assertOk()->assertJsonPath('data.status', 'inactive');
        $this->getJson("/api/restaurants/$user->restaurant_id/products/$id")->assertNotFound();
        $this->assertDatabaseHas('produts', ['id' => $id, 'status' => 'inactive', 'quantity' => 8]);
    }

    public function test_public_catalog_filters_paginates_and_hides_inactive_products(): void
    {
        $user = $this->tenant();
        $attributes = $this->attributes($user);
        foreach (['Sopa A', 'Sopa B', 'Salada'] as $name) {
            Produt::query()->create([...$attributes, 'restaurant_id' => $user->restaurant_id, 'name' => $name]);
        }
        Produt::query()->create([...$attributes, 'restaurant_id' => $user->restaurant_id, 'name' => 'Sopa oculta', 'status' => 'inactive']);
        $other = $this->tenant();
        Produt::query()->create([...$this->attributes($other), 'restaurant_id' => $other->restaurant_id]);

        $this->getJson("/api/restaurants/$user->restaurant_id/products?name=Sopa&per_page=1")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Sopa A');
        $this->getJson("/api/restaurants/$user->restaurant_id/products?name=Sopa&per_page=1&page=2")
            ->assertOk()->assertJsonPath('data.0.name', 'Sopa B');
        $this->getJson("/api/restaurants/$user->restaurant_id/products")
            ->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_another_restaurant_cannot_read_update_or_deactivate_a_product(): void
    {
        $owner = $this->tenant();
        $other = $this->tenant();
        $product = Produt::query()->create([...$this->attributes($owner), 'restaurant_id' => $owner->restaurant_id]);
        $this->actingAs($other);
        $this->getJson("/api/restaurants/$other->restaurant_id/products/$product->id")->assertNotFound();
        $this->patchJson("/api/products/$product->id", ['name' => 'Alterado'])->assertNotFound();
        $this->deleteJson("/api/products/$product->id")->assertNotFound();
        $this->assertSame('Sopa', $product->fresh()->name);
        $this->assertSame('active', $product->fresh()->status);
    }

    public function test_foreign_category_is_rejected_on_creation_and_update(): void
    {
        $owner = $this->tenant();
        $other = $this->tenant();
        $attributes = $this->attributes($owner);
        $foreignCategory = $this->attributes($other)['category_id'];
        $product = Produt::query()->create([...$attributes, 'restaurant_id' => $owner->restaurant_id]);
        $this->actingAs($owner);
        $this->postJson('/api/products', [...$attributes, 'category_id' => $foreignCategory])
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->patchJson("/api/products/$product->id", ['category_id' => $foreignCategory])
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->assertDatabaseCount('produts', 1);
        $this->assertSame($attributes['category_id'], $product->fresh()->category_id);
    }

    public function test_invalid_updates_preserve_the_product(): void
    {
        $owner = $this->tenant();
        $product = Produt::query()->create([...$this->attributes($owner), 'restaurant_id' => $owner->restaurant_id]);
        $this->actingAs($owner);
        $this->patchJson("/api/products/$product->id", [
            'name' => '', 'type' => '', 'price' => 0, 'quantity' => -1, 'status' => 'unknown',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'type', 'price', 'quantity', 'status']);
        $this->assertSame('Sopa', $product->fresh()->name);
        $this->assertSame(8, $product->fresh()->quantity);
    }

    public function test_inactive_restaurant_has_no_public_catalog(): void
    {
        $owner = $this->tenant();
        $product = Produt::query()->create([...$this->attributes($owner), 'restaurant_id' => $owner->restaurant_id]);
        $owner->restaurant->update(['status' => 'inactive']);
        $this->getJson("/api/restaurants/$owner->restaurant_id/products")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/restaurants/$owner->restaurant_id/products/$product->id")->assertNotFound();
    }

    public function test_catalog_validates_pagination_and_filters(): void
    {
        $owner = $this->tenant();
        foreach (['per_page=0', 'per_page=101', 'per_page=abc', 'name[]=Sopa'] as $query) {
            $this->getJson("/api/restaurants/$owner->restaurant_id/products?$query")->assertUnprocessable();
        }
    }

    private function tenant(): User
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Restaurante', 'address' => 'Rua Teste', 'phone_number' => '910000000',
            'nif' => fake()->unique()->numerify('#########'), 'status' => 'active',
        ]);

        return User::factory()->create(['restaurant_id' => $restaurant->id, 'role' => UserRole::STAFF->value]);
    }

    private function attributes(User $user): array
    {
        $category = Category::query()->create([
            'restaurant_id' => $user->restaurant_id, 'name' => 'Pratos', 'status' => 'active',
        ]);

        return ['name' => 'Sopa', 'type' => 'comida', 'price' => 3.50, 'quantity' => 8, 'category_id' => $category->id, 'status' => 'active'];
    }
}
