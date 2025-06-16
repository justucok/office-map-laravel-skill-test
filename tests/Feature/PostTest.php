<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        return $user;
    }

    /** @test */
    public function it_returns_paginated_active_posts()
    {
        $user = $this->authenticate();

        Post::factory()
            ->count(5)
            ->create(['is_draft' => false, 'published_at' => now()]);
        Post::factory()->create(['is_draft' => true]); // should not appear
        Post::factory()->create(['published_at' => now()->addDay()]); // scheduled, should not appear

        $response = $this->getJson('/api/posts');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'links'])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_returns_404_for_draft_or_scheduled_post()
    {
        $user = $this->authenticate();

        $draft = Post::factory()->create(['is_draft' => true]);
        $scheduled = Post::factory()->create(['is_draft' => false, 'published_at' => now()->addDay()]);

        $this->getJson("/api/posts/{$draft->id}")->assertStatus(404);
        $this->getJson("/api/posts/{$scheduled->id}")->assertStatus(404);
    }

    /** @test */
    public function authenticated_user_can_create_post()
    {
        $user = $this->authenticate();

        $payload = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/posts', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['title' => 'Test Title']);
    }

    /** @test */
    public function guest_cannot_create_post()
    {
        $payload = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/posts', $payload)->assertStatus(401);
    }

    /** @test */
    public function author_can_update_post()
    {
        $user = $this->authenticate();

        $post = Post::factory()->create(['user_id' => $user->id]);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ];

        $this->putJson("/api/posts/{$post->id}", $payload)
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    /** @test */
    public function non_author_cannot_update_post()
    {
        $this->authenticate();
        $post = Post::factory()->create();

        $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated title',
            'content' => 'Updated content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ])->assertStatus(403);
    }

    /** @test */
    public function author_can_delete_post()
    {
        $user = $this->authenticate();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/posts/{$post->id}")->assertStatus(200);
    }

    /** @test */
    public function non_author_cannot_delete_post()
    {
        $this->authenticate();
        $post = Post::factory()->create();

        $this->deleteJson("/api/posts/{$post->id}")->assertStatus(403);
    }
}
