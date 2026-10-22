<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\User;
use App\Domains\Users\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Agrupa os testes de `CategoryCrudTest` para verificar o comportamento esperado da aplicação.
 */
class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_manage_a_category_through_its_lifecycle(): void
    {
        $this->actingAs($this->createTenantUser());

        $createResponse = $this->postJson('/api/categories', [
            'name' => 'Bebidas',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Bebidas')
            ->assertJsonPath('data.status', 'active');

        $categoryId = $createResponse->json('data.id');

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $categoryId);

        $this->getJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Bebidas');

        $this->patchJson("/api/categories/{$categoryId}", [
            'name' => 'Bebidas frias',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Bebidas frias')
            ->assertJsonPath('data.status', 'active');

        $this->deleteJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('categories', [
            'id' => $categoryId,
            'name' => 'Bebidas frias',
            'status' => 'inactive',
        ]);

        $this->assertSame(1, Category::query()->count());
    }

    public function test_category_index_supports_per_page_pagination_and_filters(): void
    {
        $user = $this->createTenantUser();
        $this->actingAs($user);

        $firstCategory = Category::query()->create([
            'restaurant_id' => $user->restaurant_id,
            'name' => 'Bebidas quentes',
            'status' => 'active',
        ]);
        Category::query()->create([
            'restaurant_id' => $user->restaurant_id,
            'name' => 'Bebidas frias',
            'status' => 'active',
        ]);
        Category::query()->create([
            'restaurant_id' => $user->restaurant_id,
            'name' => 'Sobremesas',
            'status' => 'active',
        ]);

        $this->getJson('/api/categories?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/categories?name=bebidas')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/categories?id='.$firstCategory->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $firstCategory->id);
    }

    public function test_category_index_validates_per_page_and_filter_parameters(): void
    {
        $this->actingAs($this->createTenantUser());

        $this->getJson('/api/categories?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->getJson('/api/categories?id=not-an-id')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id');
    }

    private function createTenantUser(): User
    {
        $user = User::factory()->create();
        $user->role = UserRole::RESTAURANT_ADMIN->value;
        $user->save();
        $restaurant = Restaurant::query()->create([
            'name' => 'Restaurante Teste',
            'address' => 'Rua Teste',
            'phone_number' => '910000000',
            'nif' => fake()->unique()->numerify('#########'),
            'status' => 'active',
        ]);

        $user->restaurant()->associate($restaurant);
        $user->save();

        return $user;
    }
}
