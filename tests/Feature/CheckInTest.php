<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_check_in_successfully()
    {
        // Create a user and a location
        $user = User::factory()->create();
        $location = Location::factory()->create(['current_people' => 0]);

        // Simulate QR code check-in
        $response = $this->actingAs($user)->withoutMiddleware()->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $location->id,  // Simulating the QR code URL
        ]);

        // Assert that the response is a redirect to the success page (302)
        $response->assertStatus(302);
        $response->assertRedirect(route('checkin.success', ['location' => $location->id]));

        // Assert the user's check-in is logged in the database
        $this->assertDatabaseHas('check_ins', [
            'user_id' => $user->id,
            'location_id' => $location->id,
        ]);

        // Assert that the location's current_people count has increased
        $location->refresh();  // Reload the location from the database
        $this->assertEquals(1, $location->current_people);
    }



    public function test_user_cannot_check_in_twice()
    {
        // Create a user and a location
        $user = User::factory()->create();
        $location = Location::factory()->create(['current_people' => 1]);

        // Simulate the user checking in for the first time
        CheckIn::registerCheckIn($user->id, $location->id);

        // Act as the user and attempt to check in again
        $response = $this->actingAs($user)->withoutMiddleware()->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $location->id,  // Simulating the QR code URL
        ]);

        // Assert that the response is a redirect (302) with an error
        $response->assertStatus(302);
        $response->assertSessionHas('error', 'You are already checked in at this location.');

        // Assert that the check_in record in the database was not duplicated
        $this->assertEquals(1, CheckIn::where('user_id', $user->id)->where('location_id', $location->id)->count());

        // Assert that the location's current_people count remains the same
        $location->refresh();  // Reload the location from the database
        $this->assertEquals(1, $location->current_people);
    }


    public function test_infected_user_cannot_check_in()
    {
        // Create an infected user and a location
        $user = User::factory()->create(['is_infected' => true]);
        $location = Location::factory()->create();

        // Try to check in
        $response = $this->actingAs($user)->post('/checkin', [
            'location_id' => $location->id,
        ]);

        // Assert that the user is forbidden from checking in
        $response->assertStatus(403);
        $response->assertSee('You cannot check in because you are infected.');

        // Assert the user's check-in was not logged
        $this->assertDatabaseMissing('check_ins', [
            'user_id' => $user->id,
            'location_id' => $location->id,
        ]);
    }

    public function test_user_can_check_out_successfully()
    {
        // Create a user and a location
        $user = User::factory()->create();
        $location = Location::factory()->create();

        // Simulate a QR code URL for the location
        $qrCode = 'http://example.com/checkin/' . $location->id;

        // Check the user in using the QR code
        $this->actingAs($user)->post('/checkin', ['qr_code' => $qrCode]);

        // Check the user out
        $response = $this->actingAs($user)->post('/checkout');

        // Assert the response was successful (302 - redirect to home)
        $response->assertStatus(302);
        $response->assertRedirect(route('home'));

        // Assert the user's check-out was logged in the database
        $this->assertDatabaseHas('check_ins', [
            'user_id' => $user->id,
            'location_id' => $location->id,
            'check_out_time' => Carbon::now(),
        ]);

        // Assert the location's current_people count has decreased
        $location->refresh();  // Reload the location from the database
        $this->assertEquals(0, $location->current_people);
    }

    public function test_auto_checkout_after_3_hours()
    {
        // Create a user and a location
        $user = User::factory()->create();
        $location = Location::factory()->create(['current_people' => 1]);

        // Check the user in
        $checkInTime = Carbon::now()->subHours(4);  // Simulate check-in 4 hours ago
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


}
