<?php

namespace Tests\Feature;

use App\Domains\Users\Enums\UserRole;
use App\Events\OrderStatusUpdated;
use App\Jobs\BuildDailyOrderSummary;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifica versionamento, autorização de canais, identificação de pedidos e execução operacional.
 */
class OperationsAndObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_versioned_api_alias_and_legacy_api_are_both_available(): void
    {
        // A rota versionada e a rota antiga devem responder com o mesmo catálogo público.
        $this->getJson('/api/v1/restaurants')->assertOk();
        $this->getJson('/api/restaurants')->assertOk();
    }

    public function test_api_responses_include_a_safe_request_correlation_id(): void
    {
        // Um UUID enviado pelo cliente é propagado; um valor não UUID é substituído por outro seguro.
        $validId = 'f16c58f6-c938-4c8e-9e29-340b5ea3e8a2';
        $this->withHeader('X-Request-Id', $validId)
            ->getJson('/api/v1/restaurants')
            ->assertOk()
            ->assertHeader('X-Request-Id', $validId);

        $response = $this->withHeader('X-Request-Id', 'injected-log-content')
            ->getJson('/api/v1/restaurants')
            ->assertOk();

        $this->assertNotSame('injected-log-content', $response->headers->get('X-Request-Id'));
        $this->assertTrue(Str::isUuid($response->headers->get('X-Request-Id')));
    }

    public function test_only_global_administrators_can_view_pulse_metrics(): void
    {
        // Mantém métricas de utilização e de infraestrutura fora do alcance de clientes e equipas.
        $administrator = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
        ]);
        $customer = User::factory()->create([
            'role' => UserRole::CUSTOMER->value,
        ]);

        $this->assertTrue(Gate::forUser($administrator)->allows('viewPulse'));
        $this->assertFalse(Gate::forUser($customer)->allows('viewPulse'));
    }

    public function test_private_broadcast_channels_are_limited_to_the_order_participants(): void
    {
        // Usa o driver Pusher compatível do Reverb para executar as callbacks reais de autorização.
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        Broadcast::purge('reverb');
        require base_path('routes/channels.php');

        // Monta uma equipa do restaurante e um cliente para validar autorização entre canais privados.
        $restaurant = Restaurant::query()->create([
            'name' => 'Restaurante em Tempo Real',
            'address' => 'Rua do Reverb, 1',
            'phone_number' => '910000099',
            'nif' => '987654329',
            'status' => 'active',
        ]);
        $staff = User::factory()->create([
            'role' => UserRole::STAFF->value,
            'restaurant_id' => $restaurant->id,
        ]);
        $customer = User::factory()->create([
            'role' => UserRole::CUSTOMER->value,
        ]);

        // Os membros só subscrevem os canais que correspondem à sua conta e ao seu restaurante.
        $this->authenticateApiToken($staff->createToken('staff')->plainTextToken)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-restaurant.'.$restaurant->id,
            ])
            ->assertOk();
        $this->authenticateApiToken($staff->createToken('staff-other')->plainTextToken)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-orders.'.$customer->id,
            ])
            ->assertForbidden();
        $this->authenticateApiToken($customer->createToken('customer')->plainTextToken)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-orders.'.$customer->id,
            ])
            ->assertOk();
    }

    public function test_daily_order_summary_command_validates_and_dispatches_to_reports_queue(): void
    {
        // Substitui a fila real para verificar que o comando não bloqueia o processo terminal.
        Queue::fake();

        $this->artisan('marketplace:orders:summary', ['date' => '2026-10-22'])
            ->assertSuccessful();
        Queue::assertPushed(BuildDailyOrderSummary::class, fn (BuildDailyOrderSummary $job): bool => $job->date === '2026-10-22'
            && $job->queue === 'reports');

        // Datas impossíveis são rejeitadas antes de qualquer trabalho ser colocado na fila.
        $this->artisan('marketplace:orders:summary', ['date' => '2026-02-30'])
            ->assertFailed();
        Queue::assertPushed(BuildDailyOrderSummary::class, 1);
    }

    public function test_order_status_event_targets_private_restaurant_and_customer_channels(): void
    {
        // Confirma o contrato de broadcasting consumido pelo front-end sem exigir um servidor Reverb.
        $event = new OrderStatusUpdated(14, 5, 9, 'in_progress');

        $this->assertSame(
            ['private-restaurant.5', 'private-orders.9'],
            array_map(static fn ($channel): string => $channel->name, $event->broadcastOn()),
        );
        $this->assertSame([
            'order_id' => 14,
            'restaurant_id' => 5,
            'status' => 'in_progress',
        ], $event->broadcastWith());
    }

    public function test_daily_order_summary_job_logs_counts_for_the_requested_date(): void
    {
        // Cria um pedido persistido com data controlada para verificar o processamento real do job.
        $restaurant = Restaurant::query()->create([
            'name' => 'Restaurante de Relatórios',
            'address' => 'Rua dos Dados, 4',
            'phone_number' => '910000098',
            'nif' => '987654328',
            'status' => 'active',
        ]);
        $customer = User::factory()->create([
            'role' => UserRole::CUSTOMER->value,
        ]);
        Order::query()->create([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'address' => 'Rua do Cliente, 5',
            'delivery_date' => '2026-10-22',
            'payment_status' => 'pending',
            'payment_value' => '8.50',
            'created_at' => '2026-10-22 12:00:00',
            'updated_at' => '2026-10-22 12:00:00',
        ]);

        // Verifica o resumo operacional sem depender de um agregador ou saída de log externo.
        Log::shouldReceive('info')->once()->with('Resumo diário de pedidos processado.', [
            'summary_date' => '2026-10-22',
            'orders_by_status' => ['pending' => 1],
            'orders_total' => 1,
        ]);

        (new BuildDailyOrderSummary('2026-10-22'))->handle();
    }

    /**
     * Simula pedidos de API independentes sem reutilizar o utilizador guardado no guard Sanctum.
     */
    private function authenticateApiToken(string $token): static
    {
        // Limpa a identidade anterior antes de configurar o cabeçalho Bearer da chamada seguinte.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
