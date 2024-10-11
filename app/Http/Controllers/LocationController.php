<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LocationController extends Controller
{
    /**
     * Display the list of locations.
     *
     * @return View
     */
    public function index(): View
    {
        $locations = Location::all();
        return view('locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new location (Admin only).
     *
     * @return View
     */
    public function create(): View
    {
        return view('locations.create');
    }

    /**
     * Show the form for editing an existing location (Admin only).
     *
     * @param Location $location
     * @return View
     */
    public function edit(Location $location): View
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'geolocation' => 'required|string|max:255',
            'max_capacity' => 'required|integer',
            'optional_details' => 'nullable|string',
            'picture' => 'nullable|image',
        ]);

        // Handle picture upload if it exists
        if ($request->hasFile('picture')) {
            $validatedData['picture'] = $request->file('picture')->store('pictures', 'public');
        }

        // Update the location
        $location->update($validatedData);

        // Redirect back with a success message
        return redirect()->route('locations')->with('success', 'Location updated successfully.');
    }


    /**
     * Store a newly created location in the database.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'geolocation' => 'required|string|max:255',
            'max_capacity' => 'required|integer',
            'optional_details' => 'nullable|string',
            'picture' => 'nullable|image',
        ]);

        // Handle picture upload if it exists
        if ($request->hasFile('picture')) {
            $validatedData['picture'] = $request->file('picture')->store('pictures', 'public');
        }

        // First, create the location without the QR code
        $location = Location::create($validatedData);

        // Now that we have the location ID, generate the correct URL for the QR code
        $qrCodeContent = route('locations.show', $location->id);  // Generate the link for the location
        $qrCodePath = 'qrcodes/' . $location->id . '.png';

        // Generate and store the QR code in the storage folder
        QrCode::format('png')->generate($qrCodeContent, storage_path('app/public/' . $qrCodePath));

        // Save the QR code link to the location
        $location->qr_code = '/storage/' . $qrCodePath;
        $location->save();

        // Redirect back to locations with a success message
        return redirect()->route('locations')->with('success', 'Location added successfully.');
    }


    public function destroy(Location $location): RedirectResponse
    {
        // Delete the location
        $location->delete();

        // Redirect back with a success message
        return redirect()->route('locations')->with('success', 'Location deleted successfully.');
    }

}
