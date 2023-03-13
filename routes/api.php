<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterationController;
use App\Http\Controllers\Device\DeviceOrderController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Transaction\TransactionController;




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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//Registration
Route::post('verify-phone', [RegisterationController::class, 'phone_verification']);
Route::post('verify-email', [RegisterationController::class, 'email_verification']);
Route::post('resend-otp', [RegisterationController::class, 'resend_otp']);
Route::post('resend-email-otp', [RegisterationController::class, 'resend_email_otp']);
Route::post('verify-otp', [RegisterationController::class, 'verify_otp']);
Route::post('register', [RegisterationController::class, 'register']);


//Device Order
Route::post('order-device', [DeviceOrderController::class, 'order_device']);
Route::get('bank-details', [DeviceOrderController::class, 'bank_details']);
Route::get('all-pickup-location', [DeviceOrderController::class, 'all_pick_up_location']);
Route::post('state-pickup', [DeviceOrderController::class, 'state_pick_up_location']);
Route::post('lga-pickup', [DeviceOrderController::class, 'lga_pick_up_location']);



//webhooks
Route::post('v1/cash-out-webhook', [TransactionController::class, 'cash_out_webhook']);
Route::post('v1/wallet-check', [TransactionController::class, 'balance_webhook']);
Route::post('v1/transfer-request', [TransactionController::class, 'transfer_request']);


//Transactions
Route::get('transaction-status', [TransactionController::class, 'transactiion_status']);







//Login
Route::post('phone-login', [LoginController::class, 'phone_login']);
Route::post('email-login', [LoginController::class, 'email_login']);


Route::group(['middleware' => ['auth:api','acess']], function(){

    Route::get('user-info', [LoginController::class, 'user_info']);




    //Trasnaction
    Route::post('cash-out', [TransactionController::class, 'cash_out']);
    Route::get('get-banks', [TransactionController::class, 'get_banks']);








});











