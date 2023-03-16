<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Wallet;

use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Laravel\Passport\Passport;
use Laravel\Passport\HasApiTokens;



class LoginController extends Controller
{



public $success = true;
public $failed = false;



public function phone_login(Request $request){


    try{



        $credentials = request(['phone', 'password']);

        Passport::tokensExpireIn(Carbon::now()->addMinutes(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(15));

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Phone No or Password Incorrect'
            ], 500);
        }


        $token = auth()->user()->createToken('API Token')->accessToken;

        // $user = User::select(
        // 'id',
        // 'first_name',
        // 'last_name',
        // 'phone',
        // 'email',
        // 'image',
        // 'type',
        // 'is_phone_verified',
        // 'is_email_verified',
        // 'gender',
        // 'device_id',
        // 'fcm_token',
        // 'identification_type',
        // 'identification_number',
        // 'identification_image',
        // 'is_kyc_verified',
        // 'street',
        // 'city',
        // 'state',
        // 'lga',
        // 'bank_name',
        // 'account_number',
        // 'account_name',
        // 'main_wallet',
        // 'bonus_wallet',
        // 'virtual_account',
        // 'sms_code',
        // 'status',
        // 'dob')

        // ->where('id', Auth::id())->get();

        //$data = User::where('id', Auth::id()->get






        return response()->json([
            'status' => $this->success,
            'data' => Auth()->user(),
            'token' => $token

        ],200);

} catch (\Exception $th) {
    return $th->getMessage();
}

}


public function email_login(Request $request){


    try{

        $credentials = request(['email', 'password']);

        Passport::tokensExpireIn(Carbon::now()->addMinutes(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(15));

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Email or Password Incorrect'
            ], 500);
        }


        $token = auth()->user()->createToken('API Token')->accessToken;

        $user = User::select(
        'id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'image',
        'type',
        'is_phone_verified',
        'is_email_verified',
        'gender',
        'device_id',
        'fcm_token',
        'identification_type',
        'identification_number',
        'identification_image',
        'is_kyc_verified',
        'street',
        'city',
        'state',
        'lga',
        'bank_name',
        'account_number',
        'account_name',
        'main_wallet',
        'bonus_wallet',
        'virtual_account',
        'sms_code',
        'status',
        'dob')

        ->where('id', Auth::id())->get();


        return response()->json([
            'status' => $this->success,
            'data' => $user,
            'token' => $token

        ],200);

} catch (\Exception $th) {
    return $th->getMessage();
}

}





}
