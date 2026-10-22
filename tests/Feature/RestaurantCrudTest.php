<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\User;
use App\Domains\Users\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Agrupa os testes de `RestaurantCrudTest` para verificar o comportamento esperado da aplicação.
 */
class RestaurantCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_and_manage_its_restaurant(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $this->actingAs($superAdmin);

        $createResponse = $this->postJson('/api/restaurants', [
            'name' => 'Restaurante Central',
            'address' => 'Rua Central, 10',
            'phone_number' => '910000000',
            'nif' => '123456789',
            'admin_name' => 'Administrador Central',
            'admin_email' => 'admin.central@example.test',
            'admin_password' => 'A-very-secure-password',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Restaurante Central')
            ->assertJsonPath('data.status', 'active');

        $restaurantId = $createResponse->json('data.id');

        $restaurantAdmin = User::query()->where('email', 'admin.central@example.test')->firstOrFail();
        $this->assertSame($restaurantId, $restaurantAdmin->restaurant_id);
        $this->assertSame(UserRole::RESTAURANT_ADMIN->value, $restaurantAdmin->role);

        $this->getJson('/api/restaurants')
            ->assertOk()
            ->assertJsonPath('data.0.id', $restaurantId);

        $this->getJson("/api/restaurants/{$restaurantId}")
            ->assertOk()
            ->assertJsonPath('data.nif', '123456789');

        $this->patchJson("/api/restaurants/{$restaurantId}", [
            'name' => 'Restaurante Atualizado',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Restaurante Atualizado');

        $this->deleteJson("/api/restaurants/{$restaurantId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->getJson('/api/categories')
            ->assertForbidden();

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurantId,
            'status' => 'inactive',
        ]);
    }

    public function test_restaurant_cannot_access_another_tenants_categories(): void
    {
        $restaurantA = Restaurant::query()->create([
            'name' => 'Restaurante A',
            'address' => 'Rua A',
            'phone_number' => '910000001',
            'nif' => '123456781',
            'status' => 'active',
        ]);
        $restaurantB = Restaurant::query()->create([
            'name' => 'Restaurante B',
            'address' => 'Rua B',
            'phone_number' => '910000002',
            'nif' => '123456782',
            'status' => 'active',
        ]);

        $userA = User::factory()->create();
        $userA->role = UserRole::RESTAURANT_ADMIN->value;
        $userA->restaurant()->associate($restaurantA);
        $userA->save();

        $userB = User::factory()->create();
        $userB->role = UserRole::RESTAURANT_ADMIN->value;
        $userB->restaurant()->associate($restaurantB);
        $userB->save();

        $category = Category::query()->create([
            'restaurant_id' => $restaurantA->id,
            'name' => 'Categoria A',
            'status' => 'active',
        ]);

        $this->actingAs($userB)
            ->getJson("/api/categories/{$category->id}")
            ->assertNotFound();

        $this->getJson('/api/restaurants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $restaurantB->id);

        $this->getJson("/api/restaurants/{$restaurantA->id}")
            ->assertOk();
    }

    public function test_user_without_a_restaurant_cannot_manage_categories(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/categories')
            ->assertForbidden()
            ->assertJsonPath('message', 'A conta não está associada a um restaurante ativo.');
    }
}
