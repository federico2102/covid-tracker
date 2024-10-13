<script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>

@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mb-4">Welcome to Covid Tracker</h1>

        <!-- Check if there's a session error -->
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <!-- Show self-state (infected, contacted, or healthy) -->
                        <h2 class="mb-3">Your Status:
                            @if(Auth::user()->is_infected)
                                <span class="text-danger">Infected</span>
                            @elseif(Auth::user()->is_contacted)
                                <span class="text-warning">In Contact with Infected</span>
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
                                @if(Auth::user()->is_infected || Auth::user()->is_contacted)
                                    <button class="btn btn-secondary btn-lg" disabled>Check-in</button>
                                @else
                                    @if($isCheckedIn)
                                        <form action="{{ route('checkout') }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to check out?');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger">Check Out</button>
                                        </form>
                                    @else
                                        <a href="{{ route('checkin') }}" class="btn btn-success">Check In</a>
                                    @endif
                                @endif
                            </div>

                            <div class="col-md-4 text-center">
                                <!-- Test Reporting Buttons on the Right -->
                                @if(Auth::user()->is_infected)
                                    <!-- Inform Negative Test Button (Red) -->
                                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal"
                                            data-bs-target="#informNegativeModal">
                                        Inform Negative Test
                                    </button>

                                    <!-- Modal for Inform Negative Test -->
                                    <div class="modal fade" id="informNegativeModal" tabindex="-1"
                                         aria-labelledby="informNegativeLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="informNegativeLabel">Report Negative
                                                        COVID-19 Test</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{ route('infectionReports.negative') }}" method="POST"
                                                          enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="proof" class="form-label">Upload Proof (optional)</label>
                                                            <input type="file" class="form-control" id="proof" name="proof">
                                                        </div>
                                                        <button type="submit" class="btn btn-danger">Submit</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif(Auth::user()->is_contacted)
                                    <!-- Both Inform Positive and Negative Test Buttons -->
                                    <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal"
                                            data-bs-target="#informPositiveModal">
                                        Inform Positive Test
                                    </button>

                                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal"
                                            data-bs-target="#informNegativeModal">
                                        Inform Negative Test
                                    </button>

                                    <!-- Modal for Inform Positive Test -->
                                    <div class="modal fade" id="informPositiveModal" tabindex="-1"
                                         aria-labelledby="informPositiveLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="informPositiveLabel">Report Positive
                                                        COVID-19 Test</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{ route('infectionReports.store') }}" method="POST"
                                                          enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="test_date" class="form-label">Date of Positive
                                                                Test</label>
                                                            <input type="date" class="form-control" id="test_date"
                                                                   name="test_date" max="{{ date('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="proof" class="form-label">Upload Proof (optional)</label>
                                                            <input type="file" class="form-control" id="proof" name="proof">
                                                        </div>
                                                        <button type="submit" class="btn btn-warning">Submit</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal for Inform Negative Test -->
                                    <div class="modal fade" id="informNegativeModal" tabindex="-1"
                                         aria-labelledby="informNegativeLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="informNegativeLabel">Report Negative
                                                        COVID-19 Test</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{ route('infectionReports.negative') }}" method="POST"
                                                          enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="proof" class="form-label">Upload Proof (optional)</label>
                                                            <input type="file" class="form-control" id="proof" name="proof">
                                                        </div>
                                                        <button type="submit" class="btn btn-danger">Submit</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- Inform Positive Test Button (Yellow) -->
                                    <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal"
                                            data-bs-target="#informPositiveModal">
                                        Inform Positive Test
                                    </button>

                                    <!-- Modal for Inform Positive Test -->
                                    <div class="modal fade" id="informPositiveModal" tabindex="-1"
                                         aria-labelledby="informPositiveLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="informPositiveLabel">Report Positive
                                                        COVID-19 Test</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{ route('infectionReports.store') }}" method="POST"
                                                          enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="test_date" class="form-label">Date of Positive
                                                                Test</label>
                                                            <input type="date" class="form-control" id="test_date"
                                                                   name="test_date" max="{{ date('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="proof" class="form-label">Upload Proof
                                                                (optional)</label>
                                                            <input type="file" class="form-control" id="proof" name="proof">
                                                        </div>
                                                        <button type="submit" class="btn btn-warning">Submit</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
