<?php

namespace Tests\Feature\Website;

use App\Models\Business;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WebsiteFileContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

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

    public function test_guests_are_redirected_from_website_file_content_route(): void
    {
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        $this->get(route('websites.files', $website))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_cannot_access_website_file_content(): void
    {
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('websites.files', $website))
            ->assertForbidden();
    }

    public function test_authenticated_users_can_view_html_pages_in_template_directory(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'name' => 'Example Site',
            'slug' => 'example-site',
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/index.html', '<html></html>');
        Storage::disk('local')->put('sites/example-site/about.html', '<html></html>');
        Storage::disk('local')->put('sites/example-site/content.json', '{}');

        $this->actingAs($manager)
            ->get(route('websites.files', $website))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/FileContent/Index')
                ->where('website.name', 'Example Site')
                ->has('pages', 2)
                ->where('pages.0.name', 'about.html')
                ->where('pages.1.name', 'index.html')
            );
    }

    public function test_business_scoped_user_cannot_access_file_content_outside_their_business(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $scopedManager = $this->createWebsiteManager(['business_id' => $business->id]);
        $outsideWebsite = Website::factory()->create([
            'business_id' => $otherBusiness->id,
            'template_path' => 'sites/outside-site',
        ]);

        $this->actingAs($scopedManager)
            ->get(route('websites.files', $outsideWebsite))
            ->assertNotFound();
    }
}
