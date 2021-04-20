<?php

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

Route::namespace('api\v1')->prefix('v1')->group(function () {
    Route::post('/auth/register', 'AuthController@register');
    Route::post('/auth/login', 'AuthController@login');
});

Route::middleware(['auth:sanctum', 'is.admin'])->namespace('api\v1')->prefix('v1')->group(function () {
    Route::resource('coa', 'CoaController')->except('create', 'edit');
    Route::resource('coa-detail', 'CoaDetailController')->except('create', 'edit');
});

Route::middleware('auth:sanctum')->namespace('api\v1')->prefix('v1')->group(function () {
    Route::resource('coa', 'CoaController')->except('create', 'store', 'show', 'edit', 'update', 'destroy');
    Route::resource('coa-detail', 'CoaDetailController')->except('create', 'store', 'show', 'edit', 'update', 'destroy');
    Route::resource('item', 'ItemController')->except('create', 'edit');
    Route::resource('purchase', 'PurchaseController')->except('create', 'edit');
    Route::resource('sale', 'SaleController')->except('create', 'edit');
    Route::resource('cash-payment', 'CashPaymentController')->except('create', 'edit');
    Route::resource('cash-receipt', 'CashReceiptController')->except('create', 'edit');

    Route::get('/me', function(Request $request) {
        return auth()->user();
    });

    Route::post('/auth/logout', 'AuthController@logout');
});
