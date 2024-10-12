<?php

namespace Tests\Support;

class AssertionHelper
{
    public static function checkInExists($user, $location): array
    {
        return [
            'user_id' => $user->id,
            'location_id' => $location->id,
            'check_out_time' => null,
        ];
    }

    public static function checkOutExists($user, $location): array
    {
        return [
            'user_id' => $user->id,
            'location_id' => $location->id,
            'check_out_time' => now(),
        ];
    }

    public static function assertSuccessfulResponse($response, $redirectRoute, $sessionMessage = null): void
    {
        $response->assertStatus(302);
        $response->assertRedirect($redirectRoute);
        if ($sessionMessage) {
            $response->assertSessionHas('success', $sessionMessage);
        }
    }

    public static function assertForbiddenResponse($response, $errorMessage): void
    {
        $response->assertStatus(403);
        $response->assertSee($errorMessage);
    }
}
