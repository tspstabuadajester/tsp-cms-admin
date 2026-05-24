<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function validPassword(): string
    {
        return 'Password1!';
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => $this->validPassword(),
            'password_confirmation' => $this->validPassword(),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUpdatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status ?? 'active',
            'password' => '',
            'password_confirmation' => '',
        ], $overrides);
    }

    public function test_guests_are_redirected_from_user_routes(): void
    {
        $user = User::factory()->create();

        $this->get(route('user'))->assertRedirect(route('login'));
        $this->get(route('user.create'))->assertRedirect(route('login'));
        $this->post(route('user.store'), $this->validStorePayload())->assertRedirect(route('login'));
        $this->get(route('user.edit', $user))->assertRedirect(route('login'));
        $this->put(route('user.update', $user), $this->validUpdatePayload($user))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_user_index(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('user'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_view_create_user_page(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('user.create'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_create_a_user(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('user.store'), $this->validStorePayload([
            'name' => 'New User',
            'email' => 'new-user@example.com',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user'))
            ->assertSessionHas('toast', [
                'message' => 'User created successfully.',
                'variant' => 'success',
            ]);

        $createdUser = User::query()->where('email', 'new-user@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertSame('New User', $createdUser->name);
        $this->assertNotNull($createdUser->avatar);
        Storage::disk('public')->assertExists('avatars/'.$createdUser->avatar);
    }

    public function test_user_creation_requires_valid_data(): void
    {
        $admin = User::factory()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin)
            ->from(route('user.create'))
            ->post(route('user.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->actingAs($admin)
            ->from(route('user.create'))
            ->post(route('user.store'), $this->validStorePayload([
                'email' => 'existing@example.com',
            ]))
            ->assertSessionHasErrors(['email']);
    }

    public function test_authenticated_users_can_view_edit_user_page(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('user.edit', $user));

        $response->assertOk();
    }

    public function test_authenticated_users_can_update_a_user(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('user.update', $user), $this->validUpdatePayload($user, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => 'inactive',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user'))
            ->assertSessionHas('toast', [
                'message' => 'User updated successfully.',
                'variant' => 'success',
            ]);

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('inactive', $user->status);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_update_keeps_email_verification_when_email_is_unchanged(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'status' => 'active',
        ]);

        $verifiedAt = $user->email_verified_at;

        $this->actingAs($admin)
            ->put(route('user.update', $user), $this->validUpdatePayload($user, [
                'name' => 'Renamed User',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user'));

        $user->refresh();

        $this->assertSame('Renamed User', $user->name);
        $this->assertTrue($user->email_verified_at->equalTo($verifiedAt));
    }

    public function test_user_update_can_change_password(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('user.update', $user), $this->validUpdatePayload($user, [
                'password' => $this->validPassword(),
                'password_confirmation' => $this->validPassword(),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user'));

        $this->assertTrue(Hash::check($this->validPassword(), $user->refresh()->password));
    }

    public function test_user_update_regenerates_avatar_when_name_changes(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $user = User::factory()->create([
            'name' => 'Original Name',
            'avatar' => 'existing-avatar.svg',
        ]);

        Storage::disk('public')->put('avatars/existing-avatar.svg', '<svg>original</svg>');

        $this->actingAs($admin)
            ->put(route('user.update', $user), $this->validUpdatePayload($user, [
                'name' => 'Updated Name',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user'));

        $user->refresh();

        $this->assertSame('existing-avatar.svg', $user->avatar);
        Storage::disk('public')->assertExists('avatars/existing-avatar.svg');
        $this->assertNotSame('<svg>original</svg>', Storage::disk('public')->get('avatars/existing-avatar.svg'));
    }

    public function test_user_update_requires_valid_data(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['email' => 'user@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->from(route('user.edit', $user))
            ->put(route('user.update', $user), [])
            ->assertSessionHasErrors(['name', 'email', 'status']);

        $this->actingAs($admin)
            ->from(route('user.edit', $user))
            ->put(route('user.update', $user), $this->validUpdatePayload($user, [
                'email' => 'taken@example.com',
            ]))
            ->assertSessionHasErrors(['email']);

        $this->actingAs($admin)
            ->from(route('user.edit', $user))
            ->put(route('user.update', $user), $this->validUpdatePayload($user, [
                'status' => 'invalid-status',
            ]))
            ->assertSessionHasErrors(['status']);
    }
}
