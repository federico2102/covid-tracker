@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 text-center my-5">
                <h1 class="display-4">Welcome to the Covid Tracker App</h1>
                <p class="lead">Track your visits and ensure your safety.</p>
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg mt-4">Login</a>
                <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg mt-4">Register</a>
            </div>
        </div>
    </div>
@endsection
