<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    protected $fillable = ['user_id', 'location_id', 'check_in_time', 'check_out_time'];

    // Check if a user is already checked in at a location
    public static function isCheckedIn($userId, $locationId)
    {
        return self::where('user_id', $userId)
            ->where('location_id', $locationId)
            ->whereNull('check_out_time')
            ->exists();
    }

    // Register a new check-in
    public static function registerCheckIn($userId, $locationId)
    {
        return self::create([
            'user_id' => $userId,
            'location_id' => $locationId,
            'check_in_time' => now(),
        ]);
    }

    // Register a check-out
    public function registerCheckOut()
    {
        $this->update(['check_out_time' => now()]);
    }
}

