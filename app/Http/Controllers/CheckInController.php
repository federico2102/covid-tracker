<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Location;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function show(): View|Factory|Application
    {
    return view('checkin/checkin');
    }

    public function process(Request $request): RedirectResponse
    {
        // Get the location ID from the QR code
        $url = $request->input('qr_code');

        // Parse the URL and extract the path
        $path = parse_url($url, PHP_URL_PATH);

        // Extract the ID from the path
        $locationId = basename($path);

        // Find the location
        $location = Location::find($locationId);


        if (!$location) {
            return redirect()->back()->with('error', 'Invalid location.');
        }

        // Check if the user is already checked into this location
        $existingCheckin = CheckIn::where('user_id', auth()->id())
            ->where('location_id', $locationId)
            ->whereNull('check_out_time')
            ->first();

        if ($existingCheckin) {
            return redirect()->back()->with('error', 'You are already checked in at this location.');
        }

        // Register the check-in
        CheckIn::create([
            'user_id' => auth()->id(),
            'location_id' => $locationId,
            'check_in_time' => now(),
        ]);

        // Increase the current people count at the location
        $location->increment('current_people');

        return redirect()->route('checkin.success', ['location' => $location->id]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        // Find the user's active check-in
        $checkIn = CheckIn::where('user_id', auth()->id())
            ->whereNull('check_out_time')
            ->first();

        if (!$checkIn) {
            return redirect()->back()->with('error', 'You are not checked in anywhere.');
        }

        // Update the check-out time
        $checkIn->update([
            'check_out_time' => now(),
        ]);

        // Decrease the current people count at the location
        $location = Location::find($checkIn->location_id);
        $location->decrement('current_people');

        return redirect()->route('home')->with('success', 'Successfully checked out.');
    }

    public function success(Location $location): View|Factory|Application
    {
    return view('checkin.success', compact('location'));
}
}
