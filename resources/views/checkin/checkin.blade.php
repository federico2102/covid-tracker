@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mb-4">Check-in</h1>
        <div class="text-center">
            <p>Scan the QR code of the location to check in:</p>
        </div>

        <!-- Div where the camera feed will be displayed -->
        <div id="reader" class="text-center mb-4" style="width: 500px; margin: 0 auto;"></div>

        <form id="checkinForm" action="{{ route('checkin.process') }}" method="POST">
            @csrf
            <input type="hidden" id="qrCode" name="qr_code">
        </form>

        <div id="qrResult" class="text-center">
            <p>Waiting to scan QR code...</p>
        </div>
    </div>
    <!-- Include the QR Code scanner library -->
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>

    <script>
        let html5QrCode;

        function onScanSuccess(qrCodeMessage) {
            console.log('Done motherfucker!!!  ', qrCodeMessage);

            // Stop scanning
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    console.log('And this shit is workin!!');

                    // Set the scanned QR code value in the hidden form input
                    document.getElementById('qrCode').value = qrCodeMessage;

                    // Submit the form automatically after scanning
                    document.getElementById('checkinForm').submit();
                }).catch(err => {
                    console.error("Failed to stop the camera:", err);
                });
            } else {
                console.error("html5QrCode is not defined");
            }
        }

        function onScanFailure(error) {
            // Log errors or display messages in the console
            console.warn(`QR error: ${error}`);
        }

        function startCamera() {
            html5QrCode = new Html5Qrcode("reader");

            setTimeout(() => {
                html5QrCode.start(
                    { facingMode: "environment" },  // Use the back camera on mobile devices
                    {
                        fps: 15,  // Increase FPS to allow more frequent scans
                        qrbox: { width: 250, height: 250 },  // Adjust the size as needed
                        aspectRatio: 1.1  // Keep a 1:1 aspect ratio for QR codes
                    },
                    onScanSuccess,
                    onScanFailure
                ).catch(err => {
                    console.error(`Error starting the camera: ${err}`);
                });
            }, 500);  // Wait 500ms before starting the scanner
        }

        document.addEventListener('DOMContentLoaded', (event) => {
            startCamera();
        });
    </script>



    </script>
@endsection
