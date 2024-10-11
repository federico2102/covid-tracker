<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use App\Models\CheckIn;
use Carbon\Carbon;

class AutoCheckout extends Command
{
    // Command signature for running it manually or via the scheduler
    protected $signature = 'checkin:auto-checkout';

    // Description of what the command does
    protected $description = 'Automatically check out users after 3 hours of check-in if they haven\'t checked out';

    public function handle(): void
    {
        // Find all check-ins where check_out_time is NULL and check-in is older than 3 hours
        $checkIns = CheckIn::whereNull('check_out_time')
            ->where('check_in_time', '<=', Carbon::now()->subHours(3)) // Select check-ins older than 3 hours
            ->get();

        // Loop through the check-ins and check the users out
        foreach ($checkIns as $checkIn) {
            // Update check-out time to now
            $checkIn->registerCheckOut();

            // Decrease the current people count at the location
            $location = Location::find($checkIn->location_id);
            $location->decrementPeople();

            $this->info("Checked out user {$checkIn->user_id} from location {$location->name}");
        }

        $this->info('Auto-checkout process complete.');
    }
}
