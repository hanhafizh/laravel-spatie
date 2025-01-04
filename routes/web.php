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
    return view('welcome');
});

Route::get('drop', 'DropfileController@index');
Route::post('drop', 'DropfileController@store');
Route::get('drop/{filetitle}', 'DropfileController@show');
Route::get('drop/{filetitle}/download', 'DropfileController@download');
Route::get('drop/{id}/destroy', 'DropfileController@destroy');
