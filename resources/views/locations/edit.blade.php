@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Location: {{ $location->name }}</h1>

        <form action="{{ route('locations.update', $location->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Location Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $location->name }}" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" class="form-control" value="{{ $location->address }}" required>
            </div>

            <div class="form-group">
                <label for="geolocation">Geolocation</label>
                <input type="text" name="geolocation" id="geolocation" class="form-control" value="{{ $location->geolocation }}" required>
            </div>

            <div class="form-group">
                <label for="max_capacity">Max Capacity</label>
                <input type="number" name="max_capacity" id="max_capacity" class="form-control" value="{{ $location->max_capacity }}" required>
            </div>

            <div class="form-group">
                <label for="optional_details">Details (Optional)</label>
                <textarea name="optional_details" id="optional_details" class="form-control">{{ $location->optional_details }}</textarea>
            </div>

            <div class="form-group">
                <label for="picture">Update Picture (Optional)</label>
                <input type="file" name="picture" id="picture" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>

        <!-- Delete Button -->
        <form action="{{ route('locations.destroy', $location->id) }}" method="POST" class="mt-3">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this location?')">Delete Location</button>
        </form>
    </div>
@endsection
