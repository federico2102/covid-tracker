<script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>

@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mb-4">Welcome to Covid Tracker</h1>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <!-- Show self-state (infected or not) -->
                        <h2 class="mb-3">Your Status:
                            @if(Auth::user()->is_infected)
                                <span class="text-danger">Infected</span>
                            @else
                                <span class="text-success">Healthy</span>
                            @endif
                        </h2>

                        <!-- Layout for the buttons -->
                        <div class="row mb-3">
                            <div class="col-md-4 text-center">
                                <!-- Locations Button on the Left -->
                                <a href="{{ route('locations') }}" class="btn btn-info btn-lg">View Locations</a>
                            </div>
                            <div class="col-md-4 text-center">
                                <!-- Check-in Button in the Middle -->
                                @if(Auth::user()->is_infected)
                                    <button class="btn btn-secondary btn-lg" disabled>Check-in</button>
                                @else
                                    @if($isCheckedIn)
                                        <a href="{{ route('checkout') }}" class="btn btn-danger">Check Out</a>
                                    @else
                                        <a href="{{ route('checkin') }}" class="btn btn-success">Check In</a>
                                    @endif
                                @endif
                            </div>
                            <div class="col-md-4 text-center">
                                <!-- Test Reporting Button on the Right -->
                                @if(Auth::user()->is_infected)
                                    <a href="{{ route('negative-test') }}" class="btn btn-danger btn-lg">Inform Negative Test</a>
                                @else
                                    <a href="{{ route('positive-test') }}" class="btn btn-warning btn-lg">Inform Positive Test</a>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
