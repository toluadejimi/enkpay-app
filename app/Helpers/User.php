<?php


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
use Laravel\Passport\Passport;
use Laravel\Passport\HasApiTokens;



if(!function_exists('main_account')){

    function main_account(){
        $user = Auth::user();
        return $user->main_wallet;
    }

}

if(!function_exists('bonus_account')){

    function bonus_account(){
        $user = Auth::user();
        return $user->bonus_wallet;
    }

}


if(!function_exists('user_email')){

    function user_email(){
        $user = Auth::user();
        return $user->email;
    }

}


if(!function_exists('user_phone')){

    function user_phone(){
        $user = Auth::user();
        return $user->phone;
    }

}

if(!function_exists('user_bvn')){

    function user_bvn(){
        $user = Auth::user();
        return $user->identification_number;
    }

}


if(!function_exists('first_name')){

    function first_name(){
        $user = Auth::user();
        return $user->first_name;
    }

}

if(!function_exists('last_name')){

    function last_name(){
        $user = Auth::user();
        return $user->last_name;
    }

}


if(!function_exists('user_status')){

    function user_status(){
        $user = Auth::user();
        return $user->status;
    }

}


if(!function_exists('select_account')){

    function select_account(){

        $account = User::where('id', Auth::id())->first();

        // dd($account->main_wallet);
        $account_array = array();
        $account_array[0] =  [
            "title"=>"Main Account",
            "amount"=> $account->main_wallet,
            "key" => "main_account"

        ];
        $account_array[1] =  [
            "title"=>"Bonus Account",
            "amount"=> $account->bonus_wallet,
            "key" => "bonus_account"
        ];

        return  $account_array ;
    }



}
