<?php

namespace Tests\Feature;

use App\Domains\Users\Enums\UserRole;
use App\Models\Category;
use App\Models\Produt;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

/**
 * Agrupa os testes de `MarketplaceApiTest` para verificar o comportamento esperado da aplicação.
 */
class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_returns_a_sanctum_token_and_authenticates(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Cliente Exemplo',
            'email' => 'CLIENTE@example.test',
            'password' => 'A-strong-customer-password',
            'password_confirmation' => 'A-strong-customer-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', UserRole::CUSTOMER->value);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('data.email', 'cliente@example.test');

        $user = User::query()->where('email', 'cliente@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('A-strong-customer-password', $user->password));

        $login = $this->postJson('/api/auth/login', [
            'email' => 'cliente@example.test',
            'password' => 'A-strong-customer-password',
        ])->assertOk();
        $loginToken = $login->json('token');
        $loginTokenId = $user->tokens()->where('name', 'api')->firstOrFail()->id;

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/logout', [], ['Authorization' => 'Bearer '.$loginToken])
            ->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $loginTokenId]);
    }

    public function test_password_reset_uses_a_generic_response_and_revokes_existing_tokens(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'reset@example.test',
            'role' => UserRole::CUSTOMER->value,
        ]);
        $existingToken = $user->createToken('before-reset')->plainTextToken;

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', 'Se existir uma conta para esse endereço, receberá instruções para repor a palavra-passe.');

        $resetToken = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$resetToken): bool {
            $resetToken = $notification->token;

            return true;
        });

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $resetToken,
            'password' => 'A-new-secure-password',
            'password_confirmation' => 'A-new-secure-password',
        ])->assertOk();

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$existingToken])
            ->assertUnauthorized();
        $this->assertTrue(Hash::check('A-new-secure-password', $user->fresh()->password));
    }

    public function test_restaurant_admin_can_create_staff_only_for_its_restaurant(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Equipa',
            'address' => 'Rua Equipa, 1',
            'phone_number' => '910000003',
            'nif' => '123456782',
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'role' => UserRole::RESTAURANT_ADMIN->value,
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/restaurant/users', [
                'name' => 'Funcionário',
                'email' => 'funcionario@example.test',
                'password' => 'A-secure-staff-password',
                'password_confirmation' => 'A-secure-staff-password',
            ])
            ->assertCreated()
            ->assertJsonPath('data.role', UserRole::STAFF->value)
            ->assertJsonPath('data.restaurant_id', $restaurant->id);

        $this->assertDatabaseHas('users', [
            'email' => 'funcionario@example.test',
            'role' => UserRole::STAFF->value,
            'restaurant_id' => $restaurant->id,
        ]);
    }

    public function test_customer_can_order_from_a_restaurant_and_restaurant_staff_can_process_the_order(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Loja Online',
            'address' => 'Rua Loja, 1',
            'phone_number' => '910000001',
            'nif' => '123456780',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::RESTAURANT_ADMIN->value,
            'restaurant_id' => $restaurant->id,
        ]);
        $category = Category::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
            'status' => 'active',
        ]);
        $product = Produt::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Sumo',
            'type' => 'bebida',
            'price' => 2.50,
            'quantity' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/products', [
                'name' => 'Água',
                'type' => 'bebida',
                'price' => 1.25,
                'quantity' => 20,
                'category_id' => $category->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.restaurant_id', $restaurant->id);

        $customer = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $this->actingAs($customer);

        $orderResponse = $this->postJson('/api/orders', [
            'restaurant_id' => $restaurant->id,
            'address' => 'Rua do Cliente, 5',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $orderResponse->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', '5.00')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.unit_price', '2.50');

        $orderId = $orderResponse->json('data.id');
        $this->assertDatabaseHas('produts', ['id' => $product->id, 'quantity' => 3]);

        $this->actingAs($admin)
            ->patchJson("/api/restaurant/orders/{$orderId}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->patchJson("/api/restaurant/orders/{$orderId}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->actingAs($customer)
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $orderId);
    }

    public function test_customer_can_cancel_pending_order_and_restore_stock(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Restaurante Cancelamento',
            'address' => 'Rua A',
            'phone_number' => '910000002',
            'nif' => '123456781',
            'status' => 'active',
        ]);
        $category = Category::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Pratos',
            'status' => 'active',
        ]);
        $product = Produt::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Prato',
            'type' => 'comida',
            'price' => 10,
            'quantity' => 3,
            'status' => 'active',
        ]);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $this->actingAs($customer);

        $orderId = $this->postJson('/api/orders', [
            'restaurant_id' => $restaurant->id,
            'address' => 'Rua Cliente',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/orders/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('produts', ['id' => $product->id, 'quantity' => 3]);
    }
}
