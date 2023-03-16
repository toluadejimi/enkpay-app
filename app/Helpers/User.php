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
        return $user->user_phone;
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
