<?php

namespace App\Http\Controllers;

use App\Mail\ContactNotificationMail;
use App\Models\InfectionReport;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

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
        $proofPath = $request->hasFile('proof')
            ? $request->file('proof')->store("proofs/" . auth()->id() . "/" . now()->format('Y-m-d'), 'public')
            : null;

        // Create a new active infection report
        $infectionReport = InfectionReport::create([
            'user_id' => auth()->id(),
            'test_date' => $request->test_date,
            'proof' => $proofPath,
            'is_active' => true,
        ]);

        // Mark user as infected
        $user = auth()->user();
        $user->markAsInfected();

        // Identify and notify contacted users
        $contactedUsers = $this->getContactedUsers($user, $infectionReport->test_date);
        foreach ($contactedUsers as $contactDetails) {
            $contactedUser = $contactDetails['user'];
            $sharedLocation = $contactDetails['location'];
            $sharedCheckinTime = $contactDetails['check_in_time'];

            $contactedUser->markAsContacted();

            // Create a notification for the contacted user
            Notification::create([
                'user_id' => $contactedUser->id,
                'infection_report_id' => $infectionReport->id,
                'type' => 'contact',
                'message' => 'You were in contact with an infected person at ' . $sharedLocation->name .
                    ' on ' . $sharedCheckinTime,
                'is_read' => false,
            ]);

            // Send an email notification
            Mail::to($contactedUser->email)->queue(new ContactNotificationMail(
                $contactedUser,
                $user,  // infected user
                $sharedLocation,
                $sharedCheckinTime
            ));

        }

        return redirect()->route('home')->with('success', 'Positive test reported successfully.');
    }

    public function storeNegative(Request $request): RedirectResponse
    {
        // Validate negative test proof
        $request->validate([
            'proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Deactivate active reports and mark user as healthy
        $affectedRows = InfectionReport::deactivateReports(auth()->id());
        if ($affectedRows == 0) {
            return redirect()->route('home')->with('error', 'No active infection report found.');
        }

        auth()->user()->markAsHealthy();

        return redirect()->route('home')->with('success', 'Negative test reported successfully.');
    }

    private function getContactedUsers(User $infectedUser, string $testDate): Collection
    {
        // Fetch all locations where the infected user checked in during the last week before the test date
        return $infectedUser->getContactedUsersDuringPeriod($testDate);
    }
}
