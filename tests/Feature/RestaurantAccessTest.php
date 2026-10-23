<?php

namespace Tests\Feature;

use App\Actions\Restaurants\ListRestaurantsAction;
use App\Domains\Restaurants\Data\RestaurantFiltersData;
use App\Domains\Users\Enums\UserRole;
use App\Models\User;
use App\Services\RestaurantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

class RestaurantAccessTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_public_and_customer_lists_hide_inactive_restaurants_and_apply_filters(): void
    {
        $visible = $this->restaurant();
        $this->restaurant('inactive');
        $this->getJson('/api/restaurants?per_page=1&name=Teste&id='.$visible->id)
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $visible->id);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $page = $this->app->make(ListRestaurantsAction::class)->execute($customer, new RestaurantFiltersData);
        $this->assertSame(1, $page->total());
        $this->assertSame($visible->id, $page->items()[0]->id);
    }

    public function test_global_admin_can_filter_all_restaurants_including_inactive_ones(): void
    {
        $visible = $this->restaurant();
        $hidden = $this->restaurant('inactive');
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $this->actingAs($admin)->getJson('/api/restaurants?per_page=1')->assertOk()
            ->assertJsonPath('meta.total', 2)->assertJsonCount(1, 'data');
        $this->getJson('/api/restaurants?id='.$hidden->id.'&name=Teste')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $hidden->id);
        $this->assertNotSame($visible->id, $hidden->id);
    }

    public function test_restaurant_admin_can_change_only_its_own_restaurant(): void
    {
        $owned = $this->restaurant();
        $other = $this->restaurant();
        $admin = $this->restaurantUser($owned);
        $this->actingAs($admin)->patchJson("/api/restaurants/$owned->id", ['name' => 'Novo nome'])
            ->assertOk()->assertJsonPath('data.name', 'Novo nome');
        $service = $this->app->make(RestaurantService::class);
        $this->assertTrue($service->show($admin, $owned->id)->is($owned));
        $this->patchJson("/api/restaurants/$other->id", ['name' => 'Invadido'])->assertNotFound();
        $this->deleteJson("/api/restaurants/$other->id")->assertNotFound();
        $this->assertSame('Restaurante Teste', $other->fresh()->name);
        $this->deleteJson("/api/restaurants/$owned->id")->assertOk()->assertJsonPath('data.status', 'inactive');
    }

    #[DataProvider('nonAdminRoles')]
    public function test_staff_and_customers_cannot_modify_restaurant_data(UserRole $role): void
    {
        $restaurant = $this->restaurant();
        $this->actingAs($this->restaurantUser($restaurant, $role));
        $this->patchJson("/api/restaurants/$restaurant->id", ['name' => 'Não permitido'])->assertForbidden();
        $this->deleteJson("/api/restaurants/$restaurant->id")->assertForbidden();
        $this->assertSame('active', $restaurant->fresh()->status);
    }

    public static function nonAdminRoles(): array
    {
        return [[UserRole::STAFF], [UserRole::CUSTOMER]];
    }

    public function test_inactive_and_missing_restaurants_return_not_found(): void
    {
        $restaurant = $this->restaurant('inactive');
        $this->getJson("/api/restaurants/$restaurant->id")->assertNotFound();
        $this->getJson('/api/restaurants/999999')->assertNotFound();
        $this->expectException(ModelNotFoundException::class);
        $this->app->make(RestaurantService::class)->show($this->restaurantUser($restaurant), 999999);
    }

    public function test_missing_categories_cannot_be_edited_or_deactivated(): void
    {
        $this->actingAs($this->restaurantUser($this->restaurant()));
        $this->patchJson('/api/categories/999999', ['name' => 'Inexistente'])->assertNotFound();
        $this->deleteJson('/api/categories/999999')->assertNotFound();
    }
}
