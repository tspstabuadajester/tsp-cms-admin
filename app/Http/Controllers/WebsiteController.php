<?php

namespace App\Http\Controllers;

use App\Http\Requests\Website\StoreWebsiteRequest;
use App\Models\Business;
use App\Models\Website;
use App\Support\BusinessUserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WebsiteController extends Controller
{
    /**
     * Display a listing of websites.
     */
    public function index(): Response
    {
        return Inertia::render('Websites/Index', [
            'websites' => $this->scopedWebsitesQuery()
                ->orderBy('name')
                ->paginate(12, ['id', 'uuid', 'name', 'slug', 'primary_domain', 'logo', 'status', 'created_at'])
                ->withQueryString(),
        ]);
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
        $logoFilename = null;

        try {
            DB::transaction(function () use ($request, $validated, &$logoFilename): void {
                $website = Website::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => $validated['name'],
                    'slug' => $validated['slug'],
                    'primary_domain' => $validated['primary_domain'] ?? null,
                    'business_id' => $this->resolveBusinessId($validated['business_id'] ?? null),
                    'status' => $validated['status'],
                ]);

                $logoFilename = $this->storeLogo($request->file('logo'));

                if ($logoFilename !== null) {
                    $website->update(['logo' => $logoFilename]);
                }
            });
        } catch (Throwable $exception) {
            $this->deleteStoredLogo($logoFilename);

            throw $exception;
        }

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
     * @return Builder<Website>
     */
    private function scopedWebsitesQuery(): Builder
    {
        return Website::query()->forBusiness($this->authBusinessId());
    }

    private function authBusinessId(): ?int
    {
        return auth()->user()?->business_id;
    }

    private function storeLogo(?UploadedFile $logo): ?string
    {
        if ($logo === null) {
            return null;
        }

        $extension = $logo->extension() ?: 'jpg';
        $filename = Str::uuid().'.'.$extension;

        if ($logo->storeAs('logos', $filename, 'public') === false) {
            throw ValidationException::withMessages([
                'logo' => 'The logo could not be uploaded. Please try again.',
            ]);
        }

        return $filename;
    }

    private function deleteStoredLogo(?string $filename): void
    {
        if ($filename === null) {
            return;
        }

        Storage::disk('public')->delete('logos/'.$filename);
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
