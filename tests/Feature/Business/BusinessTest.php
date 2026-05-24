<?php

namespace Tests\Feature\Business;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    private function createBusinessManager(array $attributes = []): User
    {
        Permission::firstOrCreate([
            'name' => 'business.manage',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create($attributes);
        $user->givePermissionTo('business.manage');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Acme Corp',
            'address' => '123 Main St',
            'phone' => '555-0100',
            'email' => 'contact@acme.test',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUpdatePayload(Business $business, array $overrides = []): array
    {
        return array_merge([
            'name' => $business->name,
            'address' => $business->address,
            'phone' => $business->phone,
            'email' => $business->email,
            'status' => $business->status ?? 'active',
        ], $overrides);
    }

    public function test_guests_are_redirected_from_business_routes(): void
    {
        $business = Business::factory()->create();

        $this->get(route('business'))->assertRedirect(route('login'));
        $this->get(route('business.create'))->assertRedirect(route('login'));
        $this->post(route('business.store'), $this->validStorePayload())->assertRedirect(route('login'));
        $this->get(route('business.edit', $business))->assertRedirect(route('login'));
        $this->put(route('business.update', $business), $this->validUpdatePayload($business))->assertRedirect(route('login'));
    }

    public function test_users_without_business_manage_permission_cannot_access_business_routes(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        $this->actingAs($user);

        $this->get(route('business'))->assertForbidden();
        $this->get(route('business.create'))->assertForbidden();
        $this->post(route('business.store'), $this->validStorePayload())->assertForbidden();
        $this->get(route('business.edit', $business))->assertForbidden();
        $this->put(route('business.update', $business), $this->validUpdatePayload($business))->assertForbidden();
    }

    public function test_authenticated_users_can_view_business_index(): void
    {
        $manager = $this->createBusinessManager();
        Business::factory()->count(2)->create();

        $this->actingAs($manager)
            ->get(route('business'))
            ->assertOk()
            ->assertViewHas('page', function (array $page): bool {
                return $page['component'] === 'Businesses/Index'
                    && count($page['props']['businesses']['data']) === 2;
            });
    }

    public function test_authenticated_users_can_view_create_business_page(): void
    {
        $manager = $this->createBusinessManager();

        $this->actingAs($manager)
            ->get(route('business.create'))
            ->assertOk()
            ->assertViewHas('page', fn (array $page): bool => $page['component'] === 'Businesses/Create');
    }

    public function test_authenticated_users_can_create_a_business(): void
    {
        $manager = $this->createBusinessManager();

        $response = $this->actingAs($manager)->post(route('business.store'), $this->validStorePayload([
            'name' => 'New Business',
            'email' => 'new@business.test',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('business'))
            ->assertSessionHas('toast', [
                'message' => 'Business created successfully.',
                'variant' => 'success',
            ]);

        $business = Business::query()->where('email', 'new@business.test')->first();

        $this->assertNotNull($business);
        $this->assertSame('New Business', $business->name);
        $this->assertNotNull($business->uuid);
        $this->assertSame('active', $business->status);
    }

    public function test_business_creation_requires_valid_data(): void
    {
        $manager = $this->createBusinessManager();

        $this->actingAs($manager)
            ->from(route('business.create'))
            ->post(route('business.store'), [])
            ->assertSessionHasErrors(['name']);

        $this->actingAs($manager)
            ->from(route('business.create'))
            ->post(route('business.store'), $this->validStorePayload([
                'email' => 'not-an-email',
            ]))
            ->assertSessionHasErrors(['email']);
    }

    public function test_authenticated_users_can_view_edit_business_page(): void
    {
        $manager = $this->createBusinessManager();
        $business = Business::factory()->create();

        $this->actingAs($manager)
            ->get(route('business.edit', $business))
            ->assertOk()
            ->assertViewHas('page', function (array $page) use ($business): bool {
                return $page['component'] === 'Businesses/Edit'
                    && $page['props']['business']['id'] === $business->id;
            });
    }

    public function test_authenticated_users_can_update_a_business(): void
    {
        $manager = $this->createBusinessManager();
        $business = Business::factory()->create([
            'name' => 'Original Name',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->put(route('business.update', $business), $this->validUpdatePayload($business, [
            'name' => 'Updated Name',
            'status' => 'inactive',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('business'))
            ->assertSessionHas('toast', [
                'message' => 'Business updated successfully.',
                'variant' => 'success',
            ]);

        $business->refresh();

        $this->assertSame('Updated Name', $business->name);
        $this->assertSame('inactive', $business->status);
    }

    public function test_business_update_requires_valid_data(): void
    {
        $manager = $this->createBusinessManager();
        $business = Business::factory()->create();

        $this->actingAs($manager)
            ->from(route('business.edit', $business))
            ->put(route('business.update', $business), [])
            ->assertSessionHasErrors(['name', 'status']);

        $this->actingAs($manager)
            ->from(route('business.edit', $business))
            ->put(route('business.update', $business), $this->validUpdatePayload($business, [
                'status' => 'invalid-status',
            ]))
            ->assertSessionHasErrors(['status']);
    }
}
