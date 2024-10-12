<?php


namespace Tests\Support;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UserTestHelper;
use Tests\Support\AssertionHelper;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_role()
    {
        $admin = UserTestHelper::createAdminUser();
        $user = UserTestHelper::createNonAdminUser();

        $response = UserTestHelper::updateUserRoleRequest($this->actingAs($admin), $user->id, ['is_admin' => true]);

        AssertionHelper::assertSuccessfulResponse($response, route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => true]);
    }

    public function test_user_can_update_own_profile()
    {
        $user = UserTestHelper::createNonAdminUser();

        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'email' => 'newemail@example.com',
            'phone' => '1234567890',
        ]);

        AssertionHelper::assertSuccessfulResponse($response, route('profile.show'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'newemail@example.com']);
    }

    public function test_non_admin_cannot_update_other_users()
    {
        $user = UserTestHelper::createNonAdminUser();
        $otherUser = UserTestHelper::createNonAdminUser();

        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'email' => 'unauthorized@example.com',
        ], $otherUser->id);

        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');
        $this->assertDatabaseMissing('users', ['id' => $otherUser->id, 'email' => 'unauthorized@example.com']);
    }

    public function test_profile_update_form_validation()
    {
        $user = UserTestHelper::createNonAdminUser();

        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'email' => 'invalid-email', // Invalid email format
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'phone']);
    }
}
