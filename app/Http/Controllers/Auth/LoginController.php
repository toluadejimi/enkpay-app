<?php

namespace App\Http\Controllers\Auth;

use App\Models\VirtualAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Feature;
use App\Models\Setting;



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


    // try{


        $phone = $request->phone;
        $credentials = request(['phone', 'password']);


        Passport::tokensExpireIn(Carbon::now()->addMinutes(20));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(20));

        $check_status = User::where('phone', $phone)->first()->status ?? null;




        if ($check_status == 3) {

            return response()->json([
                'status' => $this->failed,
                'message' => 'Your account has restricted on ENKPAY',
            ], 500);

        }

        // $ur = User::where('phone', $phone)->first() ?? null;
        // if ($ur != null) {

        //     if ($ur->user_id == Auth::id()) {

        //         $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $ur->session_time);
        //         $currentTime = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
        //         # count difference in minutes
        //         $minuteDiff = $anchorTime->diffInMinutes($currentTime);


        //         if ($minuteDiff >= 1) {
        //             User::where('phone', $phone)->update(['session_time' => Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00")), 'session' => 0 ]);
        //         }

        //     }
        // }

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Phone No or Password Incorrect'
            ], 500);
        }



        if (Auth::user()->status == 5) {


            return response()->json([

                'status' => $this->failed,
                'message' => 'You can login at the moment, Please contact  support',

            ], 500);
        }

        $get_device_id = User::where('device_id', $request->device_id)
        ->first()->device_id ?? null;

        if($get_device_id == null){

            $update = User::where('id',Auth::id())
            ->update([
                'device_id' => $request->device_id ?? null,
            ]);

        }


        //ck session
        $ur = User::where('id', Auth::id())->first() ?? null;
        if ($ur != null) {

            if($ur->session ==1 ){

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'You can only login on a device, Please log out on current device'
                ], 500);

            }
        }



        // $ur = User::where('id', Auth::id())->first() ?? null;
        // if ($ur != null) {
        //     User::where('id', Auth::id())->update(['session_time' => Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"))->toString(), 'session' => 1 ]);
        // }





        $feature = Feature::where('id', 1)->first();
        $token = auth()->user()->createToken('API Token')->accessToken;

        $user = Auth()->user();
        $user['token']=$token;

        $is_kyc_verified = Auth::user()->is_kyc_verified;
        $status = Auth::user()->status;
        $is_phone_verified = Auth::user()->is_phone_verified;
        $is_email_verified = Auth::user()->is_email_verified;
        $is_identification_verified = Auth::user()->is_identification_verified;


        if($status !== 2 && $is_kyc_verified == 1 && $is_phone_verified == 1 && $is_email_verified == 1 && $is_identification_verified == 1  ){

            $update = User::where('id',Auth::id())
            ->update([
                'status' => 2
            ]);


        }


        $setting = Setting::select('google_url','ios_url','version')
        ->first();

        $acc_no = Auth::user()->v_account_no ?? null;




        if($acc_no == null){

            $v_account_no = VirtualAccount::where('user_id', Auth::id())
            ->first()->v_account_no ?? null;
            $v_account_name = VirtualAccount::where('user_id', Auth::id())
            ->first()->v_account_name ?? null;
            $v_bank_name = VirtualAccount::where('user_id', Auth::id())
            ->first()->v_bank_name ?? null;

            if($v_bank_name == null){

                return response()->json([
                    'status' => $this->success,
                    'data' => $user,
                    'permission' => $feature,
                    'setting' => $setting

                ],200);
            }

            User::where('id', Auth::id())
            ->update([
                'v_account_no' => $v_account_no,
                'v_account_name' => $v_account_name,
                'v_bank_name' => $v_bank_name,
            ]);

        }


        return response()->json([
            'status' => $this->success,
            'data' => $user,
            'permission' => $feature,
            'setting' => $setting


        ],200);

        // $feature = Feature::where('id', 1)->first();

        // $token = auth()->user()->createToken('API Token')->accessToken;


        // $user = Auth()->user();
        // $user['token']=$token;


        // $is_kyc_verified = Auth::user()->is_kyc_verified;
        // $status = Auth::user()->status;
        // $is_phone_verified = Auth::user()->is_phone_verified;
        // $is_email_verified = Auth::user()->is_email_verified;
        // $is_identification_verified = Auth::user()->is_identification_verified;


        // if($status !== 2 && $is_kyc_verified == 1 && $is_phone_verified == 1 && $is_email_verified == 1 && $is_identification_verified == 1  ){

        //     $update = User::where('id',Auth::id())
        //     ->update([
        //         'status' => 2
        //     ]);


        // }

        // $acc_no = Auth::user()->v_account_no ?? null;
        // if($acc_no == null){

        //     $v_account_no = VirtualAccount::where('user_id', Auth::id())
        //     ->first()->v_account_no;
        //     $v_account_name = VirtualAccount::where('user_id', Auth::id())
        //     ->first()->v_account_name;
        //     $v_bank_name = VirtualAccount::where('user_id', Auth::id())
        //     ->first()->v_bank_name;

        //     $update = User::where('id', Auth::id())
        //     ->update([
        //         'v_account_no' => $v_account_no,
        //         'v_account_name' => $v_account_name,
        //         'v_bank_name' => $v_bank_name,
        //     ]);

        // }
        // $get_user = User::find(Auth::id())->first();

        // $setting = Setting::select('google_url','ios_url','version')
        // ->first();







        // return response()->json([
        //     'status' => $this->success,
        //     'data' => $get_user,
        //     'permission' => $feature,
        //     'setting' => $setting


        // ],200);

// } catch (Exception $th) {
//     return $th->getMessage();
// }

}


public function email_login(Request $request){


    // try{


    


        $email = $request->email;

        $credentials = request(['email', 'password']);

        Passport::tokensExpireIn(Carbon::now()->addMinutes(1));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(1));

        $check_status = User::where('email', $email)->first()->status ?? null;


        if ($check_status == 3) {

            return response()->json([
                'status' => $this->failed,
                'message' => 'Your account has restricted on ENKPAY',
            ], 500);

        }


        // $ur = User::where('email', $email)->first() ?? null;
        // if ($ur != null) {

        //     if ($ur->user_id == Auth::id()) {

        //         $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $ur->session_time);
        //         $currentTime = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
        //         # count difference in minutes
        //         $minuteDiff = $anchorTime->diffInMinutes($currentTime);


        //         if ($minuteDiff >= 1) {
        //             User::where('email', $email)->update(['session_time' => Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00")), 'session' => 0 ]);
        //         }
        //     }
        // }


        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Email or Password Incorrect'
            ], 500);
        }

        
        if (Auth::user()->status == 5) {


            return response()->json([

                'status' => $this->failed,
                'message' => 'You can login at the moment,Please contact  support',

            ], 500);
        }

        $get_device_id = User::where('device_id', $request->device_id)
        ->first()->device_id ?? null;

        if($get_device_id == null){

            $update = User::where('id',Auth::id())
            ->update([
                'device_id' => $request->device_id ?? null,
            ]);

        }

        $feature = Feature::where('id', 1)->first();



        $token = auth()->user()->createToken('API Token')->accessToken;

        $user = Auth()->user();
        $user['token']=$token;


        $is_kyc_verified = Auth::user()->is_kyc_verified;
        $status = Auth::user()->status;
        $is_phone_verified = Auth::user()->is_phone_verified;
        $is_email_verified = Auth::user()->is_email_verified;
        $is_identification_verified = Auth::user()->is_identification_verified;


        if($status !== 2 && $is_kyc_verified == 1 && $is_phone_verified == 1 && $is_email_verified == 1 && $is_identification_verified == 1  ){

            $update = User::where('id',Auth::id())
            ->update([
                'status' => 2
            ]);


        }


      

        //ck session
        // $ur = User::where('id', Auth::id())->first() ?? null;
        // if ($ur != null) {

        //     if($ur->session ==1 ){

        //         return response()->json([
        //             'status' => $this->failed,
        //             'message' => 'You can only login on a device, Please log out on current device'
        //         ], 500);

        //     }
        // }



        // $ur = User::where('id', Auth::id())->first() ?? null;
        // if ($ur != null) {
        //     User::where('id', Auth::id())->update(['session_time' => Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:s")), 'session' => 1 ]);
        // }



       



        $setting = Setting::select('google_url','ios_url','version')
        ->first();


        return response()->json([
            'status' => $this->success,
            'data' => $user,
            'permission' => $feature,
            'setting' => $setting


        ],200);

// } catch (\Exception $th) {
//     return $th->getMessage();
// }

}


public function logout(Request $request) {
    $request->user()->token()->revoke();
    $ur = User::where('id', Auth::id())->first() ?? null;
    // if ($ur != null) {
    //     User::where('id', Auth::id())->update(['session_time' => Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00")), 'session' => 0 ]);
    // }
    return response()->json([
        'status' => $this->success,
        'message' => "Successfully logged out"
    ],200);
  }





}
