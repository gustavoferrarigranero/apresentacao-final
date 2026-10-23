<?php

namespace Tests\Feature;

use App\Jobs\BuildDailyOrderSummary;
use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class OperationalFailureTest extends TestCase
{
    public function test_exhausted_report_job_logs_its_date_and_original_error(): void
    {
        Log::shouldReceive('error')->once()->with('Falha ao processar o resumo diário de pedidos.', [
            'summary_date' => '2026-10-22', 'exception' => RuntimeException::class, 'message' => 'Connection lost',
        ]);
        (new BuildDailyOrderSummary('2026-10-22'))->failed(new RuntimeException('Connection lost'));
    }

    #[DataProvider('invalidDates')]
    public function test_malformed_report_dates_never_dispatch_a_job(string $date): void
    {
        Queue::fake();
        $this->artisan('marketplace:orders:summary', ['date' => $date])->assertExitCode(2);
        Queue::assertNothingPushed();
    }

    public static function invalidDates(): array
    {
        return [['today'], ['22-10-2026'], ['2026-1-01'], ['2026-10-22extra']];
    }

    public function test_production_password_policy_rejects_weak_passwords_and_accepts_a_strong_one(): void
    {
        $environment = $this->app['env'];
        $this->app['env'] = 'production';
        $verifier = $this->mock(UncompromisedVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturnTrue();
        try {
            (new AppServiceProvider($this->app))->boot();
            foreach (['short', 'longbutlowercaseonly', 'LongButNoDigitsOrSymbols', 'LongPassword1234'] as $password) {
                $this->assertTrue(Validator::make(['password' => $password], ['password' => [Password::defaults()]])->fails());
            }
            $this->assertTrue(Validator::make(['password' => 'Strong-Password-2026!'], ['password' => [Password::defaults()]])->passes());
        } finally {
            $this->app['env'] = $environment;
            (new AppServiceProvider($this->app))->boot();
        }
    }
}
