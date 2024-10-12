<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertionHelper;
use Tests\Support\CheckInTestHelper;
use Tests\Support\LocationTestHelper;
use Tests\Support\UserTestHelper;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_check_in_successfully()
    {
        $user = UserTestHelper::createUser();
        $location = LocationTestHelper::createLocation();

        $response = CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);

        // Use assertion helper for response
        AssertionHelper::assertSuccessfulResponse($response,
            route('checkin.success', ['location' => $location->id]), 'Check in was successful.');

        // Assert check-in is logged
        $this->assertDatabaseHas('check_ins', AssertionHelper::checkInExists($user, $location));

        // Assert people count increased
        $location->refresh();
        $this->assertEquals(1, $location->current_people);
    }

    public function test_infected_user_cannot_check_in()
    {
        $user = UserTestHelper::createUser(['is_infected' => true]);
        $location = LocationTestHelper::createLocation();

        $response = CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);

        // Use assertion helper for forbidden response
        AssertionHelper::assertForbiddenResponse($response, 'You cannot check in because you are infected.');

        // Assert the user's check-in was not logged
        $this->assertDatabaseMissing('check_ins', AssertionHelper::checkInExists($user, $location));
    }

    public function test_user_can_check_out_successfully()
    {
        $user = UserTestHelper::createUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);

        // Check the user out
        $response = CheckInTestHelper::checkOutUser($this->actingAs($user));

        // Use assertion helper for successful response
        AssertionHelper::assertSuccessfulResponse($response, route('home'));

        // Assert the user's check-out was logged in the database
        $this->assertDatabaseHas('check_ins', AssertionHelper::checkOutExists($user, $location));

        // Assert the location's current_people count has decreased
        $location->refresh();
        $this->assertEquals(0, $location->current_people);
    }

    public function test_auto_checkout_after_3_hours()
    {
        $user = UserTestHelper::createUser();
        $location = LocationTestHelper::createLocation(['current_people' => 1]);

        // Check the user in
        $checkInTime = Carbon::now()->subHours(4);
        DB::table('check_ins')->insert([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'check_in_time' => $checkInTime,
        ]);

        // Run the scheduled auto-checkout task
        $this->artisan('checkin:auto-checkout');

        // Assert the user was checked out
        $this->assertDatabaseHas('check_ins', [
            'user_id' => $user->id,
            'location_id' => $location->id,
            'check_in_time' => $checkInTime,
            'check_out_time' => now(),
        ]);

        // Assert the location's current_people count has decreased
        $location->refresh();
        $this->assertEquals(0, $location->current_people);
    }

    public function test_prevent_check_in_if_user_is_already_checked_in()
    {
        $user = UserTestHelper::createUser();
        $location = LocationTestHelper::createLocation();
        $location2 = LocationTestHelper::createLocation();

        // First check-in
        CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);

        // Attempt to check in again at the same location
        $response = CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);
        AssertionHelper::assertForbiddenResponse($response, 'You are already checked in here or at another location.');

        // Attempt to check in at another location
        $response = CheckInTestHelper::checkInUser($this->actingAs($user), $location2->id);
        AssertionHelper::assertForbiddenResponse($response, 'You are already checked in here or at another location.');

        // Assert there is a single active check-in
        $this->assertEquals(1, CheckIn::where('user_id', $user->id)->whereNull('check_out_time')->count());

        // Assert the current_people count remains the same
        $location->refresh();
        $location2->refresh();
        $this->assertEquals(1, $location->current_people);
        $this->assertEquals(0, $location2->current_people);
    }

    public function test_user_cannot_check_in_if_location_is_full()
    {
        $user = UserTestHelper::createUser();
        $location = LocationTestHelper::createLocation();
        $location->current_people = $location->max_capacity;
        $location->save();

        $response = CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);

        // Use assertion helper for full capacity response
        AssertionHelper::assertSuccessfulResponse($response, route('home'));
        $response->assertSessionHas('error', 'This location has reached its maximum capacity. Please try again later.');
    }

    public function test_user_can_check_in_after_checkout()
    {
        $user = UserTestHelper::createUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);
        CheckInTestHelper::checkOutUser($this->actingAs($user));

        // Check in again
        $response = CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);

        AssertionHelper::assertSuccessfulResponse($response, route('checkin.success', ['location' => $location->id]), 'Check in was successful.');

        // Assert two check-in entries in the database
        $this->assertDatabaseCount('check_ins', 2);

        // Assert second check-in has a null check_out_time
        $this->assertDatabaseHas('check_ins', AssertionHelper::checkInExists($user, $location));
    }

    public function test_check_in_requires_valid_location_id()
    {
        $user = UserTestHelper::createUser();

        // Attempt to check in with invalid data
        $response = CheckInTestHelper::checkInUser($this->actingAs($user), null);

        AssertionHelper::assertSuccessfulResponse($response, route('home'));
        $response->assertSessionHas('error', 'Invalid location.');
    }

    public function test_user_cannot_check_out_twice()
    {
        $user = UserTestHelper::createUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInUser($this->actingAs($user), $location->id);
        CheckInTestHelper::checkOutUser($this->actingAs($user));

        // Assert the first check-out was successful
        $this->assertDatabaseHas('check_ins', AssertionHelper::checkOutExists($user, $location));

        // Simulate user checking out again
        $response = CheckInTestHelper::checkOutUser($this->actingAs($user));
        AssertionHelper::assertForbiddenResponse($response, 'You are not checked in anywhere.');

        // Ensure no new check-out is registered
        $this->assertDatabaseCount('check_ins', 1);
    }

    public function test_user_cannot_check_in_without_qr_code()
    {
        $user = UserTestHelper::createUser();

        // Attempt to check in without a qr_code parameter
        $response = $this->actingAs($user)->withoutMiddleware()->post(route('checkin.process'));

        AssertionHelper::assertSuccessfulResponse($response, route('home'));
        $response->assertSessionHas('error', 'Invalid location.');
    }

    public function test_user_cannot_check_in_when_logged_out()
    {
        $location = LocationTestHelper::createLocation();

        // Attempt to check in while not authenticated
        $response = $this->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $location->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_cannot_check_out_without_check_in()
    {
        $user = UserTestHelper::createUser();

        // Simulate a checkout attempt without check-in
        $response = CheckInTestHelper::checkOutUser($this->actingAs($user));

        AssertionHelper::assertForbiddenResponse($response, 'You are not checked in anywhere.');
    }

    public function test_admin_cannot_check_in_if_infected()
    {
        $admin = UserTestHelper::createUser(['is_infected' => true, 'is_admin' => true]);
        $location = LocationTestHelper::createLocation();

        // Attempt to check in as an infected admin
        $response = CheckInTestHelper::checkInUser($this->actingAs($admin), $location->id);

        AssertionHelper::assertForbiddenResponse($response, 'You cannot check in because you are infected.');
    }

}
