<?php

namespace Tests\Feature;

use App\Domains\Users\Enums\UserRole;
use App\Models\Order;
use App\Models\Produt;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica o percurso do marketplace desde a criação do restaurante até à conclusão do pedido.
 */
class MarketplaceDeliveryJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_team_can_publish_a_catalog_and_deliver_a_customer_order(): void
    {
        // Prepara uma conta de administração global e cria um token como faria um cliente da API.
        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
        ]);
        $superAdminToken = $superAdmin->createToken('test-super-admin')->plainTextToken;

        // Cria o restaurante pela API; a operação também deve criar a conta do seu administrador.
        $this->authenticateApiToken($superAdminToken);
        $restaurantResponse = $this->postJson('/api/restaurants', [
            'name' => 'Restaurante do Bairro',
            'address' => 'Rua do Mercado, 12',
            'phone_number' => '910000010',
            'nif' => '987654321',
            'admin_name' => 'Ana Administradora',
            'admin_email' => 'ana.restaurante@example.test',
            'admin_password' => 'palavra-passe-segura-ana',
        ]);

        $restaurantResponse->assertCreated()
            ->assertJsonPath('data.name', 'Restaurante do Bairro')
            ->assertJsonPath('data.status', 'active');
        $restaurantId = $restaurantResponse->json('data.id');

        // Autentica o administrador criado no passo anterior e confirma a associação ao restaurante.
        $adminLogin = $this->postJson('/api/auth/login', [
            'email' => 'ana.restaurante@example.test',
            'password' => 'palavra-passe-segura-ana',
        ])->assertOk();
        $adminToken = $adminLogin->json('token');
        $this->assertSame($restaurantId, $adminLogin->json('user.restaurant_id'));

        // Cria um membro de equipa e confirma que a conta fica limitada ao restaurante correto.
        $this->authenticateApiToken($adminToken);
        $staffResponse = $this->postJson('/api/restaurant/users', [
            'name' => 'Rui da Equipa',
            'email' => 'rui.equipa@example.test',
            'password' => 'palavra-passe-segura-rui',
            'password_confirmation' => 'palavra-passe-segura-rui',
        ]);
        $staffResponse->assertCreated()
            ->assertJsonPath('data.role', UserRole::STAFF->value)
            ->assertJsonPath('data.restaurant_id', $restaurantId);

        // Publica uma categoria e um produto usando a conta administrativa do restaurante.
        $this->authenticateApiToken($adminToken);
        $categoryResponse = $this->postJson('/api/categories', [
            'name' => 'Pratos do dia',
        ]);
        $categoryResponse->assertCreated()
            ->assertJsonPath('data.name', 'Pratos do dia');
        $categoryId = $categoryResponse->json('data.id');

        $this->authenticateApiToken($adminToken);
        $productResponse = $this->postJson('/api/products', [
            'name' => 'Sopa de legumes',
            'type' => 'comida',
            'price' => 3.50,
            'quantity' => 8,
            'category_id' => $categoryId,
            'status' => 'active',
        ]);
        $productResponse->assertCreated()
            ->assertJsonPath('data.restaurant_id', $restaurantId)
            ->assertJsonPath('data.category_id', $categoryId)
            ->assertJsonPath('data.status', 'active');
        $productId = $productResponse->json('data.id');

        // Confirma que um visitante consegue descobrir o restaurante e consultar o catálogo publicado.
        $this->getJson('/api/restaurants')
            ->assertOk()
            ->assertJsonFragment(['id' => $restaurantId, 'name' => 'Restaurante do Bairro']);
        $this->getJson("/api/restaurants/$restaurantId/products")
            ->assertOk()
            ->assertJsonPath('data.0.id', $productId)
            ->assertJsonPath('data.0.name', 'Sopa de legumes')
            ->assertJsonPath('data.0.price', '3.50');

        // Regista um cliente através da API e usa o token emitido para efetuar as operações seguintes.
        $customerRegistration = $this->postJson('/api/auth/register', [
            'name' => 'Carlos Cliente',
            'email' => 'carlos.cliente@example.test',
            'password' => 'palavra-passe-segura-carlos',
            'password_confirmation' => 'palavra-passe-segura-carlos',
        ]);
        $customerRegistration->assertCreated()
            ->assertJsonPath('user.role', UserRole::CUSTOMER->value);
        $customerToken = $customerRegistration->json('token');

        // Verifica que falta de stock impede a criação do pedido e não altera o inventário.
        $this->authenticateApiToken($customerToken);
        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'address' => 'Avenida Central, 20',
            'items' => [
                ['product_id' => $productId, 'quantity' => 9],
            ],
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Stock insuficiente para o produto Sopa de legumes.');
        $this->assertSame(8, (int) Produt::query()->findOrFail($productId)->quantity);
        $this->assertDatabaseCount('orders', 0);

        // Efetua um pedido válido e verifica preço, total, estado inicial e baixa de stock.
        $this->authenticateApiToken($customerToken);
        $orderResponse = $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'address' => 'Avenida Central, 20',
            'items' => [
                ['product_id' => $productId, 'quantity' => 2],
            ],
        ]);
        $orderResponse->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', '7.00')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.unit_price', '3.50')
            ->assertJsonPath('data.items.0.line_total', '7.00');
        $orderId = $orderResponse->json('data.id');
        $this->assertSame(6, (int) Produt::query()->findOrFail($productId)->quantity);

        // Autentica o funcionário recém-criado e acompanha o pedido até ao estado de conclusão.
        $staffLogin = $this->postJson('/api/auth/login', [
            'email' => 'rui.equipa@example.test',
            'password' => 'palavra-passe-segura-rui',
        ])->assertOk();
        $staffToken = $staffLogin->json('token');

        $this->authenticateApiToken($staffToken);
        $this->getJson('/api/restaurant/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $orderId)
            ->assertJsonPath('data.0.customer.name', 'Carlos Cliente');
        $this->authenticateApiToken($staffToken);
        $this->patchJson("/api/restaurant/orders/$orderId/status", [
            'status' => 'in_progress',
        ])->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
        $this->authenticateApiToken($staffToken);
        $this->patchJson("/api/restaurant/orders/$orderId/status", [
            'status' => 'completed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        // Confirma que o cliente acompanha o pedido concluído e que a base de dados mantém a entrega.
        $this->authenticateApiToken($customerToken);
        $this->getJson("/api/orders/$orderId")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.restaurant.name', 'Restaurante do Bairro');
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'customer_id' => $customerRegistration->json('user.id'),
            'restaurant_id' => $restaurantId,
            'payment_status' => 'completed',
            'payment_value' => '7.00',
        ]);
    }

    public function test_marketplace_api_rejects_unauthenticated_users_and_invalid_order_data(): void
    {
        // Cria os dados de referência para provar que as verificações são aplicadas a recursos reais.
        $restaurant = Restaurant::query()->create([
            'name' => 'Restaurante Protegido',
            'address' => 'Rua Protegida, 1',
            'phone_number' => '910000011',
            'nif' => '987654322',
            'status' => 'active',
        ]);
        $customer = User::factory()->create([
            'role' => UserRole::CUSTOMER->value,
        ]);
        $customerToken = $customer->createToken('test-customer')->plainTextToken;

        // Garante que operações internas não ficam acessíveis sem token ou com um perfil de cliente.
        $this->getJson('/api/categories')->assertUnauthorized();
        $this->postJson('/api/restaurants', [])->assertUnauthorized();
        $this->authenticateApiToken($customerToken);
        $this->postJson('/api/products', [])->assertForbidden();
        $this->authenticateApiToken($customerToken);
        $this->postJson('/api/restaurant/users', [])->assertForbidden();

        // Garante que o endpoint de pedidos apresenta erros por campo sem criar pedidos inválidos.
        $this->authenticateApiToken($customerToken);
        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurant->id,
            'address' => '',
            'items' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['address', 'items']);
        $this->assertDatabaseCount('orders', 0);

        // Uma conta de cliente não pode consultar o pedido pertencente a outro cliente.
        $otherCustomer = User::factory()->create([
            'role' => UserRole::CUSTOMER->value,
        ]);
        $ownedOrder = Order::query()->create([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'address' => 'Rua do Cliente, 9',
            'delivery_date' => now()->toDateString(),
            'payment_status' => 'pending',
            'payment_value' => '4.00',
        ]);
        $otherCustomerToken = $otherCustomer->createToken('test-other-customer')->plainTextToken;

        $this->authenticateApiToken($otherCustomerToken);
        $this->getJson("/api/orders/$ownedOrder->id")
            ->assertNotFound();
    }

    public function test_completed_order_cannot_be_reopened_after_delivery(): void
    {
        // Prepara um restaurante com produto, cliente e pedido já concluído para testar a regra terminal.
        $restaurant = Restaurant::query()->create([
            'name' => 'Restaurante Entrega',
            'address' => 'Rua Entrega, 2',
            'phone_number' => '910000012',
            'nif' => '987654323',
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'role' => UserRole::RESTAURANT_ADMIN->value,
            'restaurant_id' => $restaurant->id,
        ]);
        $customer = User::factory()->create([
            'role' => UserRole::CUSTOMER->value,
        ]);
        $order = Order::query()->create([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'address' => 'Rua do Cliente, 3',
            'delivery_date' => now()->toDateString(),
            'payment_status' => 'completed',
            'payment_value' => '12.00',
        ]);
        $adminToken = $admin->createToken('test-restaurant-admin')->plainTextToken;

        // A API deve rejeitar uma transição para trás e preservar o estado final já entregue.
        $this->authenticateApiToken($adminToken);
        $this->patchJson("/api/restaurant/orders/$order->id/status", [
            'status' => 'in_progress',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Transição de estado do pedido inválida.');
        $this->assertSame('completed', $order->fresh()->payment_status);
    }

    /**
     * Limpa o utilizador autenticado anteriormente antes de definir o token da próxima chamada API.
     */
    private function authenticateApiToken(string $token): void
    {
        // O cliente de testes reutiliza o guard entre pedidos; limpá-lo simula uma nova requisição autenticada.
        $this->app['auth']->forgetGuards();
        $this->withToken($token);
    }
}
