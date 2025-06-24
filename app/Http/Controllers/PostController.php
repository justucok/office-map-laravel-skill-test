<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all posts that are not drafts and have a published date
        $posts = Post::with('user')->where('is_draft', false)->whereNotNull('published_at')->latest()->paginate(20);

        return response()->json(['data' => $posts]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        // Validate the request using StorePostRequest
        $validated = $request->validated();

        $post = Post::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_draft' => $validated['is_draft'] ?? true,
            'published_at' => $validated['published_at'] ?? null,
        ]);

        return to_route('posts.show', compact('post'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        // Check if the post is a draft or not published
        if ($post->is_draft || ($post->published_at = null)) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $post->load('user');

        return response()->json(['data' => $post]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        // Check if the user is authorized to update the post
        $this->authorize('update', $post);

        // Validate the request using UpdatePostRequest
        $validated = $request->validated();

        $post->update($validated);

        return to_route('posts.show', compact('post'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Check if the user is authorized to delete the post
        $this->authorize('delete', $post);

        $post->delete();

        return to_route('posts.index');
    }
}
