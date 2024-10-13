<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InfectionReport;
use App\Models\Location;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InfectionReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Validate the test date and proof image
        $request->validate([
            'test_date' => 'required|date|before_or_equal:today',
            'proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Store proof file if present
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store("proofs/" . auth()->id() . "/" . now()->format('Y-m-d'), 'public');
        }

        // Create a new active infection report
        $infectionReport = InfectionReport::create([
            'user_id' => auth()->id(),
            'test_date' => $request->test_date,
            'proof' => $proofPath,
            'is_active' => true,
        ]);

        // Mark user as infected
        $user = auth()->user();
        $user->is_infected = true;
        $user->save();

        // Identify users who were in contact
        $contactedUsers = $this->getContactedUsers($user, $infectionReport->test_date);

        Log::info($contactedUsers);
        // Mark users as contacted and notify them
        foreach ($contactedUsers as $contactDetails) {
            $contactedUser = $contactDetails['user'];  // Extract the user
            $sharedLocation = $contactDetails['location'];  // Extract the location
            $sharedCheckinTime = $contactDetails['check_in_time'];  // Extract the check-in time

            // Mark the contacted user as contacted
            $contactedUser->is_contacted = true;
            $contactedUser->save();

            // Create a notification for the contacted user
            Notification::create([
                'user_id' => $contactedUser->id,
                'infection_report_id' => $infectionReport->id,
                'type' => 'contact',
                'message' => 'You were in contact with an infected person at ' . $sharedLocation->name .
                    ' on ' . $sharedCheckinTime,
                'is_read' => false,  // Mark the notification as unread by default
            ]);
        }


        return redirect()->route('home')->with('success', 'Positive test reported successfully.');
    }

    public function storeNegative(Request $request): RedirectResponse
    {
        // Validate negative test proof
        $request->validate([
            'proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Fetch all active reports and set them to inactive
        $affectedRows = InfectionReport::where('user_id', auth()->id())
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($affectedRows == 0) {
            return redirect()->route('home')->with('error', 'No active infection report found.');
        }

        // Mark the user as healthy
        $user = auth()->user();
        $user->is_infected = false;
        $user->save();

        return redirect()->route('home')->with('success', 'Negative test reported successfully.');
    }

    private function getContactedUsers($infectedUser, $testDate): Collection
    {
        // Get all locations where the infected user checked in during the last week before the test date
        $infectedUserLocations = $infectedUser->checkins()
            ->whereBetween('check_in_time', [now()->subWeek(), $testDate])
            ->get(['location_id', 'check_in_time', 'check_out_time']);

        if ($infectedUserLocations->isEmpty()) {
            // If no check-ins, return an empty collection
            return collect();
        }

        // Create a collection to store the contacted users and their last shared check-in details
        $contactedUsers = collect();

        // Loop through each location the infected user has been to
        foreach ($infectedUserLocations as $infectedCheckin) {
            // Fetch users who were at the same location during the same time frame as the infected user
            $usersInContact = User::whereHas('checkins', function ($query) use ($infectedCheckin) {
                $query->where('location_id', $infectedCheckin->location_id)
                    ->where('check_in_time', '<=', $infectedCheckin->check_out_time)
                    ->where('check_out_time', '>=', $infectedCheckin->check_in_time) // Ensure overlapping time
                    ->where('user_id', '!=', auth()->id());  // Exclude the infected user
            })->get();

            // For each contacted user, check their latest shared check-in
            foreach ($usersInContact as $user) {
                // Get the latest shared check-in between this user and the infected user at this location
                $lastSharedCheckin = $user->checkins()
                    ->where('location_id', $infectedCheckin->location_id)
                    ->where('check_in_time', '<=', $infectedCheckin->check_out_time)
                    ->where('check_out_time', '>=', $infectedCheckin->check_in_time)
                    ->latest('check_in_time')
                    ->first();

                if ($lastSharedCheckin) {
                    // Store the user, location, and shared time in the collection
                    $contactedUsers->push([
                        'user' => $user,
                        'location' => Location::find($lastSharedCheckin->location_id),
                        'check_in_time' => $lastSharedCheckin->check_in_time,
                    ]);
                }
            }
        }

        // Return the contacted users with the associated location and time of contact
        return $contactedUsers;
    }


}
