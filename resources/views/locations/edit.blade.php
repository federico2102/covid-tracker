@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Location: {{ $location->name }}</h1>

        <form action="{{ route('locations.update', $location->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name">Location Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $location->name }}" required>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" value="{{ $location->address }}" required>
            </div>

            <!-- Map Section -->
            <div id="map" style="width: 100%; height: 400px;"></div>
            <input type="hidden" id="geolocation" name="geolocation" value="" data-lat="41.3851" data-lng="2.1734">

            <div class="mb-3">
                <label for="max_capacity">Max Capacity</label>
                <input type="number" name="max_capacity" id="max_capacity" class="form-control" value="{{ $location->max_capacity }}" required>
            </div>

            <div class="mb-3">
                <label for="optional_details">Details (Optional)</label>
                <textarea name="optional_details" id="optional_details" class="form-control">{{ $location->optional_details }}</textarea>
            </div>

            <div class="mb-3">
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

    <script>
        let map;
        let marker;
        let autocomplete;

        function initMap() {
            const geolocation = "{{ $location->geolocation }}".split(',');
            const latLng = { lat: parseFloat(geolocation[0]), lng: parseFloat(geolocation[1]) };
            var defaultLocation = {lat: latLng.lat, lng: latLng.lng}; // Coordinates for Barcelona

            // Initialize the map
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 14,
            });

            // Initialize the marker
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true,
            });

            // Set geolocation input when the marker is dragged
            google.maps.event.addListener(marker, 'dragend', function(event) {
                document.getElementById('geolocation').value = event.latLng.lat() + ',' + event.latLng.lng();
            });

            // Initialize autocomplete for the address input
            autocomplete = new google.maps.places.Autocomplete(document.getElementById('address'));

            // Bind the autocomplete to the map's bounds
            autocomplete.bindTo('bounds', map);

            // Update map and marker when a user selects an address from autocomplete
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();

                if (!place.geometry) {
                    console.log("No details available for input: '" + place.name + "'");
                    return;
                }

                // If the place has a geometry, then present it on a map.
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);  // Adjust the zoom after the selection
                }

                // Move marker to the new location
                marker.setPosition(place.geometry.location);

                // Update geolocation field
                document.getElementById('geolocation').value = place.geometry.location.lat() + ',' + place.geometry.location.lng();
            });
        }

        // Initialize the map after the page has loaded
        window.onload = initMap;
    </script>

    <!-- Load the Google Maps and Places API -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initMap" async defer></script>

@endsection
