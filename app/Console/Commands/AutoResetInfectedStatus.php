<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\InfectionReport;
use App\Models\Notification;
use Carbon\Carbon;

class AutoResetInfectedStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkin:auto-reset-infected-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically resets infected and contacted statuses after 14 days';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Reset infected users after 14 days
        $infectedUsers = User::whereHas('infectionReports', function ($query) {
            $query->where('is_active', true)
                ->whereDate('test_date', '<=', Carbon::now()->subDays(14));
        })->get();

        foreach ($infectedUsers as $user) {
            // Update infection report to inactive
            InfectionReport::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Set the user's 'is_infected' to false
            $user->update(['is_infected' => false]);

            $this->info("User {$user->id} infection status reset.");
        }

        // Reset contacted users after 14 days
        $contactedUsers = User::whereHas('notifications', function ($query) {
            $query->where('type', 'contact')
                ->whereDate('date_of_contact', '<=', Carbon::now()->subDays(14));
        })->get();

        foreach ($contactedUsers as $user) {
            // Reset the 'is_contacted' status to false
            $user->update(['is_contacted' => false]);

            $this->info("User {$user->id} contact status reset.");
        }

        $this->info('Infection and contact statuses reset for all users after 14 days.');
    }
}
