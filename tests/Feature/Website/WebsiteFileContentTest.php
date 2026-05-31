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
        $this->get(route('websites.preview', $website))->assertRedirect(route('login'));
        $this->get(route('websites.preview.asset', ['website' => $website, 'path' => 'assets/style.css']))
            ->assertRedirect(route('login'));
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

    public function test_authenticated_users_can_view_template_items_in_directory(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'name' => 'Example Site',
            'slug' => 'example-site',
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->makeDirectory('sites/example-site/assets');
        Storage::disk('local')->put('sites/example-site/index.html', '<html></html>');
        Storage::disk('local')->put('sites/example-site/about.html', '<html></html>');
        Storage::disk('local')->put('sites/example-site/style.css', 'body {}');
        Storage::disk('local')->put('sites/example-site/app.js', 'console.log("hi")');
        Storage::disk('local')->put('sites/example-site/content.json', '{}');

        $this->actingAs($manager)
            ->get(route('websites.files', $website))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/FileContent/Index')
                ->where('website.name', 'Example Site')
                ->has('items', 6)
                ->where('items.0.name', 'assets')
                ->where('items.0.type', 'folder')
                ->where('items.1.name', 'about.html')
                ->where('items.1.type', 'html')
                ->where('items.2.name', 'index.html')
                ->where('items.2.type', 'html')
                ->where('items.3.name', 'style.css')
                ->where('items.3.type', 'css')
                ->where('items.4.name', 'app.js')
                ->where('items.4.type', 'javascript')
                ->where('items.5.name', 'content.json')
                ->where('items.5.type', 'json')
                ->where('can_preview', true)
            );
    }

    public function test_authenticated_users_can_open_json_editor(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'name' => 'Example Site',
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'site' => [
                'title' => 'Elevate Mental Health and Wellness',
                'brandName' => 'Elevate',
                'logo' => 'assets/logo.png',
            ],
            'nav' => [
                'ctaLabel' => 'Request Appointment',
                'ctaHref' => '#appointment',
            ],
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->get(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/FileContent/JsonEditor')
                ->where('website.name', 'Example Site')
                ->where('file.name', 'content.json')
                ->where('can_preview', false)
                ->where('json_error', null)
                ->has('sections', 2)
                ->where('sections.0.key', 'site')
                ->has('sections.0.fields', 3)
                ->where('sections.0.fields.0.path', 'title')
                ->where('sections.0.fields.0.value', 'Elevate Mental Health and Wellness')
                ->where('sections.0.fields.1.path', 'brandName')
                ->where('sections.0.fields.1.value', 'Elevate')
                ->where('sections.1.key', 'nav')
                ->where('sections.1.fields.0.path', 'ctaLabel')
                ->where('sections.1.fields.0.value', 'Request Appointment')
            );
    }

    public function test_authenticated_users_can_save_json_file_changes(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'site' => [
                'title' => 'Old Title',
                'enabled' => true,
                'count' => 3,
            ],
            'version' => '1.0.0',
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->put(route('websites.files.json.update', ['website' => $website, 'path' => 'content.json']), [
                'sections' => [
                    [
                        'key' => 'site',
                        'fields' => [
                            ['path' => 'title', 'value' => 'New Title'],
                            ['path' => 'enabled', 'value' => 'false'],
                            ['path' => 'count', 'value' => '9'],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->assertSessionHas('status');

        $saved = json_decode(Storage::disk('local')->get('sites/example-site/content.json'), true);

        $this->assertSame('New Title', $saved['site']['title']);
        $this->assertFalse($saved['site']['enabled']);
        $this->assertSame(9, $saved['site']['count']);
        $this->assertSame('1.0.0', $saved['version']);
    }

    public function test_json_editor_skips_scalar_top_level_sections_without_empty_paths(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'site' => [
                'title' => 'Valid Title',
            ],
            'version' => '1.0.0',
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->get(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/FileContent/JsonEditor')
                ->has('sections', 1)
                ->where('sections.0.key', 'site')
                ->has('sections.0.fields', 1)
                ->where('sections.0.fields.0.path', 'title')
                ->where('sections.0.fields.0.value', 'Valid Title')
                ->where('json_error', null)
            );
    }

    public function test_json_editor_returns_error_when_all_top_level_sections_are_invalid(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'version' => '1.0.0',
            'enabled' => true,
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->get(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/FileContent/JsonEditor')
                ->has('sections', 0)
                ->where('json_error', 'Each top-level key must be a JSON object with nested fields.')
            );
    }

    public function test_json_editor_returns_not_found_for_non_json_files(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/style.css', 'body {}');

        $this->actingAs($manager)
            ->get(route('websites.files.json', ['website' => $website, 'path' => 'style.css']))
            ->assertNotFound();
    }

    public function test_authenticated_users_can_preview_index_html(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/index.html', '<html><head></head><body>Hello</body></html>');

        $this->actingAs($manager)
            ->get(route('websites.preview', $website))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('Hello', false)
            ->assertSee('<base href="'.route('websites.preview', $website).'/"', false);
    }

    public function test_authenticated_users_can_load_preview_assets(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/assets/style.css', 'body { color: red; }');

        $response = $this->actingAs($manager)
            ->get(route('websites.preview.asset', ['website' => $website, 'path' => 'assets/style.css']));

        $response->assertOk();
        $this->assertSame('body { color: red; }', $response->streamedContent());
    }

    public function test_preview_asset_route_blocks_path_traversal(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/index.html', '<html></html>');
        Storage::disk('local')->put('sites/secret.css', 'secret');

        $this->actingAs($manager)
            ->get(route('websites.preview.asset', ['website' => $website, 'path' => '../secret.css']))
            ->assertNotFound();
    }

    public function test_preview_returns_not_found_when_index_html_is_missing(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        $this->actingAs($manager)
            ->get(route('websites.preview', $website))
            ->assertNotFound();
    }

    public function test_file_content_index_marks_preview_unavailable_without_index_html(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/about.html', '<html></html>');

        $this->actingAs($manager)
            ->get(route('websites.files', $website))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('can_preview', false));
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
