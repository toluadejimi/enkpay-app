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
