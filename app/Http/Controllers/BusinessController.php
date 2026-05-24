<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    /**
     * Display a listing of businesses.
     */
    public function index(): Response
    {
        return Inertia::render('Businesses/Index', [
            'businesses' => Business::query()
                ->orderBy('name')
                ->get(['id', 'uuid', 'name', 'address', 'email', 'created_at']),
        ]);
    }
}
