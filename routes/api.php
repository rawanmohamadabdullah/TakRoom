<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::delete('/delete-account', [UserController::class, 'deleteAccount']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users', [AdminController::class, 'storeUser']);
        Route::patch('/admin/users/{id}/change-role', [AdminController::class, 'changeRole']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser']);
        Route::patch('/admin/users/{id}/ban', [AdminController::class, 'banUser']);
        Route::patch('/admin/users/{id}/unban', [AdminController::class, 'unbanUser']);
    });

    Route::middleware('role:customer')->group(function () {
        Route::get('/customer/orders', [OrderController::class, 'customerIndex']);
        Route::post('/orders/create', [OrderController::class, 'create']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    });

    Route::middleware('role:supplier')->group(function () {
        Route::get('/supplier/orders', [OrderController::class, 'supplierIndex']);
        Route::post('/orders/{id}/accept', [OrderController::class, 'accept']);
        Route::post('/orders/{id}/reject', [OrderController::class, 'reject']);

        Route::post('/supplier/categories', [ProductController::class, 'storeCategory']);
        Route::post('/supplier/products', [ProductController::class, 'store']);
        Route::put('/supplier/products/{id}', [ProductController::class, 'update']);
        Route::delete('/supplier/products/{id}', [ProductController::class, 'destroy']);
    });

    Route::get('/user/balance', [OrderController::class, 'showBalance']);

    Route::get('/categories', [ProductController::class, 'getAllCategories']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/categories/{id}/products', [ProductController::class, 'getByCategory']);

    Route::get('/user/balance', [OrderController::class, 'showBalance']);
    Route::post('/user/deposit', [UserController::class, 'deposit']);
});
