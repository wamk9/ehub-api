<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'auth'], function () {

});

// Serve Vue SPA for all non-API routes (Vue Router history mode)
Route::get('/{any}', function () {
    return response()->file(public_path('index.html'));
})->where('any', '^(?!api|storage|_debugbar).*');
