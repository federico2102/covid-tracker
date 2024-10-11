@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mb-4">Locations</h1>

        @if(Auth::check() && Auth::user()->is_admin)
            <div class="mb-3 text-center">
                <a href="{{ route('locations.create') }}" class="btn btn-primary">Add New Location</a>
            </div>
        @endif

        <div class="row">
            @foreach($locations as $location)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <!-- Make the location name clickable -->
                            <h5 class="card-title">
                                <a href="{{ route('locations.show', $location->id) }}">
                                    {{ $location->name }}
                                </a>
                            </h5>
                            <p class="card-text">
                                <strong>Address:</strong> {{ $location->address }}<br>
                                <strong>Geolocation:</strong> {{ $location->geolocation }}<br>
                                <strong>Max Capacity:</strong> {{ $location->max_capacity }}<br>
                                <strong>Current People:</strong> {{ $location->current_people }}<br>
                                <strong>Details:</strong> {{ $location->optional_details ?? 'N/A' }}<br>

                                <!-- Display the picture if it exists -->
                                @if($location->picture)
                                    <img src="{{ asset('storage/' . $location->picture) }}" alt="Location Image" class="img-fluid mt-2">
                            @else
                                <p>No image available.</p>
                                @endif

                                @if(Auth::check() && Auth::user()->is_admin)
                                    <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-secondary">Edit Location</a>

                                    <form action="{{ route('locations.destroy', $location->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this location?')">Delete</button>
                                    </form>
                                @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
