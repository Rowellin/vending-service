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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong 2'
    ]);
});

Route::middleware(['auth.basic.api'])->group(function () {
    Route::post('/login', 'Api\VendingController@login');
    Route::post('/invoice', 'Api\VendingController@invoice');
    Route::get('/products', 'Api\VendingController@products');
    Route::get('/vending-check', 'Api\VendingController@vendingCheck');
});

Route::post('/callback', 'Api\VendingController@callback');
