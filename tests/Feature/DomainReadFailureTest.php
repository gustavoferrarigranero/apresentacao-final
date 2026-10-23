<?php

namespace Tests\Feature;

use App\Actions\Categories\ListCategoriesAction;
use App\Actions\Orders\ManageOrdersAction;
use App\Actions\Products\ListProductsAction;
use App\Actions\Restaurants\ListRestaurantsAction;
use App\Domains\Categories\Data\CategoryFiltersData;
use App\Domains\Categories\Exceptions\CategoryException;
use App\Domains\Orders\Exceptions\OrderException;
use App\Domains\Products\Exceptions\ProductException;
use App\Domains\Restaurants\Data\RestaurantFiltersData;
use App\Domains\Restaurants\Exceptions\RestaurantException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

class DomainReadFailureTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    #[DataProvider('readOperations')]
    public function test_read_errors_keep_the_original_cause_and_do_not_leave_the_connection_unusable(string $operation, string $exceptionClass): void
    {
        $restaurant = $this->restaurant();
        $user = $this->restaurantUser($restaurant);
        $read = match ($operation) {
            'category index' => fn () => $this->app->make(ListCategoriesAction::class)->execute($restaurant->id, new CategoryFiltersData),
            'product index' => fn () => $this->app->make(ListProductsAction::class)->execute($restaurant->id, null, 10, true),
            'product detail' => fn () => $this->app->make(ListProductsAction::class)->find($restaurant->id, 1, true),
            'restaurant index' => fn () => $this->app->make(ListRestaurantsAction::class)->execute(null, new RestaurantFiltersData),
            'restaurant detail' => fn () => $this->app->make(ListRestaurantsAction::class)->find($user, $restaurant->id),
            'public restaurant detail' => fn () => $this->app->make(ListRestaurantsAction::class)->findPublic($restaurant->id),
            'customer order detail' => fn () => $this->app->make(ManageOrdersAction::class)->findForCustomer($user->id, 1),
        };

        $failure = new RuntimeException('Database unavailable');
        $reject = true;
        DB::connection()->beforeExecuting(function () use (&$reject, $failure): void {
            if ($reject) {
                throw $failure;
            }
        });
        try {
            $read();
            $this->fail('A indisponibilidade da base de dados deve ser propagada.');
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertSame($failure, $exception->getPrevious());
        } finally {
            $reject = false;
        }

        $this->assertDatabaseCount('restaurants', 1);
    }

    public static function readOperations(): array
    {
        return [
            ['category index', CategoryException::class],
            ['product index', ProductException::class],
            ['product detail', ProductException::class],
            ['restaurant index', RestaurantException::class],
            ['restaurant detail', RestaurantException::class],
            ['public restaurant detail', RestaurantException::class],
            ['customer order detail', OrderException::class],
        ];
    }
}
