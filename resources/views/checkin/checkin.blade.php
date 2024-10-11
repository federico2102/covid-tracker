<script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
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

    <script>
        function onScanSuccess(qrCodeMessage) {
            // Set the scanned QR code value in the hidden form input
            document.getElementById('qrCode').value = qrCodeMessage;

            // Submit the form automatically after scanning
            document.getElementById('checkinForm').submit();
        }

        function onScanFailure(error) {
            // You can log errors or display messages in the console
            console.warn(`QR error: ${error}`);
        }

        function startCamera() {
            const html5QrCode = new Html5Qrcode("reader");

            // Delay QR code scanner start to ensure camera feed is ready
            setTimeout(() => {
                html5QrCode.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: { width: 300, height: 300 },
                    },
                    onScanSuccess,
                    onScanFailure
                ).catch(err => {
                    alert(`Error: Unable to start camera - ${err}`);
                });
            }, 500);  // Wait 500ms before starting the scanner
        }

        document.addEventListener('DOMContentLoaded', (event) => {
            startCamera();
        });


    </script>
@endsection
