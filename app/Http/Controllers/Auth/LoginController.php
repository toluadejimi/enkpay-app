<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Feature;


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


        $phone = $request->phone;


        $credentials = request(['phone', 'password']);

        Passport::tokensExpireIn(Carbon::now()->addMinutes(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(15));

        $check_status = User::where('phone', $phone)->first()->status ?? null;


        if ($check_status == 2) {

            return response()->json([
                'status' => $this->failed,
                'message' => 'Your account has restricted on ENKPAY',
            ], 500);

        }

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Phone No or Password Incorrect'
            ], 500);
        }




        $token = auth()->user()->createToken('API Token')->accessToken;


        $user = Auth()->user();
        $user['feature']=$features;
        $user['token']=$token;


        return response()->json([
            'status' => $this->success,
            'data' => $user,

        ],200);

} catch (\Exception $th) {
    return $th->getMessage();
}

}


public function email_login(Request $request){


    try{

        $email = $request->email;

        $credentials = request(['email', 'password']);

        Passport::tokensExpireIn(Carbon::now()->addMinutes(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(15));

        $check_status = User::where('email', $email)->first()->status ?? null;


        if ($check_status == 2) {

            return response()->json([
                'status' => $this->failed,
                'message' => 'Your account has restricted on ENKPAY',
            ], 500);

        }

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Email or Password Incorrect'
            ], 500);
        }

        $features = Feature::select('name', 'status')->get();



        $token = auth()->user()->createToken('API Token')->accessToken;

        $user = Auth()->user();
        $user['feature']=$features;
        $user['token']=$token;





        return response()->json([
            'status' => $this->success,
            'data' => $user

        ],200);

} catch (\Exception $th) {
    return $th->getMessage();
}

}





}
