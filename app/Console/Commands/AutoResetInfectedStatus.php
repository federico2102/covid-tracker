<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\InfectionReport;
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
    protected $description = 'Automatically resets infected status after 14 days';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Get all users who have active infection reports older than 14 days
        $users = User::whereHas('infectionReports', function ($query) {
            $query->where('is_active', true)
                ->whereDate('test_date', '<=', Carbon::now()->subDays(14));
        })->get();

        foreach ($users as $user) {
            // Update infection report to inactive
            InfectionReport::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Set the user's 'is_infected' to false
            $user->update(['is_infected' => false]);

            $this->info("User {$user->id} infection status reset.");
        }

        $this->info('Infection status reset for all users after 14 days.');
    }
}
