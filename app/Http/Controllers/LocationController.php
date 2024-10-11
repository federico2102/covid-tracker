<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::all();
        return view('locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('locations.create');
    }

    public function show(Location $location): View
    {
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        return view('locations.show', compact('location', 'isAdmin'));
    }

    public function edit(Location $location): View
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'geolocation' => 'required|string|max:255',
            'max_capacity' => 'required|integer',
            'optional_details' => 'nullable|string',
            'picture' => 'nullable|image',
        ]);

        // Handle picture upload
        $validatedData['picture'] = Location::handlePictureUpload($request) ?? $location->picture;

        // Update the location
        $location->update($validatedData);

        return redirect()->route('locations')->with('success', 'Location updated successfully.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'geolocation' => 'required|string|max:255',
            'max_capacity' => 'required|integer',
            'optional_details' => 'nullable|string',
            'picture' => 'nullable|image',
        ]);

        // Handle picture upload
        $validatedData['picture'] = Location::handlePictureUpload($request);

        // Create the location
        $location = Location::create($validatedData);

        // Generate and save the QR code
        $location->generateQrCode();

        return redirect()->route('locations')->with('success', 'Location added successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();
        return redirect()->route('locations')->with('success', 'Location deleted successfully.');
    }
}

