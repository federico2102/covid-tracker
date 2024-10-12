<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
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

    public function create(): Factory|Application|\Illuminate\Contracts\View\View|JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }
        return view('locations.create');
    }

    public function show(Location $location): View
    {
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        return view('locations.show', compact('location', 'isAdmin'));
    }

    public function edit(Location $location): Application|Factory|\Illuminate\Contracts\View\View|JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): JsonResponse|RedirectResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'geolocation' => ['required', 'regex:/^-?\d{1,3}\.\d+,\s*-?\d{1,3}\.\d+$/'],
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

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'geolocation' => ['required', 'regex:/^-?\d{1,3}\.\d+,\s*-?\d{1,3}\.\d+$/'],
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

    public function destroy(Location $location): JsonResponse|RedirectResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }
        $location->delete();
        return redirect()->route('locations')->with('success', 'Location deleted successfully.');
    }
}

