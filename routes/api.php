<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterationController;
use App\Http\Controllers\Device\DeviceOrderController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\VAS\AirtimeController;
use App\Http\Controllers\VAS\DataController;
use App\Http\Controllers\VAS\PowerController;







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
Route::post('resend-phone-otp', [RegisterationController::class, 'resend_phone_otp']);
Route::post('resend-email-otp', [RegisterationController::class, 'resend_email_otp']);
Route::post('verify-phone-otp', [RegisterationController::class, 'verify_phone_otp']);
Route::post('verify-email-otp', [RegisterationController::class, 'verify_email_otp']);

Route::post('register', [RegisterationController::class, 'register']);


//Device Order
Route::post('order-device', [DeviceOrderController::class, 'order_device']);
Route::get('bank-details', [DeviceOrderController::class, 'bank_details']);
Route::get('all-pickup-location', [DeviceOrderController::class, 'all_pick_up_location']);
Route::post('state-pickup', [DeviceOrderController::class, 'state_pick_up_location']);
Route::post('lga-pickup', [DeviceOrderController::class, 'lga_pick_up_location']);



//webhooks
Route::post('v1/cash-out-webhook', [TransactionController::class, 'cash_out_webhook']);
Route::post('v1/cash-in', [TransactionController::class, 'cash_in_webhook']);
Route::post('v1/wallet-check', [TransactionController::class, 'balance_webhook']);
Route::post('v1/transfer-request', [TransactionController::class, 'transfer_request']);


//Transactions
Route::get('transaction-status', [TransactionController::class, 'transactiion_status']);


//Get Pool Banalce
Route::get('pool-balance', [TransactionController::class, 'pool_account']);



//Get Data Plans
Route::get('get-data-plan', [DataController::class, 'get_data']);

//Get State
Route::get('get-states', [RegisterationController::class, 'get_states']);


//Get Lga
Route::post('get-lga', [RegisterationController::class, 'get_lga']);

//Get Eletric Compnay
Route::get('electric-company', [PowerController::class, 'get_eletric_company']);

//Charges
Route::get('transfer-charges', [TransactionController::class, 'transfer_charges']);

//Get Token
Route::get('get-token', [TransactionController::class, 'get_token']);









//Login
Route::post('phone-login', [LoginController::class, 'phone_login']);
Route::post('email-login', [LoginController::class, 'email_login']);


Route::group(['middleware' => ['auth:api','acess']], function(){


    //Profile
    Route::get('user-info', [ProfileController::class, 'user_info']);
    Route::post('update-kyc', [ProfileController::class, 'update_user']);
    Route::post('verify-info', [ProfileController::class, 'verify_info']);
    Route::post('update-account-info', [ProfileController::class, 'update_account_info']);







    //Trasnaction
    Route::post('cash-out', [TransactionController::class, 'cash_out']);
    Route::post('resolve-bank', [TransactionController::class, 'resolve_bank']);
    Route::post('resolve-enkpay-account', [TransactionController::class, 'resolve_enkpay_account']);



    Route::get('get-banks', [TransactionController::class, 'get_banks']);



    //Airtime
    Route::post('buy-airtime', [AirtimeController::class, 'buy_airtime']);


    //Buy Data Bundle
    Route::post('buy-data', [DataController::class, 'buy_data']);


    //Power
    Route::post('verify-account', [PowerController::class, 'verify_account']);


    //Get all Transactions
    Route::get('all-transaction', [TransactionController::class, 'get_all_transactions']);


    //Bank Transfer
    Route::post('bank-transfer', [TransactionController::class, 'bank_transfer']);













});











