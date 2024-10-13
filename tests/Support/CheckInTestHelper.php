<?php

namespace Tests\Support;

use App\Models\CheckIn;
use Carbon\Carbon;

class CheckInTestHelper
{
    public static function checkInUser($user, $locationId, $date = null, $userId = null)
    {
       if ($date){
           $checkin = CheckIn::create([
               'user_id' => $userId,
               'location_id' => $locationId,
               'check_in_time' => $date,
           ]);
       } else {
           $checkin = $user->withoutMiddleware()->post(route('checkin.process'), [
               'qr_code' => 'http://example.com/checkin/' . $locationId,
           ]);
       }

       return $checkin;
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
