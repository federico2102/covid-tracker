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
        // Store positive test
        $request->validate([
            'test_date' => 'required|date|before_or_equal:today',
            'proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);


        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs', 'public');
        }

        InfectionReport::create([
            'user_id' => auth()->id(),
            'test_date' => $request->test_date,
            'proof' => $proofPath,
            'is_active' => true,  // Set active when a positive test is reported
        ]);

        // Set the user's is_infected status to true
        $user = auth()->user();
        $user->is_infected = true;
        $user->save();

        return redirect()->route('home')->with('success', 'Positive test reported successfully.');
    }

    public function storeNegative(Request $request): RedirectResponse
    {
        // Store negative test
        $request->validate([
            'proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Update the latest infection report to inactive
        $infectionReport = InfectionReport::where('user_id', auth()->id())
            ->where('is_active', true)
            ->latest()
            ->first();

        if ($infectionReport) {
            $infectionReport->update(['is_active' => false]);
        }

        // Set the user's is_infected status to false
        $user = auth()->user();
        $user->is_infected = false;
        $user->save();

        return redirect()->route('home')->with('success', 'Negative test reported successfully.');
    }
}
