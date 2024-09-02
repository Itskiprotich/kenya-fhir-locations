<?php

use App\Http\Controllers\LocationController;
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

Route::get('/fhir', [LocationController::class, 'fhir_locations'])->name('fhir_locations');
Route::get('/wards', [LocationController::class, 'index'])->name('wards');
Route::get('/ward_names', [LocationController::class, 'ward_names'])->name('ward_names');