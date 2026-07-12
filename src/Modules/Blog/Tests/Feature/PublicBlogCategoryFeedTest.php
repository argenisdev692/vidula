<?php

declare(strict_types=1);

namespace Modules\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
use Modules\Post\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;
use Tests\TestCase;

final class PublicBlogCategoryFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_requires_no_authentication(): void
    {
        BlogCategoryEloquentModel::factory()->create();

        $this->getJson('/api/blog-categories/public')->assertOk();
    }

    public function test_public_feed_does_not_expose_internal_ids(): void
    {
        BlogCategoryEloquentModel::factory()->create();

        $response = $this->getJson('/api/blog-categories/public')->assertOk();

        $response->assertJsonMissingPath('data.0.id');
        $response->assertJsonMissingPath('data.0.user_id');
    }

    public function test_public_feed_counts_only_published_posts(): void
    {
        $category = BlogCategoryEloquentModel::factory()->create(['blog_category_name' => 'Engineering']);
        PostEloquentModel::factory()->published()->create(['category_id' => $category->id]);
        PostEloquentModel::factory()->create(['category_id' => $category->id]); // draft

        $response = $this->getJson('/api/blog-categories/public')->assertOk();

        $data = $response->json('data');
        $row = collect($data)->firstWhere('name', 'Engineering');
        $this->assertSame(1, $row['posts_count']);
    }

    public function test_suspended_categories_are_excluded(): void
    {
        BlogCategoryEloquentModel::factory()->create(['blog_category_name' => 'Deleted Soon'])->delete();

        $this->getJson('/api/blog-categories/public')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Deleted Soon']);
    }
}
