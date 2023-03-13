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

class ProfileController extends Controller
{

public $success = true;
public $failed = false;


public function user_info(request $request){


    try{
        $user = Auth::user();

        return response()->json([
            'status' => $this->success,
            'data' => $user,

        ],200);

    } catch (\Exception $th) {
        return $th->getMessage();
    }


    }



    
}
