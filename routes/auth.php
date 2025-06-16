<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');


// Route::middleware('auth')->post('/test-auth', function (Request $request) {
//     return response()->json([
//         'message' => 'Authenticated',
//         'user' => $request->user(),
//     ]);
// });
