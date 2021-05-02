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
    Route::resource('/coa', 'CoaController')->except('create', 'edit');
    Route::resource('/coa-detail', 'CoaDetailController')->except('create', 'edit');

    Route::get('/user', 'UserController@index');
    Route::put('/user-make-admin/{user}', 'UserController@makeAdmin');
    Route::put('/user-remove-admin/{user}', 'UserController@removeAdmin');
    Route::put('/user-ban-user/{user}', 'UserController@banUser');
    Route::put('/user-unban-user/{user}', 'UserController@unbanUser');

    Route::get('/user-count', 'DashboardController@userCount');
    Route::get('/admin-count', 'DashboardController@adminCount');
    Route::get('/banned-count', 'DashboardController@bannedCount');
});

Route::middleware('auth:sanctum')->namespace('api\v1')->prefix('v1')->group(function () {
    Route::resource('/coa', 'CoaController')->except('create', 'store', 'show', 'edit', 'update', 'destroy');
    Route::resource('/item', 'ItemController')->except('create', 'edit');
    Route::resource('/purchase', 'PurchaseController')->except('create', 'edit');
    Route::resource('/sale', 'SaleController')->except('create', 'edit');
    Route::resource('/cash-payment', 'CashPaymentController')->except('create', 'edit');
    Route::resource('/cash-receipt', 'CashReceiptController')->except('create', 'edit');
    Route::resource('/general-entry', 'GeneralEntryController')->except('create', 'edit');

    Route::get('/income-statement', 'IncomeStatementController@index');
    Route::post('/income-statement', 'IncomeStatementController@filterDate');
    Route::post('/income-statement/print', 'IncomeStatementController@printReport');

    Route::get('/purchase-count', 'DashboardController@purchaseCount');
    Route::get('/sale-count', 'DashboardController@saleCount');
    Route::get('/cash-payment-count', 'DashboardController@cashPaymentCount');
    Route::get('/cash-receipt-count', 'DashboardController@cashReceiptCount');

    Route::get('/me', 'AuthController@profile');

    Route::post('/auth/logout', 'AuthController@logout');
});
