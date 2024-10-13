<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\InfectionReport;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UserTestHelper;
use Tests\Support\LocationTestHelper;
use Tests\Support\CheckInTestHelper;
use Tests\Support\AssertionHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Mail\ContactNotificationMail;

class InfectionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_positive_and_negative_infection()
    {
        $user = UserTestHelper::createNonAdminUser();

        // Positive test
        $response = $this->actingAs($user)->post(route('infectionReports.store'), [
            'test_date' => now()->subDays(1)->format('Y-m-d'),
            'proof' => null,
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('infection_reports', ['user_id' => $user->id, 'is_active' => true]);
        $this->assertEquals(1, $user->fresh()->is_infected);

        // Negative test
        InfectionReport::create([
            'user_id' => $user->id,
            'test_date' => now()->subDays(10)->format('Y-m-d'),
            'is_active' => true,
        ]);
        $response = $this->actingAs($user)->post(route('infectionReports.negative'), ['proof' => null]);
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
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), ['test_date' => now()->format('Y-m-d')]);

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
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), ['test_date' => now()->format('Y-m-d')]);

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
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), ['test_date' => now()->subDays(14)]);

        // Reset status after 14 days
        $this->artisan('checkin:auto-reset-infected-status')->assertExitCode(0);
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
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), ['test_date' => now()->format('Y-m-d')]);

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

        $this->actingAs($infectedUser)->post(route('infectionReports.store'), ['test_date' => now()->format('Y-m-d')]);
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
        $this->actingAs($infectedUser)->post(route('infectionReports.store'), ['test_date' => now()->format('Y-m-d')]);

        $this->assertCount(1, $contactedUser->notifications()->where('type', 'contact')->get());
        Mail::assertQueued(ContactNotificationMail::class, 1);
    }
}
