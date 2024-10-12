<?php

namespace Tests\Support;

class CheckInTestHelper
{
    public static function checkInUser($user, $locationId)
    {
        return $user->withoutMiddleware()->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $locationId,
        ]);
    }

    public static function checkOutUser($user)
    {
        return $user->post('/checkout');
    }
}
