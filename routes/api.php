<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Routes d'authentification publiques
Route::group([
    'prefix' => 'auth',
    'middleware' => ['api']
], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('google', [AuthController::class, 'googleLogin']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Routes protégées (nécessitent authentification)
Route::group([
    'middleware' => ['auth:sanctum']
], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    Route::get('auth/profile', [AuthController::class, 'profile']);
});

// Route de test
Route::get('/test', function () {
    return response()->json([
        'message' => 'MediExpress API is running!',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
