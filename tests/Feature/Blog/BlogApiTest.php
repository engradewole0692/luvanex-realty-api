<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Origin', 'http://localhost:8080');
        $this->withHeader('Referer', 'http://localhost:8080/admin/blog');
    }

    public function test_published_post_is_returned_publicly(): void
    {
        $post = BlogPost::factory()->published()->create([
            'title' => 'Published Article',
            'slug' => 'published-article',
        ]);

        $this->getJson('/api/v1/blog/posts')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'published-article']);

        $this->getJson('/api/v1/blog/posts/'.$post->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', 'published-article');
    }

    public function test_draft_is_not_returned_publicly(): void
    {
        BlogPost::factory()->create([
            'title' => 'Secret Draft',
            'slug' => 'secret-draft',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $this->getJson('/api/v1/blog/posts')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'secret-draft']);

        $this->getJson('/api/v1/blog/posts/secret-draft')
            ->assertNotFound();
    }

    public function test_invalid_slug_returns_404(): void
    {
        $this->getJson('/api/v1/blog/posts/does-not-exist')
            ->assertNotFound();
    }

    public function test_pagination_works(): void
    {
        BlogPost::factory()->published()->count(3)->create();

        $this->getJson('/api/v1/blog/posts?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_category_filtering_works(): void
    {
        $category = BlogCategory::factory()->create(['slug' => 'markets']);
        $other = BlogCategory::factory()->create(['slug' => 'finance']);

        BlogPost::factory()->published()->create([
            'category_id' => $category->id,
            'slug' => 'market-post',
        ]);
        BlogPost::factory()->published()->create([
            'category_id' => $other->id,
            'slug' => 'finance-post',
        ]);

        $this->getJson('/api/v1/blog/posts?category=markets')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'market-post'])
            ->assertJsonMissing(['slug' => 'finance-post']);
    }

    public function test_unauthenticated_admin_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/admin/blog/posts')->assertUnauthorized();
        $this->postJson('/api/v1/admin/blog/posts', [])->assertUnauthorized();
    }

    public function test_non_admin_is_rejected(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->getJson('/api/v1/admin/blog/posts')
            ->assertForbidden();
    }

    public function test_admin_can_create_draft_edit_publish_unpublish_and_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();

        $create = $this->actingAs($admin)->postJson('/api/v1/admin/blog/posts', [
            'title' => 'How Nigerian Investors Can Buy Property in America',
            'excerpt' => 'A practical overview.',
            'content' => '<p>Useful guidance for investors.</p>',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.slug', 'how-nigerian-investors-can-buy-property-in-america');

        $id = $create->json('data.id');

        $this->actingAs($admin)->putJson('/api/v1/admin/blog/posts/'.$id, [
            'excerpt' => 'Updated excerpt.',
        ])->assertOk()->assertJsonPath('data.excerpt', 'Updated excerpt.');

        $this->actingAs($admin)->postJson('/api/v1/admin/blog/posts/'.$id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->getJson('/api/v1/blog/posts/how-nigerian-investors-can-buy-property-in-america')
            ->assertOk();

        $this->actingAs($admin)->postJson('/api/v1/admin/blog/posts/'.$id.'/unpublish')
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->getJson('/api/v1/blog/posts/how-nigerian-investors-can-buy-property-in-america')
            ->assertNotFound();

        $this->actingAs($admin)->deleteJson('/api/v1/admin/blog/posts/'.$id)
            ->assertOk();

        $this->assertDatabaseMissing('blog_posts', ['id' => $id]);
    }

    public function test_validation_errors_work_on_create(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/blog/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_invalid_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/api/v1/admin/blog/media', [
                'image' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertStatus(422);
    }

    public function test_valid_image_upload_is_accepted(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/api/v1/admin/blog/media', [
                'image' => UploadedFile::fake()->image('cover.jpg'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonStructure(['path', 'url']);
    }
}
