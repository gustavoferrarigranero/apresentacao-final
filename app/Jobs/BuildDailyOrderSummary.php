<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calcula e regista um resumo diário dos pedidos fora do ciclo HTTP.
 */
final class BuildDailyOrderSummary implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly string $date)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        // Agrupa os pedidos por estado para que operações de relatório não bloqueiem pedidos dos clientes.
        $ordersByStatus = DB::table('orders')
            ->selectRaw('payment_status, COUNT(*) AS total')
            ->whereDate('created_at', $this->date)
            ->groupBy('payment_status')
            ->orderBy('payment_status')
            ->pluck('total', 'payment_status')
            ->map(static fn ($total): int => (int) $total)
            ->all();

        Log::info('Resumo diário de pedidos processado.', [
            'summary_date' => $this->date,
            'orders_by_status' => $ordersByStatus,
            'orders_total' => array_sum($ordersByStatus),
        ]);
    }

    /**
     * Regista contexto suficiente para investigar falhas repetidas na fila de relatórios.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Falha ao processar o resumo diário de pedidos.', [
            'summary_date' => $this->date,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
