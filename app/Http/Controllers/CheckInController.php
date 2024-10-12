<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class CheckInController extends Controller
{
    public function show(): View
    {
    return view('checkin.checkin');
    }

    public function process(Request $request)
    {
    $url = $request->input('qr_code');
    $path = parse_url($url, PHP_URL_PATH);
    $locationId = basename($path);

    $location = Location::find($locationId);

    if (!$location) {
        return redirect()->back()->with('error', 'Invalid location.');
    }

    // Check if the location has reached its max capacity
    if ($location->isFull()) {
        return redirect()->route('home')->with('error', 'This location has reached its maximum capacity. Please try again later.');
    }

    if (CheckIn::isCheckedIn(auth()->id(), $locationId)) {
        return redirect()->back()->with('error', 'You are already checked in at this location.');
    }

    if (auth()->user()->is_infected) {
        return response()->json(['error' => 'You cannot check in because you are infected.'], 403);
    }


    CheckIn::registerCheckIn(auth()->id(), $locationId);
    $location->incrementPeople();

        return redirect()->route('checkin.success', ['location' => $location->id]);
    }

    public function checkout(): RedirectResponse
    {
        $checkIn = CheckIn::where('user_id', auth()->id())
        ->whereNull('check_out_time')
        ->first();

     if (!$checkIn) {
            return redirect()->back()->with('error', 'You are not checked in anywhere.');
        }

        $checkIn->registerCheckOut();
        $location = Location::find($checkIn->location_id);
        $location->decrementPeople();

        return redirect()->route('home')->with('success', 'Successfully checked out.');
    }

    public function success(Location $location): View   {
        return view('checkin.success', compact('location'));
    }
}
