<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CloudinaryController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
    Route::post('refresh',  [AuthController::class, 'refresh']);
});

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::get('me',      [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Cloudinary: delete foto pelo public_id
    Route::post('photos/delete', [CloudinaryController::class, 'destroy']);

    // CRUD de grupos
    Route::get('groups',          [GroupController::class, 'index']);
    Route::post('groups',         [GroupController::class, 'store']);
    Route::get('groups/{group}',  [GroupController::class, 'show']);
    Route::put('groups/{group}',  [GroupController::class, 'update']);
    Route::patch('groups/{group}',[GroupController::class, 'update']);
    Route::delete('groups/{group}', [GroupController::class, 'destroy']);
});
