<?php

namespace Tests\Feature;

use App\Models\Post;
use Database\Factories\PostFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    protected function authenticate()
    {
        $user = UserFactory::new()->create();

        return $this->actingAs($user);
    }

    protected function createPosts()
    {
        $posts = Post::factory(50)->create();

        return $posts;
    }

    public function test_can_list_active_posts()
    {
        $this->authenticate();
        Post::factory(100)->create();

        $response = $this->withHeader('Accept', 'application/json')->getJson(route('posts.index'));

        $response
            ->assertStatus(200)
            ->assertJsonCount(20, '*.data.*')
            ->assertJsonStructure([
                '*' => [
                    'current_page',
                    'data' => [
                        '*' => ['user_id', 'user'],
                    ],
                    'last_page',
                ],
            ]);
    }

    public function test_cannot_list_draft_posts()
    {
        $this->authenticate();
        Post::factory(20)->create(['is_draft' => true]);

        $response = $this->withHeader('Accept', 'application/json')->getJson(route('posts.index'));

        $response->assertStatus(200)->assertJsonCount(0, '*.data.*');
    }

    public function test_authenticated_user_can_create_post()
    {
        $this->authenticate();

        $postData = PostFactory::new()->make()->toArray();

        $response = $this->withHeader('Accept', 'application/json')->postJson(route('posts.store'), $postData);

        $response->assertStatus(302)->assertRedirect(route('posts.show', ['post' => 1]));

        $this->assertDatabaseHas('posts', [
            'title' => $postData['title'],
            'content' => $postData['content'],
            'is_draft' => false,
        ]);
    }

    public function test_non_authenticated_user_cannot_create_post()
    {
        $postData = PostFactory::new()->make()->toArray();

        $response = $this->withHeader('Accept', 'application/json')->postJson(route('posts.store'), $postData);

        $response->assertStatus(401)->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_can_show_post()
    {
        $this->authenticate();
        $post = Post::factory()->create(['is_draft' => false]);

        $response = $this->withHeader('Accept', 'application/json')->getJson(route('posts.show', ['post' => $post->id]));

        $response->assertStatus(200)->assertJsonStructure([
            'data' => ['id', 'title', 'content', 'user_id', 'user'],
        ]);
    }

    public function test_cannot_show_draft_post()
    {
        $this->authenticate();
        $post = Post::factory()->create(['is_draft' => true]);

        $response = $this->withHeader('Accept', 'application/json')->getJson(route('posts.show', ['post' => $post->id]));

        $response->assertStatus(404)->assertJson(['message' => 'Post not found.']);
    }

    public function test_only_author_can_update_post()
    {
        $user = UserFactory::new()->create();
        $this->actingAs($user);
        $post = Post::factory()->create(['user_id' => $user, 'is_draft' => false]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ];

        $response = $this->withHeader('Accept', 'application/json')->putJson(route('posts.update', ['post' => $post->id]), $updateData);

        $response->assertStatus(302)->assertRedirect(route('posts.show', ['post' => $post->id]));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => $updateData['title'],
            'content' => $updateData['content'],
        ]);
    }

    public function test_non_author_cannot_update_post()
    {
        $this->authenticate();
        $post = Post::factory()->create(['is_draft' => false]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ];

        $response = $this->withHeader('Accept', 'application/json')->putJson(route('posts.update', ['post' => $post->id]), $updateData);

        $response->assertStatus(403)->assertJson(['message' => 'This action is unauthorized.']);
    }

    public function test_only_author_can_delete_post()
    {
        $user = UserFactory::new()->create();
        $this->actingAs($user);
        $post = Post::factory()->create(['user_id' => $user, 'is_draft' => false]);

        $response = $this->withHeader('Accept', 'application/json')->deleteJson(route('posts.destroy', ['post' => $post->id]));

        $response->assertStatus(302)->assertRedirect(route('posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_non_author_cannot_delete_post()
    {
        $this->authenticate();
        $post = Post::factory()->create(['is_draft' => false]);

        $response = $this->withHeader('Accept', 'application/json')->deleteJson(route('posts.destroy', ['post' => $post->id]));

        $response->assertStatus(403)->assertJson(['message' => 'This action is unauthorized.']);
    }
}
