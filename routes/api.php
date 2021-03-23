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

Route::middleware('auth:sanctum')->namespace('api\v1')->prefix('v1')->group(function () {
    Route::resource('coa', 'CoaController')->except('create', 'edit');
    Route::resource('coa-detail', 'CoaDetailController')->except('create', 'edit');
    Route::resource('item', 'ItemController')->except('create', 'edit');

    Route::get('/me', function(Request $request) {
        return auth()->user();
    });

    Route::post('/auth/logout', 'AuthController@logout');
});
