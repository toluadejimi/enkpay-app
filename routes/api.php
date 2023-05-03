<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterationController;
use App\Http\Controllers\Device\DeviceOrderController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\VAS\AirtimeController;
use App\Http\Controllers\VAS\DataController;
use App\Http\Controllers\VAS\PowerController;
use App\Http\Controllers\VAS\EducationController;
use App\Http\Controllers\VAS\CableController;
use App\Http\Controllers\VAS\InsuranceController;
use App\Http\Controllers\Virtual\VirtualaccountController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::post('forgot-password', [RegisterationController::class, 'forgot_password']);


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

Route::post('order-device-complete', [DeviceOrderController::class, 'order_complete']);



//webhooks
Route::post('v1/cash-out-webhook', [TransactionController::class, 'cash_out_webhook']);
Route::post('v1/cash-in', [VirtualaccountController::class, 'cash_in_webhook']);
Route::post('v1/p-cash-in', [VirtualaccountController::class, 'providusCashIn']);
Route::post('v1/wallet-check', [TransactionController::class, 'balance_webhook']);
Route::post('v1/transfer-request', [TransactionController::class, 'transfer_request']);

Route::post('v1/merchant-details', [ProfileController::class, 'view_agent_account']);






//Transactions
Route::post('transaction-status', [TransactionController::class, 'transactiion_status']);

//Get Pool Banalce
Route::get('pool-balance', [TransactionController::class, 'pool_account']);

//Get Data Plans

//Get State
Route::get('get-states', [RegisterationController::class, 'get_states']);

//Get Lga
Route::post('get-lga', [RegisterationController::class, 'get_lga']);


//Charges
Route::get('transfer-charges', [TransactionController::class, 'transfer_charges']);

//Get Token
Route::get('get-token', [TransactionController::class, 'get_token']);

//Get All virtual acount
Route::get('all-virtual-account', [VirtualaccountController::class, 'get_created_account']);
Route::get('account-history', [VirtualaccountController::class, 'virtual_acct_history']);

//Login
Route::post('phone-login', [LoginController::class, 'phone_login']);
Route::post('email-login', [LoginController::class, 'email_login']);

//Contact
Route::get('contact', [ProfileController::class, 'contact']);


Route::group(['middleware' => ['auth:api', 'acess']], function () {


    //Fogot Pin
    Route::post('forgot-pin', [ProfileController::class, 'forgot_pin']);

    //Profile
    Route::get('user-info', [ProfileController::class, 'user_info']);
    Route::post('update-kyc', [ProfileController::class, 'update_user']);
    Route::post('verify-info', [ProfileController::class, 'verify_info']);
    Route::post('update-business', [ProfileController::class, 'update_business']);
    Route::post('update-account-info', [ProfileController::class, 'update_account_info']);
    Route::post('update-bank-info', [ProfileController::class, 'update_bank_info']);
    Route::post('verify-identity', [ProfileController::class, 'verify_identity']);
    Route::post('upload-identity', [ProfileController::class, 'upload_identity']);



   //Get Eletric Compnay
Route::get('electric-company', [PowerController::class, 'get_eletric_company']);



//Auth Verification
Route::post('auth-verify-phone', [RegisterationController::class, 'auth_phone_verification']);
Route::post('auth-verify-email', [RegisterationController::class, 'auth_email_verification']);






    Route::get('get-data-plan', [DataController::class, 'get_data']);

    //Trasnaction
    Route::post('cash-out', [TransactionController::class, 'cash_out']);
    Route::post('resolve-bank', [TransactionController::class, 'resolve_bank']);
    Route::post('resolve-enkpay-account', [TransactionController::class, 'resolve_enkpay_account']);
    Route::post('enkpay-transfer', [TransactionController::class, 'enkpay_transfer']);
    Route::get('get-wallet', [TransactionController::class, 'get_wallet']);
    Route::post('self-cash-out', [TransactionController::class, 'self_cash_out']);

    Route::get('get-terminals', [TransactionController::class, 'get_terminals']);
    Route::post('get-terminal-transaction', [TransactionController::class, 'get_terminal_transaction']);







    //Pin Verify
    Route::post('verify-pin', [TransactionController::class, 'verify_pin']);



// Transfer Properties
    Route::get('transfer-properties', [TransactionController::class, 'transfer_properties']);
    Route::get('selfcashout-properties', [TransactionController::class, 'selfcashout_properties']);



    //Airtime
    Route::post('buy-airtime', [AirtimeController::class, 'buy_airtime']);

    //Buy Data Bundle
    Route::post('buy-data', [DataController::class, 'buy_data']);

    //Power
    Route::post('verify-account', [PowerController::class, 'verify_account']);
    Route::post('buy-power', [PowerController::class, 'buy_power']);

    //Get  Transactions
    Route::get('all-transaction', [TransactionController::class, 'get_all_transactions']);
    Route::get('get-pos', [TransactionController::class, 'pos']);
    Route::get('get-transfers', [TransactionController::class, 'transfer']);
    Route::get('get-vas', [TransactionController::class, 'vas']);

    //Bank Transfer
    Route::post('bank-transfer', [TransactionController::class, 'bank_transfer']);

    //Virtual Acccount
    Route::post('create-account', [VirtualaccountController::class, 'create_account']);
    Route::get('get-virtual-account', [VirtualaccountController::class, 'get_virtual_account']);

    //Education
    Route::get('get-waec', [EducationController::class, 'get_waec']);
    Route::post('buy-waec', [EducationController::class, 'buy_waec']);

    //Cable
    Route::get('get-cable-plan', [CableController::class, 'get_cable_plan']);
    Route::post('buy-cable', [CableController::class, 'buy_cable']);


    //
    Route::post('logout', [LoginController::class, 'logout']);



    //





    //insurance
    Route::get('get-motor-insurance', [InsuranceController::class, 'third_party_motor']);
    Route::get('get-health-insurance', [InsuranceController::class, 'health_insurance']);
    Route::get('personal-accident-insurance', [InsuranceController::class, 'personal_accident_insurance']);
    Route::get('home-cover-insurance', [InsuranceController::class, 'home_cover_insurance']);
    Route::get('extra-home-cover-insurance', [InsuranceController::class, 'extra_home_cover_insurance']);












});
