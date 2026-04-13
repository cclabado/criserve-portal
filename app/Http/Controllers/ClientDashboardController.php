<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class ClientDashboardController extends Controller
{

    public function index()
    {
        $applications = Application::where('user_id', auth()->id())
            ->latest()
            ->get();

        $latestApplication = $applications->first();

        return view('client.dashboard', compact('applications', 'latestApplication'));
    }
}