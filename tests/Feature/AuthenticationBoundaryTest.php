<?php

namespace Tests\Feature;

use App\Actions\Users\CreateRestaurantStaffAction;
use App\Domains\Users\Data\CreateStaffData;
use App\Domains\Users\Enums\UserRole;
use App\Http\Middleware\EnsureUserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuthenticationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_credentials_do_not_issue_tokens(): void
    {
        User::factory()->create(['email' => 'customer@example.test', 'password' => 'correct-password']);
        foreach (['missing@example.test', 'customer@example.test'] as $email) {
            $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'incorrect-password'])
                ->assertUnprocessable()->assertJsonValidationErrors('email');
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_account_cannot_log_in_even_with_the_correct_password(): void
    {
        $user = User::factory()->create(['status' => 'inactive', 'password' => 'correct-password']);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'correct-password'])
            ->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_password_reset_keeps_the_existing_password_and_tokens(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $user->createToken('existing-device');
        $hash = $user->password;
        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email, 'token' => 'invalid-token',
            'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_an_admin_without_a_restaurant_cannot_create_staff_through_the_action(): void
    {
        $admin = User::factory()->create(['role' => UserRole::RESTAURANT_ADMIN->value, 'restaurant_id' => null]);
        $this->expectException(AuthorizationException::class);
        $this->app->make(CreateRestaurantStaffAction::class)->execute($admin, new CreateStaffData('Staff', 'staff@example.test', 'secure-password'));
    }

    public function test_role_middleware_rejects_a_guest_and_allows_an_authorized_user(): void
    {
        $middleware = new EnsureUserRole;
        $request = Request::create('/restricted');
        $request->setUserResolver(fn () => null);
        $next = fn () => response('Allowed');
        $this->assertSame(403, $middleware->handle($request, $next, 'staff')->getStatusCode());
        $request->setUserResolver(fn () => new User(['role' => 'staff']));
        $response = $middleware->handle($request, $next, 'staff');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Allowed', $response->getContent());
    }
}
