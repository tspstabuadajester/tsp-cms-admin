<?php

namespace Tests\Feature\User;

use App\Models\Business;
use App\Models\User;
use App\Support\AssignableUserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAssignableRoles();
    }

    private function validPassword(): string
    {
        return 'Password1!';
    }

    private function seedAssignableRoles(): void
    {
        foreach (AssignableUserRoles::NAMES as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Super-admin treatment: no business_id (null).
     */
    private function createSettingsManager(array $attributes = []): User
    {
        Permission::firstOrCreate([
            'name' => 'settings.manage',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(array_merge([
            'business_id' => null,
        ], $attributes));

        $user->givePermissionTo('settings.manage');

        return $user;
    }

    /**
     * Business-scoped settings manager.
     */
    private function createScopedSettingsManager(?Business $business = null): User
    {
        $business ??= Business::factory()->create();

        return $this->createSettingsManager([
            'business_id' => $business->id,
        ]);
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
            'role' => 'content-manager',
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
            'role' => $user->getRoleNames()->first() ?? 'content-manager',
            'password' => '',
            'password_confirmation' => '',
        ], $overrides);
    }

    private function assignRole(User $user, string $role = 'content-manager'): void
    {
        if ($user->roles()->count() === 0) {
            $user->assignRole($role);
        }
    }

    public function test_guests_are_redirected_from_user_routes(): void
    {
        $user = User::factory()->create();

        $this->get(route('users'))->assertRedirect(route('login'));
        $this->get(route('users.create'))->assertRedirect(route('login'));
        $this->post(route('users.store'), $this->validStorePayload())->assertRedirect(route('login'));
        $this->get(route('users.edit', $user))->assertRedirect(route('login'));
        $this->put(route('users.update', $user), $this->validUpdatePayload($user))->assertRedirect(route('login'));
    }

    public function test_users_without_settings_manage_permission_cannot_access_user_routes(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $this->actingAs($user);

        $this->get(route('users'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->post(route('users.store'), $this->validStorePayload())->assertForbidden();
        $this->get(route('users.edit', $targetUser))->assertForbidden();
        $this->put(route('users.update', $targetUser), $this->validUpdatePayload($targetUser))->assertForbidden();
    }

    public function test_authenticated_users_can_view_user_index(): void
    {
        $admin = $this->createSettingsManager();

        $response = $this->actingAs($admin)->get(route('users'));

        $response->assertOk();
    }

    public function test_super_admin_sees_all_users_on_index(): void
    {
        $business = Business::factory()->create();

        User::factory()->count(2)->create(['business_id' => $business->id]);
        $unscopedUser = User::factory()->create(['business_id' => null]);

        $admin = $this->createSettingsManager();

        $this->actingAs($admin)
            ->get(route('users'))
            ->assertOk()
            ->assertViewHas('page', function (array $page) use ($unscopedUser): bool {
                $userIds = collect($page['props']['users']['data'])->pluck('id')->all();

                return $page['component'] === 'User/Index'
                    && count($userIds) === 4
                    && $page['props']['users']['per_page'] === 10
                    && in_array($unscopedUser->id, $userIds, true);
            });
    }

    public function test_business_scoped_user_only_sees_users_in_their_business_on_index(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $inBusiness = User::factory()->create(['business_id' => $business->id]);
        $outsideUser = User::factory()->create(['business_id' => $otherBusiness->id]);
        $unscopedUser = User::factory()->create(['business_id' => null]);

        $scopedManager = $this->createScopedSettingsManager($business);

        $this->actingAs($scopedManager)
            ->get(route('users'))
            ->assertOk()
            ->assertViewHas('page', function (array $page) use ($inBusiness, $outsideUser, $unscopedUser, $scopedManager): bool {
                $userIds = collect($page['props']['users']['data'])->pluck('id')->all();

                return $page['component'] === 'User/Index'
                    && count($userIds) === 2
                    && in_array($inBusiness->id, $userIds, true)
                    && in_array($scopedManager->id, $userIds, true)
                    && ! in_array($outsideUser->id, $userIds, true)
                    && ! in_array($unscopedUser->id, $userIds, true);
            });
    }

    public function test_business_scoped_user_cannot_edit_user_outside_their_business(): void
    {
        $business = Business::factory()->create();
        $scopedManager = $this->createScopedSettingsManager($business);
        $outsideUser = User::factory()->create([
            'business_id' => Business::factory()->create()->id,
        ]);
        $this->assignRole($outsideUser);

        $this->actingAs($scopedManager)
            ->get(route('users.edit', $outsideUser))
            ->assertNotFound();

        $this->actingAs($scopedManager)
            ->put(route('users.update', $outsideUser), $this->validUpdatePayload($outsideUser, [
                'name' => 'Hacked Name',
            ]))
            ->assertNotFound();
    }

    public function test_business_scoped_user_creates_users_in_their_business(): void
    {
        Storage::fake('public');

        $business = Business::factory()->create();
        $scopedManager = $this->createScopedSettingsManager($business);

        $this->actingAs($scopedManager)
            ->post(route('users.store'), $this->validStorePayload([
                'name' => 'Scoped User',
                'email' => 'scoped-user@example.com',
                'business_id' => Business::factory()->create()->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users'));

        $createdUser = User::query()->where('email', 'scoped-user@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertSame($business->id, $createdUser->business_id);
        $this->assertTrue($createdUser->hasRole('content-manager'));
    }

    public function test_authenticated_users_can_view_create_user_page(): void
    {
        $admin = $this->createSettingsManager();

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_create_a_user(): void
    {
        Storage::fake('public');

        $admin = $this->createSettingsManager();

        $response = $this->actingAs($admin)->post(route('users.store'), $this->validStorePayload([
            'name' => 'New User',
            'email' => 'new-user@example.com',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users'))
            ->assertSessionHas('toast', [
                'message' => 'User created successfully.',
                'variant' => 'success',
            ]);

        $createdUser = User::query()->where('email', 'new-user@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertSame('New User', $createdUser->name);
        $this->assertNotNull($createdUser->avatar);
        $this->assertTrue($createdUser->hasRole('content-manager'));
        Storage::disk('public')->assertExists('avatars/'.$createdUser->avatar);
    }

    public function test_user_creation_requires_valid_data(): void
    {
        $admin = $this->createSettingsManager();
        User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);

        $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), $this->validStorePayload([
                'email' => 'existing@example.com',
            ]))
            ->assertSessionHasErrors(['email']);

        $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), $this->validStorePayload([
                'role' => 'super-admin',
            ]))
            ->assertSessionHasErrors(['role']);
    }

    public function test_authenticated_users_can_view_edit_user_page(): void
    {
        $admin = $this->createSettingsManager();
        $user = User::factory()->create();
        $this->assignRole($user);

        $response = $this->actingAs($admin)->get(route('users.edit', $user));

        $response->assertOk();
    }

    public function test_authenticated_users_can_update_a_user(): void
    {
        Storage::fake('public');

        $admin = $this->createSettingsManager();
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => 'active',
        ]);
        $this->assignRole($user);

        $response = $this->actingAs($admin)->put(route('users.update', $user), $this->validUpdatePayload($user, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => 'inactive',
            'role' => 'business-admin',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users'))
            ->assertSessionHas('toast', [
                'message' => 'User updated successfully.',
                'variant' => 'success',
            ]);

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('inactive', $user->status);
        $this->assertTrue($user->hasRole('business-admin'));
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_update_keeps_email_verification_when_email_is_unchanged(): void
    {
        $admin = $this->createSettingsManager();
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'status' => 'active',
        ]);
        $this->assignRole($user);

        $verifiedAt = $user->email_verified_at;

        $this->actingAs($admin)
            ->put(route('users.update', $user), $this->validUpdatePayload($user, [
                'name' => 'Renamed User',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users'));

        $user->refresh();

        $this->assertSame('Renamed User', $user->name);
        $this->assertTrue($user->email_verified_at->equalTo($verifiedAt));
    }

    public function test_user_update_can_change_password(): void
    {
        $admin = $this->createSettingsManager();
        $user = User::factory()->create();
        $this->assignRole($user);

        $this->actingAs($admin)
            ->put(route('users.update', $user), $this->validUpdatePayload($user, [
                'password' => $this->validPassword(),
                'password_confirmation' => $this->validPassword(),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users'));

        $this->assertTrue(Hash::check($this->validPassword(), $user->refresh()->password));
    }

    public function test_user_update_regenerates_avatar_when_name_changes(): void
    {
        Storage::fake('public');

        $admin = $this->createSettingsManager();
        $user = User::factory()->create([
            'name' => 'Original Name',
            'avatar' => 'existing-avatar.svg',
        ]);
        $this->assignRole($user);

        Storage::disk('public')->put('avatars/existing-avatar.svg', '<svg>original</svg>');

        $this->actingAs($admin)
            ->put(route('users.update', $user), $this->validUpdatePayload($user, [
                'name' => 'Updated Name',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users'));

        $user->refresh();

        $this->assertSame('existing-avatar.svg', $user->avatar);
        Storage::disk('public')->assertExists('avatars/existing-avatar.svg');
        $this->assertNotSame('<svg>original</svg>', Storage::disk('public')->get('avatars/existing-avatar.svg'));
    }

    public function test_user_update_requires_valid_data(): void
    {
        $admin = $this->createSettingsManager();
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->assignRole($user);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->put(route('users.update', $user), [])
            ->assertSessionHasErrors(['name', 'email', 'status', 'role']);

        $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->put(route('users.update', $user), $this->validUpdatePayload($user, [
                'email' => 'taken@example.com',
            ]))
            ->assertSessionHasErrors(['email']);

        $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->put(route('users.update', $user), $this->validUpdatePayload($user, [
                'status' => 'invalid-status',
            ]))
            ->assertSessionHasErrors(['status']);

        $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->put(route('users.update', $user), $this->validUpdatePayload($user, [
                'role' => 'super-admin',
            ]))
            ->assertSessionHasErrors(['role']);
    }
}
