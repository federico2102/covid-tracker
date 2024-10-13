<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
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
        $user = auth()->user();

        if ($user->is_infected) {
            return response()->json(['error' => 'You cannot check in because you are infected.'], 403);
        }

        if ($user->is_contacted) {
            return response()->json([
                'error' => 'You cannot check in because you were in contact with an infected individual.'], 403);
        }

        if (CheckIn::isCheckedIn(auth()->id())) {
            return response()->json(['error' => 'You are already checked in here or at another location.'], 403);
        }

        if (!$location) {
            return redirect()->route('home')->with('error', 'Invalid location.');
        }

        // Check if the location has reached its max capacity
        if ($location->isFull()) {
            return redirect()->route('home')
                ->with('error', 'This location has reached its maximum capacity. Please try again later.');
        }

        CheckIn::registerCheckIn(auth()->id(), $locationId);
        $location->incrementPeople();

        return redirect()->route('checkin.success', ['location' => $location->id])
            ->with('success', 'Check in was successful.');
    }

    public function checkout(): JsonResponse|RedirectResponse
    {
        $checkIn = CheckIn::where('user_id', auth()->id())
            ->whereNull('check_out_time')
            ->first();

        if (!$checkIn) {
            return response()->json(['error' => 'You are not checked in anywhere.'], 403);
        }

        $checkIn->registerCheckOut();
        $location = Location::find($checkIn->location_id);
        $location->decrementPeople();

        return redirect()->route('home')->with('success', 'Successfully checked out.');
    }

    public function success(Location $location): View
    {
        return view('checkin.success', compact('location'));
    }

}
