@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mb-4">{{ $location->name }}</h1>

        <div class="text-center">
            <p><strong>Address:</strong> {{ $location->address }}</p>
            <p><strong>Geolocation:</strong> {{ $location->geolocation }}</p>
            <p><strong>Max Capacity:</strong> {{ $location->max_capacity }}</p>
            <p><strong>Current People:</strong> {{ $location->current_people }}</p>
            @if($location->optional_details)
                <p><strong>Details:</strong> {{ $location->optional_details }}</p>
            @endif

            <div id="map" style="width: 100%; height: 400px;"></div>

            <!-- Show the QR code only if the user is an admin -->
            @if($isAdmin && $location->qr_code)
                <div class="text-center">
                    <h5>QR Code for this location:</h5>
                    <img src="{{ $location->qr_code }}" alt="QR Code for {{ $location->name }}">
                </div>
            @endif
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('locations') }}" class="btn btn-primary">Back to Locations</a>
        </div>
    </div>

    <script>
        function initMap() {
            const geolocation = "{{ $location->geolocation }}".split(',');
            const latLng = { lat: parseFloat(geolocation[0]), lng: parseFloat(geolocation[1]) };

            const map = new google.maps.Map(document.getElementById('map'), {
                center: latLng,
                zoom: 15
            });

            const marker = new google.maps.Marker({
                position: latLng,
                map: map
            });
        }

        window.onload = initMap;
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initMap" async defer></script>

@endsection
