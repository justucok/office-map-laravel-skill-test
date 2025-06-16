<?php

use App\Http\Controllers\Api\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::middleware('auth')->group(function () {
//     Route::resource('posts', PostController::class)->except(['store', 'update', 'destroy']);
// });

Route::resource('posts', PostController::class)->except(['store', 'update', 'destroy']);

Route::middleware('auth')->group(function () {
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
});
