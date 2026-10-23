<?php

namespace Tests\Feature;

use App\Actions\Users\ManageAuthenticationAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\TransientToken;
use Tests\TestCase;

class ApiLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_revokes_only_the_current_api_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current');
        $other = $user->createToken('other');

        $this->withToken($current->plainTextToken)->postJson('/api/auth/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $current->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_logout_accepts_a_user_without_an_api_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('other-device');

        $this->app->make(ManageAuthenticationAction::class)->logout($user);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout_accepts_a_transient_spa_token_without_revoking_other_devices(): void
    {
        $user = User::factory()->create();
        $user->createToken('other-device');
        $user->withAccessToken(new TransientToken);

        $this->app->make(ManageAuthenticationAction::class)->logout($user);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}
