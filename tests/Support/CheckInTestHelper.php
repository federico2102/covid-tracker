<?php

namespace Tests\Support;

use App\Models\CheckIn;
use Carbon\Carbon;

class CheckInTestHelper
{
    public static function checkInWithDate($user, $locationId, $date)
    {
        return CheckIn::create([
            'user_id' => $user->id,
            'location_id' => $locationId,
            'check_in_time' => $date,
        ]);
    }

    public static function checkInThroughRequest($user, $locationId)
    {
        return $user->withoutMiddleware()->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $locationId,
        ]);
    }

    public static function checkOutUser($user)
    {
        return $user->post('/checkout');
    }

    public static function simulateAutoCheckout(): void
    {
        // Find all users checked in for more than 3 hours and automatically check them out
        $users = CheckIn::where('check_in_time', '<', now()->subHours(3))
            ->whereNull('check_out_time')
            ->get();

        foreach ($users as $checkIn) {
            // Simulate automatic checkout logic
            $checkIn->update(['check_out_time' => Carbon::parse($checkIn->check_in_time)->addHours(3)]);
        }
    }

}
