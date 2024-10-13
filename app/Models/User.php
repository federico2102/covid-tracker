<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Collection;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property bool $is_admin
 */
class User extends Authenticatable
{
    use HasFactory;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'is_admin',
        'is_infected'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function infectionReports(): HasMany
    {
        return $this->hasMany(InfectionReport::class, 'user_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function latestLocation()
    {
        // Fetch the latest check-in, or return null if no check-in exists
        $latestCheckin = $this->checkins()->latest()->first();
        return $latestCheckin ? $latestCheckin->location : null;
    }

    public function markAsInfected()
    {
        $this->is_infected = true;
        $this->save();
    }

    public function markAsContacted()
    {
        $this->is_contacted = true;
        $this->save();
    }

    public function markAsHealthy()
    {
        $this->is_infected = false;
        $this->save();
    }

    public function getContactedUsersDuringPeriod(string $testDate): Collection
    {
        // Fetch all locations where the infected user checked in during the past week
        $infectedCheckins = $this->checkins()->whereBetween('check_in_time', [now()->subWeek(), $testDate])->get();

        if ($infectedCheckins->isEmpty()) {
            return collect();
        }

        $contactedUsers = collect();

        // Loop through the locations and find other users in contact with the infected user
        foreach ($infectedCheckins as $infectedCheckin) {
            $usersInContact = User::whereHas('checkins', function ($query) use ($infectedCheckin) {
                $query->where('location_id', $infectedCheckin->location_id)
                    ->where('check_in_time', '<=', $infectedCheckin->check_out_time)
                    ->where('check_out_time', '>=', $infectedCheckin->check_in_time)
                    ->where('user_id', '!=', $this->id);
            })->get();

            foreach ($usersInContact as $user) {
                $lastSharedCheckin = $user->checkins()
                    ->where('location_id', $infectedCheckin->location_id)
                    ->where('check_in_time', '<=', $infectedCheckin->check_out_time)
                    ->where('check_out_time', '>=', $infectedCheckin->check_in_time)
                    ->latest('check_in_time')
                    ->first();

                if ($lastSharedCheckin) {
                    $contactedUsers->push([
                        'user' => $user,
                        'location' => Location::find($lastSharedCheckin->location_id),
                        'check_in_time' => $lastSharedCheckin->check_in_time,
                    ]);
                }
            }
        }

        return $contactedUsers;
    }
}
