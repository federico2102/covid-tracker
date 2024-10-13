<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\InfectionReport;
use App\Models\User;
use App\Models\Location;
use Tests\Support\UserTestHelper;
use Tests\Support\LocationTestHelper;
use Tests\Support\CheckInTestHelper;
use Tests\Support\AssertionHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class InfectionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_positive_infection()
    {
        $user = UserTestHelper::createNonAdminUser();

        $response = $this->actingAs($user)->post(route('infectionReports.store'), [
            'test_date' => now()->subDays(1)->format('Y-m-d'),
            'proof' => null,  // No proof uploaded
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('infection_reports', ['user_id' => $user->id, 'is_active' => true]);

        // Fetch fresh user data and assert that is_infected is 1 (representing true in the database)
        $this->assertEquals(1, $user->fresh()->is_infected);
    }


    public function test_user_can_report_negative_test()
    {
        $user = UserTestHelper::createNonAdminUser();
        InfectionReport::create([
            'user_id' => $user->id,
            'test_date' => now()->subDays(10)->format('Y-m-d'),
            'proof' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('infectionReports.negative'), [
            'proof' => null, // Optional proof of negative test
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('infection_reports', ['user_id' => $user->id, 'is_active' => false]);

        // Assert that 'is_infected' is 0 (representing false in the database)
        $this->assertEquals(0, $user->fresh()->is_infected);
    }

    public function test_users_in_same_location_receive_notifications()
    {
        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        // Check-in both users at the same location within a week of the positive test
        CheckInTestHelper::checkInUser($this->actingAs($infectedUser), $location->id, now()->subDays(4), $infectedUser->id);  // 3 days ago
        CheckInTestHelper::checkInUser($this->actingAs($contactedUser), $location->id, now()->subDays(4), $contactedUser->id);  // 4 days ago

        // Simulate auto-checkout for users who have been checked in for more than 3 hours
        CheckInTestHelper::simulateAutoCheckout();

        // Report infection for the infected user
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), [
            'test_date' => now()->format('Y-m-d'),
            'proof' => null,
        ]);

        // Assert that a notification was created for the contacted user
        $this->assertDatabaseHas('notifications', [
            'user_id' => $contactedUser->id,
            'type' => 'contact',
            'is_read' => false,
        ]);
    }

    public function test_infected_or_contacted_user_cannot_check_in()
    {
        $infectedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        // Report infection without middleware
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), [
            'test_date' => now()->format('Y-m-d'),
            'proof' => null,
        ]);

        // Refresh the user to ensure the infection status is updated
        $infectedUser->refresh();

        // Try to check in without middleware
        $response = $this->withoutMiddleware()->actingAs($infectedUser)->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $location->id,
        ]);

        // Assert that the user cannot check in
        AssertionHelper::assertForbiddenResponse($response, 'You cannot check in because you are infected.');
    }



    public function test_automatic_reset_after_14_days()
    {
        // Create an infected user with an active infection report
        $user = UserTestHelper::createNonAdminUser();
        $user->update(['is_infected' => true]);
        InfectionReport::create([
            'user_id' => $user->id,
            'test_date' => now()->subDays(15),  // 15 days ago
            'is_active' => true,
        ]);

        // Simulate running the command that resets the infection status after 14 days
        $this->artisan('checkin:auto-reset-infected-status')->assertExitCode(0);

        // Ensure the user is marked as healthy
        $this->assertEquals(0, $user->fresh()->is_infected);

        // Ensure the latest infection report is inactive
        $this->assertDatabaseHas('infection_reports', ['user_id' => $user->id, 'is_active' => false]);
    }

    public function test_contacted_users_are_marked_correctly()
    {
        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        // Check-in both users at the same location within a week of the positive test
        CheckInTestHelper::checkInUser($this->actingAs($infectedUser), $location->id, now()->subDays(4), $infectedUser->id);  // 3 days ago
        CheckInTestHelper::checkInUser($this->actingAs($contactedUser), $location->id, now()->subDays(4), $contactedUser->id);  // 4 days ago

        // Simulate auto-checkout for users who have been checked in for more than 3 hours
        CheckInTestHelper::simulateAutoCheckout();

        // Report infection for the infected user
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), [
            'test_date' => now()->format('Y-m-d'),
            'proof' => null,
        ]);

        // Assert that the contacted user is marked as contacted
        $this->assertEquals(1, $contactedUser->fresh()->is_contacted);
    }

}
