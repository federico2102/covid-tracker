<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'location_id', 'check_in_time', 'check_out_time'];

    // Check if a user is already checked in at a location
    public static function isCheckedIn($userId)
    {
        return self::where('user_id', $userId)
            ->whereNull('check_out_time')
            ->exists();
    }

    // Register a new check-in
    public static function registerCheckIn($userId, $locationId)
    {
        return self::create([
            'user_id' => $userId,
            'location_id' => $locationId,
            'check_in_time' => Carbon::now(),
        ]);
    }

    // Register a check-out
    public function registerCheckOut(): void
    {
        $this->update(['check_out_time' => Carbon::now()]);
    }

    public function scopeAutoCheckout($query)
    {
        return $query->whereNull('check_out_time')
            ->where('check_in_time', '<=', Carbon::now()->subHours(3));
    }

}

