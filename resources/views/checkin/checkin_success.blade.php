@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Check-in Successful!</h1>
        <p>You have successfully checked in at {{ $location->name }}.</p>

        <div>
            <p><strong>Address:</strong> {{ $location->address }}</p>
            <p><strong>Max Capacity:</strong> {{ $location->max_capacity }}</p>
            <p><strong>Current People:</strong> {{ $location->current_people }}</p>
            <p><strong>Details:</strong> {{ $location->optional_details ?? 'N/A' }}</p>
        </div>

        <a href="{{ route('home') }}" class="btn btn-primary">Go Back to Home</a>
    </div>
@endsection
