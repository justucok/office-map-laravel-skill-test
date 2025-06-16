<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class PostController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return response()->json(Post::all());

        $posts = Post::with('user')
            ->where('is_draft', 0)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->latest()
            ->paginate(20);

        return response()->json($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        $post = Post::create([
            'user_id' => Auth::id(), // mengambil id user yang sedang login
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_draft' => $validated['is_draft'] ?? true,
            'published_at' => $validated['published_at'] ?? null,
        ]);

        return response()->json(
            [
                'message' => 'Post created successfully.',
                'data' => $post,
            ],
            201,
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $post = Post::with('user')
            ->where('id', $id)
            ->where('is_draft', false)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post); // 🔒 hanya author

        $validated = $request->validated();

        $post->update($validated);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->load('user'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }

    public function create()
    {
        return response()->json([
            'message' => 'posts.create',
        ]);
    }

    public function edit($id): JsonResponse
    {
        return response()->json([
            'message' => 'posts.edit',
        ]);
    }
}
