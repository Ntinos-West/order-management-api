<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VatController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderItemController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('customers', CustomerController::class);
Route::apiResource('payments', PaymentController::class);
Route::apiResource('orders', OrderController::class);
Route::apiResource('vats', VatController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('order-items', OrderItemController::class);