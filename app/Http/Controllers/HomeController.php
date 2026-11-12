<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('index', [
            'application' => 'Community Services Management System',
            'sprint' => 'Sprint 6 - Final Release',
            'technology' => 'PHP with Laravel',
            'version' => config('app.release_version'),
        ]);
    }
}
