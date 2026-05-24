<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
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
                ->get(['id', 'name', 'email', 'avatar', 'email_verified_at', 'created_at']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('User/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'avatar' => $this->generateAvatar($validated['name']),
        ]);

        return to_route('user');
    }

    /**
     * Generate and store an avatar image for the user.
     */
    private function generateAvatar(string $identifier): string
    {
        $filename = Str::uuid().'.svg';
        $relativePath = 'avatars/'.$filename;

        Storage::disk('public')->makeDirectory('avatars');

        Storage::disk('public')->put(
            $relativePath,
            Avatar::create($identifier)->toSvg(),
        );

        return $filename;
    }
}
