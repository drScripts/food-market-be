<?php

use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

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


Route::prefix('users')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    Route::middleware('jwt')->group(function () {
        Route::get("/profile", [UserController::class, 'profile']);
        Route::patch('/profile', [UserController::class, 'update']);
    });
});

Route::middleware('jwt')->prefix("products")->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get("/{id}", [ProductController::class, 'show']);

    Route::middleware('jwt.admin')->group(function () {
        Route::post('/', [ProductController::class, 'create']);
        Route::patch('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'delete']);
    });
});

Route::middleware('jwt')->prefix('transactions')->group(function () {
    Route::post("/", [TransactionController::class, 'create']);
    Route::get("/", [TransactionController::class, 'index']);
    Route::patch('/{id}', [TransactionController::class, 'update']);
});

Route::post('/transactions/notif', [TransactionController::class, 'notif']);
