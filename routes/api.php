<?php

use App\Http\Controllers\Api\V1\AttributeController as V1AttributeController;
use App\Http\Controllers\Api\V1\CategoryController as V1CategoryController;
use App\Http\Controllers\Api\V1\ContactController as V1ContactController;
use App\Http\Controllers\Api\V1\DashboardController as V1DashboardController;
use App\Http\Controllers\Api\V1\DeviceController as V1DeviceController;
use App\Http\Controllers\Api\V1\EpcLogController as V1EpcLogController;
use App\Http\Controllers\Api\V1\InvoiceController as V1InvoiceController;
use App\Http\Controllers\Api\V1\ItemController as V1ItemController;
use App\Http\Controllers\Api\V1\UserController as V1UserController;
use App\Http\Controllers\Api\V1\WarehouseController as V1WarehouseController;
use App\Http\Controllers\Api\V1\LocationController as V1LocationController;
use App\Http\Controllers\Api\V1\MutationController as V1MutationController;
use App\Http\Controllers\Api\V1\OrderController as V1OrderController;
use App\Http\Controllers\Api\V1\ProductController as V1ProductController;
use App\Http\Controllers\Api\V1\RelocationController as V1RelocationController;
use App\Http\Controllers\Api\V1\ServiceController as V1ServiceController;
use App\Http\Controllers\Api\V1\TransferController as V1TransferController;
use App\Http\Controllers\Api\V1\UserLogController as V1UserLogController;
use App\Http\Controllers\MailController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('kirim-email', [MailController::class, 'index']);

Route::prefix('v1')->group(function () {
    Route::post('/register', [V1UserController::class, 'register']);
    Route::post('/login', [V1UserController::class, 'login']);

    Route::resource('/services', V1ServiceController::class);
    Route::resource('/devices', V1DeviceController::class);
    Route::get('/users', [V1UserController::class, 'getAll']);

    Route::post('/orders', [V1OrderController::class, 'store']);
    Route::get('/orders', [V1OrderController::class, 'index']);
    Route::get('/orders/token/{token}', [V1OrderController::class, 'getByToken']);
    Route::put('/orders/token/{token}/upload', [V1OrderController::class, 'uploadPaymentProof']);
    Route::post('/orders/{id}/approve', [V1OrderController::class, 'approve']);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'data user',
            'data' => $request->user()
        ]);
    });
    Route::get('/invoices', [V1InvoiceController::class, 'index']);

    Route::get('/dashboards', [V1DashboardController::class, 'index']);
    Route::post('/users/upload-image', [V1UserController::class, 'uploadImageUser']);
    Route::post('/companies/upload-image', [V1UserController::class, 'uploadImageCompany']);

    Route::post('/warehouses', [V1WarehouseController::class, 'store']);
    Route::get('/warehouses', [V1WarehouseController::class, 'index']);
    Route::get('/warehouses/{id}', [V1WarehouseController::class, 'show']);
    Route::get('/warehouses/{id}/stock', [V1WarehouseController::class, 'inStock']);

    Route::post('/locations', [V1LocationController::class, 'store']);
    Route::get('/locations', [V1LocationController::class, 'index']);
    Route::get('/locations/warehouse/{id}', [V1LocationController::class, 'getByWarehouseId']);

    Route::post('/contacts', [V1ContactController::class, 'store']);
    Route::get('/contacts', [V1ContactController::class, 'index']);
    Route::put('/contacts/{id}', [V1ContactController::class, 'update']);

    Route::post('/categories', [V1CategoryController::class, 'store']);
    Route::get('/categories', [V1CategoryController::class, 'index']);
    Route::put('/categories/{id}', [V1CategoryController::class, 'update']);

    Route::post('/attributes', [V1AttributeController::class, 'store']);
    Route::get('/attributes', [V1AttributeController::class, 'index']);
    Route::put('/attributes/{id}', [V1AttributeController::class, 'update']);

    Route::post('/products', [V1ProductController::class, 'store']);
    Route::get('/products', [V1ProductController::class, 'index']);
    Route::get('/products/{id}', [V1ProductController::class, 'show']);
    Route::put('/products/{id}', [V1ProductController::class, 'update']);
    Route::get('/products/{id}/stock', [V1ProductController::class, 'inStock']);

    Route::post('/items', [V1ItemController::class, 'store']);
    Route::get('/items', [V1ItemController::class, 'index']);
    Route::get('/items/epc/{epc}', [V1ItemController::class, 'getByEpc']);
    Route::get('/items/stock/{wid}', [V1ItemController::class, 'inStock']);
    Route::post('/items/stock/{wid}', [V1ItemController::class, 'inStockByPath']);

    Route::post('/mutations', [V1MutationController::class, 'store']);
    Route::get('/mutations', [V1MutationController::class, 'index']);
    Route::get('/mutations/pdf', [V1MutationController::class, 'printPdf']);
    Route::get('/mutations/excel', [V1MutationController::class, 'printExcel']);
    Route::get('/mutations/{id}', [V1MutationController::class, 'show']);

    Route::post('/relocations', [V1RelocationController::class, 'store']);

    Route::get('/transfers', [V1TransferController::class, 'index']);
    Route::get('/transfers/pdf', [V1TransferController::class, 'printPdf']);
    Route::get('/transfers/excel', [V1TransferController::class, 'printExcel']);
    Route::get('/transfers/{id}', [V1TransferController::class, 'show']);
    Route::put('/transfers/{id}', [V1TransferController::class, 'update']);
    Route::post('/transfers', [V1TransferController::class, 'store']);
    Route::get('/transfers/wait/{wid}', [V1TransferController::class, 'waitingTransfer']);
    Route::get('/transfers/pending/{wid}', [V1TransferController::class, 'pendingTransfer']);
    Route::get('/transfers/transfer/{id}', [V1TransferController::class, 'getById']);

    Route::get('/epc-logs', [V1EpcLogController::class, 'index']);
    Route::get('/epc-logs/{epc}', [V1EpcLogController::class, 'getByEpc']);

    Route::get('/user-logs', [V1UserLogController::class, 'index']);
    Route::post('/user-logs', [V1UserLogController::class, 'store']);
});


Route::get('/', function () {
    return response()->json([
        'success' => false,
        'message' => "please login"
    ], 400);
})->name('login');
