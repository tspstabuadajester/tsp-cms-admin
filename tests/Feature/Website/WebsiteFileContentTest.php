<?php

namespace Tests\Feature\Website;

use App\Models\Business;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
                'links' => [
                    ['label' => 'Home', 'href' => '#home'],
                    ['label' => 'About', 'href' => '#about', 'suffix' => '▾'],
                ],
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
                ->where('sections.1.key', 'nav')
                ->where('sections.1.fields.0.path', 'ctaLabel')
                ->has('sections.1.arrays', 1)
                ->where('sections.1.arrays.0.key', 'links')
                ->has('sections.1.arrays.0.items', 2)
                ->where('sections.1.arrays.0.items.0.fields.0.key', 'label')
                ->where('sections.1.arrays.0.items.0.fields.0.value', 'Home')
                ->where('sections.1.arrays.0.items.1.fields.2.key', 'suffix')
                ->where('sections.1.arrays.0.items.1.fields.2.value', '▾')
            );
    }

    public function test_authenticated_users_can_upload_json_editor_images(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        $response = $this->actingAs($manager)->post(route('websites.files.assets.upload', $website), [
            'file' => UploadedFile::fake()->image('hero-banner.png', 200, 100),
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['path']);

        $storedPath = $response->json('path');

        $this->assertIsString($storedPath);
        $this->assertStringStartsWith('assets/', $storedPath);
        Storage::disk('local')->assertExists("sites/example-site/{$storedPath}");
    }

    public function test_json_editor_image_upload_assigns_extension_when_filename_has_none(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        $response = $this->actingAs($manager)->post(route('websites.files.assets.upload', $website), [
            'file' => UploadedFile::fake()->create('hero-banner', 100, 'image/jpeg'),
        ]);

        $storedPath = $response->json('path');

        $response->assertOk();
        $this->assertIsString($storedPath);
        $this->assertMatchesRegularExpression('/^assets\/hero-banner-[a-z0-9]{8}\.jpg$/', $storedPath);
        Storage::disk('local')->assertExists("sites/example-site/{$storedPath}");
    }

    public function test_json_editor_image_upload_rejects_non_images(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        $this->actingAs($manager)
            ->post(route('websites.files.assets.upload', $website), [
                'file' => UploadedFile::fake()->create('notes.txt', 100, 'text/plain'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_authenticated_users_can_save_json_array_groups(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'nav' => [
                'links' => [
                    ['label' => 'Home', 'href' => '#home'],
                ],
            ],
            'services' => [
                'items' => [
                    ['id' => 'adhd', 'title' => 'ADHD', 'detail' => 'Detail', 'icon' => 'star'],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->put(route('websites.files.json.update', ['website' => $website, 'path' => 'content.json']), [
                'sections' => [
                    [
                        'key' => 'nav',
                        'fields' => [],
                        'arrays' => [
                            [
                                'key' => 'links',
                                'items' => [
                                    [
                                        'fields' => [
                                            ['key' => 'label', 'value' => 'Home'],
                                            ['key' => 'href', 'value' => '#home'],
                                        ],
                                        'hidden' => [],
                                    ],
                                    [
                                        'fields' => [
                                            ['key' => 'label', 'value' => 'Contact'],
                                            ['key' => 'href', 'value' => '#contact'],
                                        ],
                                        'hidden' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'services',
                        'fields' => [],
                        'arrays' => [
                            [
                                'key' => 'items',
                                'items' => [
                                    [
                                        'fields' => [
                                            ['key' => 'title', 'value' => 'ADHD Updated'],
                                            ['key' => 'detail', 'value' => 'Detail'],
                                            ['key' => 'icon', 'value' => 'star'],
                                        ],
                                        'hidden' => [
                                            ['key' => 'id', 'value' => 'adhd'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('websites.files.json', ['website' => $website, 'path' => 'content.json']));

        $saved = json_decode(Storage::disk('local')->get('sites/example-site/content.json'), true);

        $this->assertCount(2, $saved['nav']['links']);
        $this->assertSame('Contact', $saved['nav']['links'][1]['label']);
        $this->assertSame('adhd', $saved['services']['items'][0]['id']);
        $this->assertSame('ADHD Updated', $saved['services']['items'][0]['title']);
    }

    public function test_json_editor_exposes_nested_image_src_in_array_items(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'hero' => [
                'slides' => [
                    [
                        'title' => 'Slide 1',
                        'image' => [
                            'src' => 'assets/hero.jpg',
                            'alt' => 'Hero',
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->get(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Websites/FileContent/JsonEditor')
                ->where('sections.0.key', 'hero')
                ->where('sections.0.arrays.0.key', 'slides')
                ->where('sections.0.arrays.0.items.0.fields.1.key', 'image.src')
                ->where('sections.0.arrays.0.items.0.fields.1.value', 'assets/hero.jpg')
                ->where('sections.0.arrays.0.items.0.fields.2.key', 'image.alt')
                ->where('sections.0.arrays.0.items.0.fields.2.value', 'Hero')
            );
    }

    public function test_authenticated_users_can_save_nested_image_src_in_array_items(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'hero' => [
                'slides' => [
                    [
                        'title' => 'Slide 1',
                        'image' => [
                            'src' => 'assets/hero.jpg',
                            'alt' => 'Hero',
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->put(route('websites.files.json.update', ['website' => $website, 'path' => 'content.json']), [
                'sections' => [
                    [
                        'key' => 'hero',
                        'fields' => [],
                        'arrays' => [
                            [
                                'key' => 'slides',
                                'items' => [
                                    [
                                        'fields' => [
                                            ['key' => 'title', 'value' => 'Slide 1'],
                                            ['key' => 'image.src', 'value' => 'assets/hero-updated.jpg'],
                                            ['key' => 'image.alt', 'value' => 'Hero updated'],
                                        ],
                                        'hidden' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('websites.files.json', ['website' => $website, 'path' => 'content.json']));

        $saved = json_decode(Storage::disk('local')->get('sites/example-site/content.json'), true);

        $this->assertSame('assets/hero-updated.jpg', $saved['hero']['slides'][0]['image']['src']);
        $this->assertSame('Hero updated', $saved['hero']['slides'][0]['image']['alt']);
        $this->assertSame('Slide 1', $saved['hero']['slides'][0]['title']);
    }

    public function test_json_save_returns_readable_validation_errors(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'nav' => [
                'links' => [
                    ['label' => 'Home', 'href' => '#home'],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $response = $this->actingAs($manager)
            ->from(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->put(route('websites.files.json.update', ['website' => $website, 'path' => 'content.json']), [
                'sections' => [
                    [
                        'key' => 'nav',
                        'fields' => [],
                        'arrays' => [
                            [
                                'key' => 'links',
                                'items' => [
                                    [
                                        'fields' => [
                                            ['key' => 'label', 'value' => 123],
                                            ['key' => 'href', 'value' => '#home'],
                                        ],
                                        'hidden' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->assertSessionHasErrors('sections.0.arrays.0.items.0.fields.0.value');

        $message = session('errors')->first('sections.0.arrays.0.items.0.fields.0.value');

        $this->assertStringNotContainsString('sections.0.arrays.0.items.0.fields.0.value', $message);
        $this->assertStringContainsString('Nav', $message);
        $this->assertStringContainsString('Links', $message);
        $this->assertStringContainsString('Label', $message);
        $this->assertStringContainsString('must be text', $message);
    }

    public function test_json_save_rejects_invalid_field_paths(): void
    {
        $manager = $this->createWebsiteManager(['business_id' => null]);
        $website = Website::factory()->create([
            'template_path' => 'sites/example-site',
        ]);

        Storage::disk('local')->put('sites/example-site/content.json', json_encode([
            'site' => [
                'title' => 'Old Title',
            ],
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($manager)
            ->from(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->put(route('websites.files.json.update', ['website' => $website, 'path' => 'content.json']), [
                'sections' => [
                    [
                        'key' => 'site',
                        'fields' => [
                            ['path' => '../etc/passwd', 'value' => 'Hacked'],
                        ],
                        'arrays' => [],
                    ],
                ],
            ])
            ->assertRedirect(route('websites.files.json', ['website' => $website, 'path' => 'content.json']))
            ->assertSessionHasErrors('sections.0.fields.0.path');

        $pathMessage = session('errors')->first('sections.0.fields.0.path');

        $this->assertStringNotContainsString('sections.0.fields.0.path', $pathMessage);
        $this->assertStringContainsString('Site', $pathMessage);
        $this->assertStringContainsString('not allowed', $pathMessage);

        $saved = json_decode(Storage::disk('local')->get('sites/example-site/content.json'), true);

        $this->assertSame('Old Title', $saved['site']['title']);
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
                        'arrays' => [],
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
