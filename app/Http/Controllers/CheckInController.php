<?php

namespace App\Http\Controllers;

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
$qrCode = $request->input('qr_code');

// Decode the QR code to get the location ID (if the QR code contains a link, extract the location ID)
$locationId = $this->getLocationIdFromQrCode($qrCode);

// Fetch the location
$location = Location::findOrFail($locationId);

// Add the user to the location (you can customize this logic based on your needs)
$location->current_people += 1;
$location->save();

// Optionally, save the check-in details to a `check_ins` table
// CheckIn::create([...]);

return redirect()->route('checkin.success', $location->id);
}

protected function getLocationIdFromQrCode($qrCode): false|string
{
// Extract the qr location ID
$urlParts = parse_url($qrCode);
$pathParts = explode('/', $urlParts['path']);

// Assuming the location ID is the last part of the URL
return end($pathParts);
}

public function success(Location $location): View|Factory|Application
{
return view('checkin/checkin_success', compact('location'));
}
}
