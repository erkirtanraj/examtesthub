<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::create([
            'name' => 'Website Content Admin',
            'email' => 'website-content-admin@example.test',
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_updating_legal_content_preserves_its_public_slug(): void
    {
        $admin = $this->superAdmin();
        $legal = WebsiteContent::create([
            'type' => 'legal',
            'key' => 'privacy',
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => ['en' => 'Initial policy'],
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)->put('/admin/settings/website-content/'.$legal->id, [
            'type' => 'legal',
            'key' => 'privacy',
            'title' => 'Updated Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => ['en' => 'Updated policy'],
            'is_published' => true,
            'sort_order' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('website_contents', [
            'id' => $legal->id,
            'type' => 'legal',
            'slug' => 'privacy-policy',
            'title' => 'Updated Privacy Policy',
        ]);

        $this->get('/pages/privacy-policy')->assertOk();
    }

    public function test_updating_a_legal_slug_uses_the_submitted_slug(): void
    {
        $admin = $this->superAdmin();
        $legal = WebsiteContent::create([
            'type' => 'legal',
            'key' => 'terms',
            'title' => 'Terms',
            'slug' => 'terms',
            'content' => ['en' => 'Terms'],
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)->put('/admin/settings/website-content/'.$legal->id, [
            'type' => 'legal',
            'key' => 'terms',
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'content' => ['en' => 'Updated terms'],
            'is_published' => true,
            'sort_order' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('website_contents', [
            'id' => $legal->id,
            'slug' => 'terms-of-service',
        ]);
        $this->get('/pages/terms-of-service')->assertOk();
    }

    public function test_sitemap_contains_public_catalogues_and_only_published_cms_pages(): void
    {
        WebsiteContent::create([
            'type' => 'page',
            'key' => 'about',
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => ['en' => 'About ExamTestHub'],
            'is_published' => true,
            'sort_order' => 0,
        ]);
        WebsiteContent::create([
            'type' => 'legal',
            'key' => 'privacy',
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => ['en' => 'Privacy details'],
            'is_published' => true,
            'sort_order' => 1,
        ]);
        WebsiteContent::create([
            'type' => 'page',
            'key' => 'draft',
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'content' => ['en' => 'Not public'],
            'is_published' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $response->assertSee('/courses', false)
            ->assertSee('/study-materials', false)
            ->assertSee('/test-series', false)
            ->assertSee('/pages/about-us', false)
            ->assertSee('/pages/privacy-policy', false)
            ->assertDontSee('/pages/draft-page', false);
    }
}
