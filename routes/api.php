<?php

use App\Http\Controllers\Admin\HandleAfterPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaliCalonSiswa\PendaftaranSiswaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// After payment 
Route::post('/after-payment-pendaftaran', [PendaftaranSiswaController::class, 'updateStatus']);

// Handle payment
Route::post('handle-payment', [HandleAfterPaymentController::class, 'handleTransaction']);
