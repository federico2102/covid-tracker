@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mb-4">Add New Location</h1>

        <form method="POST" action="{{ route('locations.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Location Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" required>
            </div>

            <div class="mb-3">
                <label for="geolocation" class="form-label">Geolocation (to be replaced by Google Maps API)</label>
                <input type="text" class="form-control" id="geolocation" name="geolocation" placeholder="Geolocation placeholder for now" value="37.774929, -122.419418" required>
            </div>

            <div class="mb-3">
                <label for="max_capacity" class="form-label">Max Capacity</label>
                <input type="number" class="form-control" id="max_capacity" name="max_capacity" required>
            </div>

            <div class="mb-3">
                <label for="optional_details" class="form-label">Optional Details</label>
                <textarea class="form-control" id="optional_details" name="optional_details"></textarea>
            </div>

            <div class="mb-3">
                <label for="picture" class="form-label">Picture (optional)</label>
                <input type="file" class="form-control" id="picture" name="picture">
            </div>

            <button type="submit" class="btn btn-primary">Add Location</button>
        </form>
    </div>
@endsection
