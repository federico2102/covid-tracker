let map, marker, autocomplete;

function initMap() {
    console.log("Map initialization started");
    // Default location: Barcelona
    let defaultLocation = { lat: 41.3851, lng: 2.1734 };
    let latLng;

    // Check if there's a geolocation element (for edit and show views)
    const geolocationElement = document.getElementById('geolocation');
    if (geolocationElement && geolocationElement.value) {
        const geolocation = geolocationElement.value.split(',');
        latLng = { lat: parseFloat(geolocation[0]), lng: parseFloat(geolocation[1]) };
    } else {
        latLng = defaultLocation;
    }

    // Initialize the map
    map = new google.maps.Map(document.getElementById('map'), {
        center: latLng,
        zoom: 14,
    });

    // Initialize the marker
    marker = new google.maps.Marker({
        position: latLng,
        map: map,
        draggable: !!geolocationElement, // Allow dragging only if geolocation input exists (i.e., create/edit view)
    });

    // If there's a geolocation input field (create or edit), update it when the marker is dragged
    if (geolocationElement) {
        google.maps.event.addListener(marker, 'dragend', function (event) {
            geolocationElement.value = event.latLng.lat() + ',' + event.latLng.lng();
        });
    }

    // Initialize autocomplete for the address input (only for create or edit views)
    const addressElement = document.getElementById('address');
    if (addressElement) {
        autocomplete = new google.maps.places.Autocomplete(addressElement);
        autocomplete.bindTo('bounds', map);

        // Update map and marker when a user selects an address from autocomplete
        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();

            if (!place.geometry) {
                console.log("No details available for input: '" + place.name + "'");
                return;
            }

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }

            // Move marker to the new location
            marker.setPosition(place.geometry.location);

            // Update geolocation field if it exists
            if (geolocationElement) {
                geolocationElement.value = place.geometry.location.lat() + ',' + place.geometry.location.lng();
            }
        });
    }
}

// Manually call initMap when the DOM is ready
document.addEventListener("DOMContentLoaded", function() {
    if (typeof initMap === 'function') {
        initMap();
    } else {
        console.error("initMap is not a function or Google Maps API failed to load.");
    }
});
