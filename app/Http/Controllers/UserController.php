<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Business;
use App\Models\User;
use App\Support\AssignableUserRoles;
use App\Support\BusinessUserScope;
use Illuminate\Database\Eloquent\Builder;
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
            'users' => $this->scopedUsersQuery()
                ->orderBy('name')
                ->paginate(10, ['id', 'name', 'email', 'avatar', 'status', 'created_at'])
                ->withQueryString(),
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
            'showBusinessField' => ! $this->isBusinessScoped(),
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
            'business_id' => $this->resolveBusinessId($validated['business_id'] ?? null),
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
        $this->ensureUserInScope($user);

        $isSuperAdmin = $user->hasRole('super-admin');

        return Inertia::render('User/Edit', [
            'user' => array_merge(
                $user->only(['id', 'name', 'email', 'avatar', 'status', 'business_id']),
                ['role' => $user->getRoleNames()->first()],
            ),
            'businesses' => $this->businessesForSelect($user->business_id),
            'roles' => AssignableUserRoles::forSelect(),
            'roleEditable' => ! $isSuperAdmin,
            'showBusinessField' => ! $this->isBusinessScoped(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureUserInScope($user);

        $validated = $request->validated();
        $nameChanged = $validated['name'] !== $user->name;

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'business_id' => $this->resolveBusinessId($validated['business_id'] ?? null),
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
     * @return Builder<User>
     */
    private function scopedUsersQuery(): Builder
    {
        return User::query()->forBusiness($this->authBusinessId());
    }

    private function isBusinessScoped(): bool
    {
        return BusinessUserScope::isScoped(auth()->user());
    }

    private function authBusinessId(): ?int
    {
        return BusinessUserScope::scopedBusinessId(auth()->user());
    }

    private function ensureUserInScope(User $user): void
    {
        abort_unless(
            BusinessUserScope::userBelongsToScope($user, auth()->user()),
            403,
        );
    }

    private function resolveBusinessId(?int $businessId): ?int
    {
        return $this->authBusinessId() ?? $businessId;
    }

    /**
     * Active businesses for select dropdowns; includes the user's current business when inactive.
     *
     * @return Collection<int, Business>
     */
    private function businessesForSelect(?int $includeBusinessId = null)
    {
        if ($this->isBusinessScoped()) {
            return Business::query()
                ->where('id', $this->authBusinessId())
                ->get(['id', 'name']);
        }

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
