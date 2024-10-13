<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UserTestHelper;
use Tests\Support\LocationTestHelper;
use Tests\Support\CheckInTestHelper;
use Tests\Support\AssertionHelper;
use Tests\Support\InfectionReportTestHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Mail\ContactNotificationMail;

class InfectionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_positive_and_negative_infection()
    {
        $user = UserTestHelper::createNonAdminUser();

        // Positive test
        $response = InfectionReportTestHelper::reportPositiveTest($this->actingAs($user), now()->subDays(1)->format('Y-m-d'));
        $response->assertStatus(302);
        $this->assertDatabaseHas('infection_reports', ['user_id' => $user->id, 'is_active' => true]);
        $this->assertEquals(1, $user->fresh()->is_infected);

        // Negative test
        InfectionReportTestHelper::createInfectionReport($user, now()->subDays(10)->format('Y-m-d'));
        $response = InfectionReportTestHelper::reportNegativeTest($this->actingAs($user));
        $response->assertStatus(302);
        $this->assertDatabaseHas('infection_reports', ['user_id' => $user->id, 'is_active' => false]);
        $this->assertEquals(0, $user->fresh()->is_infected);
    }

    public function test_users_in_same_location_receive_notifications()
    {
        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(4));
        CheckInTestHelper::checkInWithDate($contactedUser, $location->id, now()->subDays(4));

        CheckInTestHelper::simulateAutoCheckout();
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $contactedUser->id,
            'type' => 'contact',
            'is_read' => false,
        ]);
    }

    public function test_infected_or_contacted_user_cannot_check_in()
    {
        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(3));
        CheckInTestHelper::checkInWithDate($contactedUser, $location->id, now()->subDays(3));
        CheckInTestHelper::simulateAutoCheckout();

        // Report infection for infected user
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser));

        // Infected user cannot check in
        $response = $this->actingAs($infectedUser)->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $location->id,
        ]);
        AssertionHelper::assertForbiddenResponse($response, 'You cannot check in because you are infected.');

        // Contacted user cannot check in
        $this->assertEquals(1, $contactedUser->refresh()->is_contacted);
        $response = $this->actingAs($contactedUser)->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $location->id,
        ]);
        AssertionHelper::assertForbiddenResponse($response, 'You cannot check in because you were in contact with an infected individual.');
    }

    public function test_reset_status_after_14_days()
    {
        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        // Infected and contacted users check in
        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(15));
        CheckInTestHelper::checkInWithDate($contactedUser, $location->id, now()->subDays(15));

        // Simulate auto-checkout and report infection
        CheckInTestHelper::simulateAutoCheckout();
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser), now()->subDays(14));

        // Reset status after 14 days
        InfectionReportTestHelper::resetInfectedStatus($this);
        $this->assertEquals(0, $infectedUser->fresh()->is_infected);
        $this->assertEquals(0, $contactedUser->fresh()->is_contacted);
    }

    public function test_email_is_sent_to_contacted_user()
    {
        Mail::fake();

        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(4));
        CheckInTestHelper::checkInWithDate($contactedUser, $location->id, now()->subDays(4));

        CheckInTestHelper::simulateAutoCheckout();
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser));

        Mail::assertQueued(ContactNotificationMail::class, function ($mail) use ($contactedUser) {
            return $mail->hasTo($contactedUser->email);
        });
    }

    public function test_no_email_sent_if_no_contacted_users()
    {
        Mail::fake();
        $infectedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(4));
        CheckInTestHelper::simulateAutoCheckout();

        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser));
        Mail::assertNothingQueued();
    }

    public function test_notification_is_not_duplicated_for_multiple_contacts()
    {
        Mail::fake();
        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(4));
        CheckInTestHelper::checkInWithDate($contactedUser, $location->id, now()->subDays(4));
        CheckInTestHelper::checkInWithDate($contactedUser, $location->id, now()->subDays(3));

        CheckInTestHelper::simulateAutoCheckout();
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser));

        $this->assertCount(1, $contactedUser->notifications()->where('type', 'contact')->get());
        Mail::assertQueued(ContactNotificationMail::class, 1);
    }

    public function test_contacted_user_reports_positive_test()
    {
        $infectedUser = UserTestHelper::createNonAdminUser();
        $contactedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        // Check-in both users at the same location
        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(4));
        CheckInTestHelper::checkInWithDate($contactedUser, $location->id, now()->subDays(4));

        // Simulate auto-checkout and report infection for infected user
        CheckInTestHelper::simulateAutoCheckout();
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser));

        // Contacted user should be marked as contacted
        $this->assertEquals(1, $contactedUser->refresh()->is_contacted);

        // Now, contacted user reports a positive test
        $response = InfectionReportTestHelper::reportPositiveTest($this->actingAs($contactedUser));

        // Assert that the user is marked as infected and not contacted anymore
        $response->assertStatus(302);
        $this->assertEquals(1, $contactedUser->refresh()->is_infected);
    }

    public function test_infected_user_with_contact_status_is_reset_by_infection_report()
    {
        $infectedUser = UserTestHelper::createNonAdminUser(['is_infected' => true]);
        $anotherInfectedUser = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        // Infected and contacted users check in
        CheckInTestHelper::checkInWithDate($infectedUser, $location->id, now()->subDays(15));
        CheckInTestHelper::checkInWithDate($anotherInfectedUser, $location->id, now()->subDays(15));

        // Simulate auto-checkout and report infection
        CheckInTestHelper::simulateAutoCheckout();
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($anotherInfectedUser), now()->subDays(14));

        // Then the user reports a positive test
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($infectedUser), now()->subDays(14));

        // Run the auto-reset logic after 14 days
        InfectionReportTestHelper::resetInfectedStatus($this);

        // Assert that the user is reset based on the infection report, not the contact status
        $this->assertEquals(0, $infectedUser->refresh()->is_infected);
        $this->assertEquals(0, $infectedUser->refresh()->is_contacted);
    }

    public function test_user_can_reset_status_after_contact_and_infection()
    {
        $user = UserTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        // User is first marked as contacted
        CheckInTestHelper::checkInWithDate($user, $location->id, now()->subDays(4));
        $user->update(['is_contacted' => true]);

        // Then user gets infected
        InfectionReportTestHelper::reportPositiveTest($this->actingAs($user));

        // User submits a negative test to clear their infection
        $response = InfectionReportTestHelper::reportNegativeTest($this->actingAs($user));

        // Assert that user is no longer infected or contacted
        $response->assertStatus(302);
        $this->assertEquals(0, $user->fresh()->is_infected);
        $this->assertEquals(0, $user->fresh()->is_contacted);
    }
}
