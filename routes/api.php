<?php

use Illuminate\Http\Request;

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

Route::group(['middleware' => 'cors', 'prefix' => '/v1'], function () {

    Route::post('/car', 'ParkController@storeCar');

    Route::post('/exit', 'ParkController@carExit');

    Route::get('/car', 'ParkController@getCar');

    Route::post('/park', 'ParkController@setParkingLotSize');

    Route::get('/park', 'ParkController@getParkSize');

    Route::post('/car/type', 'ParkController@getCarByType');

    Route::post('/car/color', 'ParkController@getCarByColor');

});


