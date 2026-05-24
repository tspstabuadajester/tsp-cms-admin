<?php

namespace App\Http\Controllers;

use App\Http\Requests\Business\StoreBusinessRequest;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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
                ->get(['id', 'uuid', 'name', 'address', 'email', 'status', 'created_at']),
        ]);
    }

    /**
     * Show the form for creating a new business.
     */
    public function create(): Response
    {
        return Inertia::render('Businesses/Create');
    }

    /**
     * Store a newly created business.
     */
    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Business::create([
            'uuid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        return to_route('business')->with('toast', [
            'message' => 'Business created successfully.',
            'variant' => 'success',
        ]);
    }

    /**
     * Show the form for editing the specified business.
     */
    public function edit(Business $business): Response
    {
        return Inertia::render('Businesses/Edit', [
            'business' => $business->only(['id', 'name', 'address', 'phone', 'email', 'status']),
        ]);
    }

    /**
     * Update the specified business.
     */
    public function update(UpdateBusinessRequest $request, Business $business): RedirectResponse
    {
        $validated = $request->validated();

        $business->fill([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
        ]);

        $business->save();

        return to_route('business')->with('toast', [
            'message' => 'Business updated successfully.',
            'variant' => 'success',
        ]);
    }
}
