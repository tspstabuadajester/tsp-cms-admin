<?php

namespace Tests\Feature\Website;

use App\Models\Business;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    private function createWebsiteManager(array $attributes = []): User
    {
        Permission::firstOrCreate([
            'name' => 'websites.manage',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create($attributes);
        $user->givePermissionTo('websites.manage');

        return $user;
    }

    private function createSuperAdminWebsiteManager(): User
    {
        return $this->createWebsiteManager([
            'business_id' => null,
        ]);
    }

    private function createScopedWebsiteManager(?Business $business = null): User
    {
        $business ??= Business::factory()->create();

        return $this->createWebsiteManager([
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
            'name' => 'Acme Site',
            'slug' => 'acme-site',
            'primary_domain' => 'acme.example.com',
            'status' => 'active',
        ], $overrides);
    }

    public function test_guests_are_redirected_from_website_routes(): void
    {
        $this->get(route('websites'))->assertRedirect(route('login'));
        $this->get(route('websites.create'))->assertRedirect(route('login'));
        $this->post(route('websites.store'), $this->validStorePayload())->assertRedirect(route('login'));
    }

    public function test_users_without_websites_manage_permission_cannot_access_website_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(route('websites'))->assertForbidden();
        $this->get(route('websites.create'))->assertForbidden();
        $this->post(route('websites.store'), $this->validStorePayload())->assertForbidden();
    }

    public function test_authenticated_users_can_view_website_index(): void
    {
        $manager = $this->createSuperAdminWebsiteManager();
        Website::factory()->count(3)->create();

        $this->actingAs($manager)
            ->get(route('websites'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/Index')
                ->has('websites.data', 3)
            );
    }

    public function test_business_scoped_user_only_sees_their_business_websites_on_index(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $scopedManager = $this->createScopedWebsiteManager($business);

        Website::factory()->create(['business_id' => $business->id, 'name' => 'Own Site']);
        Website::factory()->create(['business_id' => $otherBusiness->id, 'name' => 'Other Site']);

        $this->actingAs($scopedManager)
            ->get(route('websites'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/Index')
                ->has('websites.data', 1)
                ->where('websites.data.0.name', 'Own Site')
            );
    }

    public function test_super_admin_can_create_a_website_for_a_business(): void
    {
        $superAdmin = $this->createSuperAdminWebsiteManager();
        $business = Business::factory()->create();

        $this->actingAs($superAdmin)
            ->post(route('websites.store'), $this->validStorePayload([
                'business_id' => $business->id,
                'slug' => 'super-admin-site',
                'primary_domain' => 'super.example.com',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('websites'))
            ->assertSessionHas('toast', [
                'message' => 'Website created successfully.',
                'variant' => 'success',
            ]);

        $website = Website::query()->where('slug', 'super-admin-site')->first();

        $this->assertNotNull($website);
        $this->assertSame($business->id, $website->business_id);
        $this->assertSame('super.example.com', $website->primary_domain);
    }

    public function test_super_admin_requires_business_id_when_creating_a_website(): void
    {
        $superAdmin = $this->createSuperAdminWebsiteManager();

        $this->actingAs($superAdmin)
            ->from(route('websites.create'))
            ->post(route('websites.store'), $this->validStorePayload([
                'slug' => 'missing-business',
                'primary_domain' => 'missing.example.com',
            ]))
            ->assertSessionHasErrors(['business_id']);

        $this->assertDatabaseMissing('websites', [
            'slug' => 'missing-business',
        ]);
    }

    public function test_super_admin_cannot_assign_an_inactive_business(): void
    {
        $superAdmin = $this->createSuperAdminWebsiteManager();
        $inactiveBusiness = Business::factory()->inactive()->create();

        $this->actingAs($superAdmin)
            ->from(route('websites.create'))
            ->post(route('websites.store'), $this->validStorePayload([
                'business_id' => $inactiveBusiness->id,
                'slug' => 'inactive-business-site',
                'primary_domain' => 'inactive.example.com',
            ]))
            ->assertSessionHasErrors(['business_id']);

        $this->assertDatabaseMissing('websites', [
            'slug' => 'inactive-business-site',
        ]);
    }

    public function test_business_scoped_user_creates_website_in_their_business(): void
    {
        $business = Business::factory()->create();
        $scopedManager = $this->createScopedWebsiteManager($business);

        $this->actingAs($scopedManager)
            ->post(route('websites.store'), $this->validStorePayload([
                'slug' => 'scoped-site',
                'primary_domain' => 'scoped.example.com',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('websites'));

        $website = Website::query()->where('slug', 'scoped-site')->first();

        $this->assertNotNull($website);
        $this->assertSame($business->id, $website->business_id);
    }

    public function test_business_scoped_user_cannot_create_website_for_another_business(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $scopedManager = $this->createScopedWebsiteManager($business);

        $this->actingAs($scopedManager)
            ->post(route('websites.store'), $this->validStorePayload([
                'business_id' => $otherBusiness->id,
                'slug' => 'cross-business-site',
                'primary_domain' => 'cross.example.com',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('websites'));

        $website = Website::query()->where('slug', 'cross-business-site')->first();

        $this->assertNotNull($website);
        $this->assertSame($business->id, $website->business_id);
        $this->assertNotSame($otherBusiness->id, $website->business_id);
    }
}
