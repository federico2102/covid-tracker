<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View|Factory|Application
    {
        // Check if the user is currently checked in
        $isCheckedIn = CheckIn::where('user_id', auth()->id())
            ->whereNull('check_out_time')
            ->exists();

        // Pass the $isCheckedIn variable to the home view
        return view('home', compact('isCheckedIn'));
    }
}

