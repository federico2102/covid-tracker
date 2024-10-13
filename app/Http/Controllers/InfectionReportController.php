<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InfectionReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        InfectionReport::create([
            'user_id' => auth()->id(),
            'test_date' => $request->test_date,
            'proof' => $proofPath,
            'is_active' => true,
        ]);

        // Mark user as infected
        $user = auth()->user();
        $user->is_infected = true;
        $user->save();

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
}
