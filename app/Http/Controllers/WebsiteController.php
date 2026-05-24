<?php

namespace App\Http\Controllers;

use App\Http\Requests\Website\StoreWebsiteRequest;
use App\Models\Business;
use App\Models\Website;
use App\Support\BusinessUserScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    /**
     * Display a listing of websites.
     */
    public function index(): Response
    {
        return Inertia::render('Websites/Index');
    }

    /**
     * Show the form for creating a new website.
     */
    public function create(): Response
    {
        return Inertia::render('Websites/Create', [
            'businesses' => $this->businessesForSelect(),
            'showBusinessField' => ! BusinessUserScope::isScoped(auth()->user()),
        ]);
    }

    /**
     * Store a newly created website.
     */
    public function store(StoreWebsiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Website::create([
            'uuid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'primary_domain' => $validated['primary_domain'] ?? null,
            'business_id' => $this->resolveBusinessId($validated['business_id'] ?? null),
            'status' => $validated['status'],
        ]);

        return to_route('websites')->with('toast', [
            'message' => 'Website created successfully.',
            'variant' => 'success',
        ]);
    }

    private function resolveBusinessId(?int $businessId): ?int
    {
        return BusinessUserScope::scopedBusinessId(auth()->user()) ?? $businessId;
    }

    /**
     * @return Collection<int, Business>
     */
    private function businessesForSelect()
    {
        $scopedBusinessId = BusinessUserScope::scopedBusinessId(auth()->user());

        if ($scopedBusinessId !== null) {
            return Business::query()
                ->where('id', $scopedBusinessId)
                ->get(['id', 'name']);
        }

        return Business::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
