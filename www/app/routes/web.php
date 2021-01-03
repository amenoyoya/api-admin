<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // return view('welcome');
    return redirect('/admin');
});


Route::group(['prefix' => 'admin'], function () {
    Route::post('{slug}/import', 'App\\Http\\Controllers\\VoyagerController@import');
    Route::post('{slug}/export', 'App\\Http\\Controllers\\VoyagerController@export');
    Voyager::routes();
});

/**
 * Voyager API
 * @note Sessionを利用してトークン認証を行うため api ではなく web チャンネルでルーティング
 */
Route::prefix('voyager/api')->middleware('voyager_api')->group(function() {
    Route::any('/exec', 'App\\Http\\Controllers\\Api\\VoyagerController@exec');
});
