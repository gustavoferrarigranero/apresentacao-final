<?php

namespace App\Console\Commands;

use App\Jobs\BuildDailyOrderSummary;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Enfileira o resumo de pedidos de uma data para execução por um worker.
 */
final class DispatchDailyOrderSummary extends Command
{
    protected $signature = 'marketplace:orders:summary {date? : Data no formato AAAA-MM-DD}';

    protected $description = 'Enfileira o resumo diário dos pedidos do marketplace';

    public function handle(): int
    {
        // Usa a data local do Laravel por omissão e rejeita datas que não sejam calendariamente válidas.
        $date = $this->argument('date') ?? now()->toDateString();
        if (! is_string($date) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) !== 1) {
            $this->components->error('Indique uma data válida no formato AAAA-MM-DD.');

            return self::INVALID;
        }

        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
            $this->components->error('Indique uma data válida no formato AAAA-MM-DD.');

            return self::INVALID;
        }

        BuildDailyOrderSummary::dispatch($date);
        Log::info('Resumo diário de pedidos colocado na fila.', [
            'summary_date' => $date,
            'queue' => 'reports',
        ]);

        $this->components->info("Resumo de pedidos de {$date} enviado para a fila reports.");

        return self::SUCCESS;
    }
}
