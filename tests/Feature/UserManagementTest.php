<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\AssertionHelper;
use Tests\Support\UserTestHelper;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_role_and_non_admin_cannot()
    {
        $admin = UserTestHelper::createAdminUser();
        $user = UserTestHelper::createNonAdminUser();

        // Admin can update user role
        $response = UserTestHelper::updateUserRoleRequest($this->actingAs($admin), $user->id, ['is_admin' => true]);
        AssertionHelper::assertSuccessfulResponse($response, route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => true]);

        // Non-admin cannot update user role
        $nonAdmin = UserTestHelper::createNonAdminUser();
        $response = UserTestHelper::updateUserRoleRequest($this->actingAs($nonAdmin), $user->id, ['is_admin' => true]);
        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');
    }

    public function test_user_can_update_own_profile_and_password()
    {
        $user = UserTestHelper::createNonAdminUser();

        // Update profile
        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'email' => 'newemail@example.com',
            'phone_number' => '1234567890',
        ], $user->id);
        AssertionHelper::assertSuccessfulResponse($response, route('profile.show', $user->id));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'newemail@example.com']);

        // Update password
        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ], $user->id);
        AssertionHelper::assertSuccessfulResponse($response, route('profile.show', $user->id));
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_invalid_profile_update_and_password_length()
    {
        $user = UserTestHelper::createNonAdminUser();

        // Invalid email format
        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'email' => 'invalid-email',
        ], $user->id);
        $response->assertSessionHasErrors(['email']);

        // Invalid password length
        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ], $user->id);
        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_cannot_revoke_own_admin_privileges()
    {
        $admin = UserTestHelper::createAdminUser();

        // Try to make the admin a non-admin
        $response = UserTestHelper::updateUserRoleRequest($this->actingAs($admin), $admin->id, ['is_admin' => false]);

        // Assert that the response redirects to the users list and contains the correct error message
        $response->assertStatus(302);
        $response->assertSessionHas('error', 'You cannot revoke your own admin privileges.');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_admin' => true]);
    }

    public function test_admin_can_view_user_list_and_non_admin_cannot()
    {
        $admin = UserTestHelper::createAdminUser();
        $user = UserTestHelper::createNonAdminUser();

        // Admin can view the user list
        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertViewIs('users.index');
        $response->assertSee($admin->email);
        $response->assertSee($user->email);

        // Non-admin cannot view the user list
        $response = $this->actingAs($user)->get(route('users.index'));
        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');
    }

    public function test_email_uniqueness_on_profile_update()
    {
        UserTestHelper::createNonAdminUser(['email' => 'user1@example.com']);
        $user2 = UserTestHelper::createNonAdminUser(['email' => 'user2@example.com']);

        // Try to update user2's email to user1's email
        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user2), [
            'email' => 'user1@example.com',
        ], $user2->id);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_update_own_profile()
    {
        $admin = UserTestHelper::createAdminUser();
        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($admin), [
            'email' => 'admin@example.com',
            'phone_number' => '9876543210',
        ], $admin->id);

        AssertionHelper::assertSuccessfulResponse($response, route('profile.show', $admin->id));
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'email' => 'admin@example.com']);
    }

    public function test_admin_can_update_user_role_to_non_admin()
    {
        $admin = UserTestHelper::createAdminUser();
        $user = UserTestHelper::createAdminUser();  // Create another admin user

        // Admin updates another admin to non-admin
        $response = UserTestHelper::updateUserRoleRequest($this->actingAs($admin), $user->id, ['is_admin' => false]);

        // Assert success and role change in the database
        AssertionHelper::assertSuccessfulResponse($response, route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => false]);
    }

    public function test_non_admin_cannot_grant_themselves_admin_privileges()
    {
        $user = UserTestHelper::createNonAdminUser();

        $response = UserTestHelper::updateUserProfileRequest($this->actingAs($user), [
            'email' => 'user@example.com',
            'is_admin' => true,  // Attempt to give themselves admin privileges
        ], $user->id);

        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => false]);
    }
}
