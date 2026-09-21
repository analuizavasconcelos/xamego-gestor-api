<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StockEntryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DeliveryZoneController;
use App\Http\Controllers\Api\CourierSettlementController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::put('me/password', [AuthController::class, 'updatePassword']);

    // Produtos e Estoque
    Route::apiResource('products', ProductController::class);
    Route::get('products/{product}/stock-entries', [StockEntryController::class, 'index']);
    Route::post('stock-entries', [StockEntryController::class, 'store']);

    // Pedidos
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'update', 'destroy']);

    // Bairros / Zonas de Entrega
    Route::get('delivery-zones', [DeliveryZoneController::class, 'index']);
    Route::post('delivery-zones', [DeliveryZoneController::class, 'store']);

    // Acerto diário do Motoboy
    Route::get('courier/settlement', [CourierSettlementController::class, 'dailySummary']);
    Route::post('courier/settle', [CourierSettlementController::class, 'settle']);

    // Relatórios
    Route::get('reports/profit', [ReportController::class, 'profit']);
    Route::get('reports/analytics', [ReportController::class, 'analytics']);
});