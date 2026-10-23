<?php

namespace Tests\Feature;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\EditCategoryAction;
use App\Actions\Orders\ManageOrdersAction;
use App\Actions\Products\CreateProductAction;
use App\Actions\Products\DeactivateProductAction;
use App\Actions\Products\UpdateProductAction;
use App\Actions\Restaurants\CreateRestaurantAction;
use App\Actions\Restaurants\DeactivateRestaurantAction;
use App\Actions\Restaurants\UpdateRestaurantAction;
use App\Domains\Categories\Data\CreateCategoryData;
use App\Domains\Categories\Data\EditCategoryData;
use App\Domains\Categories\Exceptions\CategoryException;
use App\Domains\Orders\Data\CreateOrderData;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Exceptions\OrderException;
use App\Domains\Products\Data\ProductData;
use App\Domains\Products\Exceptions\ProductException;
use App\Domains\Restaurants\Data\CreateRestaurantData;
use App\Domains\Restaurants\Data\UpdateRestaurantData;
use App\Domains\Restaurants\Exceptions\RestaurantException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Order_iten;
use App\Models\Produt;
use App\Models\Restaurant;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

class PersistenceFailureTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    #[DataProvider('catalogWrites')]
    public function test_catalog_write_failures_preserve_data_and_expose_the_original_cause(string $domain, string $operation, string $model, string $event, string $exceptionClass): void
    {
        $restaurant = $this->restaurant();
        $admin = $this->restaurantUser($restaurant);
        $product = $this->product($restaurant);
        $categoryId = $product->category_id;
        $categoryCount = Category::query()->count();
        $userCount = User::query()->count();

        $write = match ("$domain.$operation") {
            'category.create' => fn () => $this->app->make(CreateCategoryAction::class)->execute($restaurant->id, new CreateCategoryData('Bebidas')),
            'category.update' => fn () => $this->app->make(EditCategoryAction::class)->execute($restaurant->id, new EditCategoryData(name: 'Alterada'), $categoryId),
            'category.deactivate' => fn () => $this->app->make(DeleteCategoryAction::class)->execute($restaurant->id, $categoryId),
            'product.create' => fn () => $this->app->make(CreateProductAction::class)->execute($restaurant->id, new ProductData(name: 'Novo', type: 'comida', price: 2, quantity: 5, categoryId: $categoryId)),
            'product.update' => fn () => $this->app->make(UpdateProductAction::class)->execute($restaurant->id, $product->id, new ProductData(name: 'Alterado')),
            'product.deactivate' => fn () => $this->app->make(DeactivateProductAction::class)->execute($restaurant->id, $product->id),
            'restaurant.create' => fn () => $this->app->make(CreateRestaurantAction::class)->execute(new CreateRestaurantData('Novo', 'Rua Nova', '910000001', '123456789', 'Admin', 'novo@example.test', 'secure-password-123')),
            'restaurant.update' => fn () => $this->app->make(UpdateRestaurantAction::class)->execute($admin, $restaurant->id, new UpdateRestaurantData(name: 'Alterado')),
            'restaurant.deactivate' => fn () => $this->app->make(DeactivateRestaurantAction::class)->execute($admin, $restaurant->id),
        };

        $this->rejectModelWrite($model, $event, function () use ($write, $exceptionClass): void {
            $this->assertDomainFailure($write, $exceptionClass);
        });

        $this->assertDatabaseCount('restaurants', 1);
        $this->assertDatabaseCount('users', $userCount);
        $this->assertDatabaseCount('categories', $categoryCount);
        $this->assertDatabaseCount('produts', 1);
        $this->assertSame('Pratos', Category::query()->findOrFail($categoryId)->name);
        $this->assertSame('Sopa', $product->fresh()->name);
        $this->assertSame('active', $product->fresh()->status);
        $this->assertSame('Restaurante Teste', $restaurant->fresh()->name);
        $this->assertSame('active', $restaurant->fresh()->status);
    }

    public static function catalogWrites(): array
    {
        return [
            'category creation' => ['category', 'create', Category::class, 'creating', CategoryException::class],
            'category update' => ['category', 'update', Category::class, 'updating', CategoryException::class],
            'category deactivation' => ['category', 'deactivate', Category::class, 'updating', CategoryException::class],
            'product creation' => ['product', 'create', Produt::class, 'creating', ProductException::class],
            'product update' => ['product', 'update', Produt::class, 'updating', ProductException::class],
            'product deactivation' => ['product', 'deactivate', Produt::class, 'updating', ProductException::class],
            'restaurant creation' => ['restaurant', 'create', Restaurant::class, 'creating', RestaurantException::class],
            'admin creation rolls back restaurant' => ['restaurant', 'create', User::class, 'creating', RestaurantException::class],
            'restaurant update' => ['restaurant', 'update', Restaurant::class, 'updating', RestaurantException::class],
            'restaurant deactivation' => ['restaurant', 'deactivate', Restaurant::class, 'updating', RestaurantException::class],
        ];
    }

    #[DataProvider('orderCreationWrites')]
    public function test_order_creation_rolls_back_stock_items_and_order_if_any_write_is_rejected(string $model, string $event): void
    {
        $restaurant = $this->restaurant();
        $product = $this->product($restaurant);
        $customer = User::factory()->create();
        $data = new CreateOrderData($restaurant->id, 'Rua Cliente', [['product_id' => $product->id, 'quantity' => 2]]);

        $this->rejectModelWrite($model, $event, function () use ($customer, $data): void {
            $this->assertDomainFailure(fn () => $this->app->make(ManageOrdersAction::class)->create($customer, $data), OrderException::class);
        });

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_itens', 0);
        $this->assertSame(8, $product->fresh()->quantity);
    }

    public static function orderCreationWrites(): array
    {
        return [
            'order insert' => [Order::class, 'creating'],
            'stock deduction' => [Produt::class, 'updating'],
            'item insert' => [Order_iten::class, 'creating'],
            'order total update' => [Order::class, 'updating'],
        ];
    }

    #[DataProvider('orderCancellationWrites')]
    public function test_failed_cancellation_never_leaves_restored_stock_on_a_pending_order(string $model, bool $byCustomer): void
    {
        $restaurant = $this->restaurant();
        $product = $this->product($restaurant, 6);
        $customer = User::factory()->create();
        $order = $this->order($restaurant, $customer, $product);

        $this->rejectModelWrite($model, 'updating', function () use ($byCustomer, $restaurant, $customer, $order): void {
            $action = $this->app->make(ManageOrdersAction::class);
            $this->assertDomainFailure(
                fn () => $byCustomer ? $action->cancelByCustomer($customer->id, $order->id) : $action->updateStatus($restaurant->id, $order->id, OrderStatus::CANCELLED),
                OrderException::class,
            );
        });

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(6, $product->fresh()->quantity);
    }

    public static function orderCancellationWrites(): array
    {
        return [
            'customer stock restore' => [Produt::class, true],
            'customer status update' => [Order::class, true],
            'staff stock restore' => [Produt::class, false],
            'staff status update' => [Order::class, false],
        ];
    }

    private function rejectModelWrite(string $model, string $event, Closure $test): void
    {
        $dispatcher = Model::getEventDispatcher();
        Model::setEventDispatcher(clone $dispatcher);
        $model::$event(fn () => false);
        try {
            $test();
        } finally {
            Model::setEventDispatcher($dispatcher);
        }
    }

    private function assertDomainFailure(Closure $operation, string $exceptionClass): void
    {
        try {
            $operation();
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertNotNull($exception->getPrevious());
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());

            return;
        }

        $this->fail('Uma gravação recusada não pode ser tratada como sucesso.');
    }
}
