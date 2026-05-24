<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Business;
use App\Models\User;
use App\Support\AssignableUserRoles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravolt\Avatar\Facade as Avatar;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): Response
    {
        return Inertia::render('User/Index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'avatar', 'status', 'created_at']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('User/Create', [
            'businesses' => $this->businessesForSelect(),
            'roles' => AssignableUserRoles::forSelect(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'avatar' => $this->generateAvatar($validated['name']),
            'business_id' => $validated['business_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        return to_route('user')->with('toast', [
            'message' => 'User created successfully.',
            'variant' => 'success',
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $isSuperAdmin = $user->hasRole('super-admin');

        return Inertia::render('User/Edit', [
            'user' => array_merge(
                $user->only(['id', 'name', 'email', 'avatar', 'status', 'business_id']),
                ['role' => $user->getRoleNames()->first()],
            ),
            'businesses' => $this->businessesForSelect($user->business_id),
            'roles' => AssignableUserRoles::forSelect(),
            'roleEditable' => ! $isSuperAdmin,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $nameChanged = $validated['name'] !== $user->name;

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'business_id' => $validated['business_id'] ?? null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        if ($nameChanged) {
            $user->avatar = $this->generateAvatar($validated['name'], $user->avatar);
        }

        $user->save();

        if (! $user->hasRole('super-admin') && isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return to_route('user')->with('toast', [
            'message' => 'User updated successfully.',
            'variant' => 'success',
        ]);
    }

    /**
     * Active businesses for select dropdowns; includes the user's current business when inactive.
     *
     * @return Collection<int, Business>
     */
    private function businessesForSelect(?int $includeBusinessId = null)
    {
        return Business::query()
            ->where(function ($query) use ($includeBusinessId) {
                $query->where('status', 'active');

                if ($includeBusinessId !== null) {
                    $query->orWhere('id', $includeBusinessId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Generate and store an avatar image for the user.
     */
    private function generateAvatar(string $identifier, ?string $existingFilename = null): string
    {
        $filename = $existingFilename ?? Str::uuid().'.svg';
        $relativePath = 'avatars/'.$filename;

        Storage::disk('public')->makeDirectory('avatars');

        Storage::disk('public')->put(
            $relativePath,
            Avatar::create($identifier)->toSvg(),
        );

        return $filename;
    }
}
