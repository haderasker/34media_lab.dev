<?php

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

Route::group(['prefix' => 'v1', 'namespace'=>'Api\V1'], function() {
	Route::group(['prefix' => 'user'], function() {
		Route::resource('/', 'UserController');

		Route::post('/login', 'UserController@login');
		Route::post('/active-account', 'UserController@activeAccount');
		Route::post('/update-profile', 'UserController@updateProfile');
		Route::post('/update-phone', 'UserController@updatePhone');
		Route::post('/update-password', 'UserController@updatePassword');

		Route::post('/send-activation-code', 'UserController@sendActivationCode');
		Route::put('/reset-password', 'UserController@resetPassword');
	});

	Route::group(['prefix' => 'restaurant'], function() {
		Route::get('/home', 'ResturantController@index');
		Route::get('/nearest-restaurant', 'ResturantController@nearestResturant');
		Route::get('/popular-restaurant', 'ResturantController@pupularResturant');
	});
});




