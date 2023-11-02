<?php

namespace App\Http\Controllers\Auth;

use App\Models\OauthAccessToken;
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

use function PHPUnit\Framework\isEmpty;

class LoginController extends Controller
{



    public $success = true;
    public $failed = false;



    public function phone_login(Request $request)
    {




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


        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Phone No or Password Incorrect'
            ], 500);
        }


        $get_token = OauthAccessToken::where('user_id', Auth::id())->first()->user_id ?? null;

        if($get_token != null){
            OauthAccessToken::where('user_id', Auth::id())->delete();
        }


        $get_device_id = Auth::user()->device_id ?? null;
        $get_deviceIdentifier = Auth::user()->deviceIdentifier ?? null;
        $get_deviceName = Auth::user()->deviceName ?? null;
        $get_ip = Auth::user()->ip_address ?? null;


        $get_device_id = User::where('device_id', $request->device_id)
            ->first()->device_id ?? null;

        if ($get_device_id == null) {

            $update = User::where('id', Auth::id())
                ->update([
                    'device_id' => $request->device_id ?? null,
                ]);
        }

        if ($get_deviceName == null) {

            $update = User::where('id', Auth::id())
                ->update([
                    'deviceName' => $request->deviceName ?? null,
                ]);
        }

        if ($get_deviceIdentifier == null) {

            $update = User::where('id', Auth::id())
                ->update([
                    'deviceIdentifier' => $request->deviceIdentifier ?? null,
                ]);
        }


        User::where('id', Auth::id())
        ->update([
            'ip_address' => $request->ip() ?? null,
        ]);





        if (Auth::user()->status == 5) {


            return response()->json([

                'status' => $this->failed,
                'message' => 'You can not login at the moment, Please contact  support',

            ], 500);
        }




            $feature = Feature::where('id', 1)->first();
            $token = auth()->user()->createToken('API Token')->accessToken;
            $virtual_account = virtual_account();

            $user = Auth()->user();
            $user['token'] = $token;
            $user['user_virtual_account_list'] = $virtual_account;
            $user['terminal_info'] = terminal_info();
            $tid_config = tid_config();



            $is_kyc_verified = Auth::user()->is_kyc_verified;
            $status = Auth::user()->status;
            $is_phone_verified = Auth::user()->is_phone_verified;
            $is_email_verified = Auth::user()->is_email_verified;
            $is_identification_verified = Auth::user()->is_identification_verified;


            if ($status !== 2 && $is_kyc_verified == 1 && $is_phone_verified == 1 && $is_email_verified == 1 && $is_identification_verified == 1) {

                $update = User::where('id', Auth::id())
                    ->update([
                        'status' => 2
                    ]);
            }

            $setting = Setting::select('google_url', 'ios_url', 'version')
                ->first();



            return response()->json([
                'status' => $this->success,
                'data' => $user,
                'permission' => $feature,
                'isNewDevice' => false,
                'setting' => $setting,
                'tid_config' => $tid_config,

            ], 200);

    }

    public function pin_login(Request $request)
    {


        $email = $request->email ?? null;
        $phone = $request->phone ?? null;
        $pin = $request->pin ?? null;
        $password = $request->password ?? null;







        if($email != null){



        if($request->pin  == null) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Pin can not be empty"
            ], 500);

        }


        if($request->password  == null) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Password can not be empty"
            ], 500);

        }

        if($request->email  == null) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Phone or email can not be empty"
            ], 500);

        }


            $credentials = request(['email', 'password']);
            Passport::tokensExpireIn(Carbon::now()->addMinutes(20));
            Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(20));
            $check_status = User::where('email', $email)->first()->status ?? null;

            $get_pin = User::where('email', $email)->first()->pin;
            if (Hash::check($pin, $get_pin) == false) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Incorrect Pin \n\n Please try again!"
                ], 500);
            }

            if (!auth()->attempt($credentials)) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => "Incorrect Pin \n\n Please try again!"
                ], 500);
            }

            $user = User::where('email', $email)->first();

            if ($user->status == 5) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => 'You can not login at the moment, Please contact  support',

                ], 500);
            }

            $get_token = OauthAccessToken::where('user_id', $user->id)->first()->user_id ?? null;

            if($get_token != null){
                OauthAccessToken::where('user_id', $user->id)->delete();
            }



            $feature = Feature::where('id', 1)->first();
            $token = auth()->user()->createToken('API Token')->accessToken;
            $virtual_account = virtual_account();
            $user = Auth()->user();
            $user['token'] = $token;
            $user['user_virtual_account_list'] = $virtual_account;
            $user['terminal_info'] = terminal_info();
            $setting = Setting::select('google_url', 'ios_url', 'version')
                ->first();

            return response()->json([
                'status' => $this->success,
                'data' => $user,
                'permission' => $feature,
                'isNewDevice' => false,
                'setting' => $setting


            ], 200);



        }

        if($phone != null){


        if($request->pin  == null) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Pin can not be empty"
            ], 500);

        }


        if($request->password  == null) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Password can not be empty"
            ], 500);

        }


            $credentials = request(['phone', 'password']);
            Passport::tokensExpireIn(Carbon::now()->addMinutes(20));
            Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(20));
            $check_status = User::where('phone', $phone)->first()->status ?? null;

            if (!auth()->attempt($credentials)) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => "Incorrect Pin \n\n Please try again!"
                ], 500);
            }


            $get_pin = User::where('phone', $phone)->first()->pin;
            if (Hash::check($pin, $get_pin) == false) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Incorrect Pin \n\n Please try again!"
                ], 500);
            }

            if (!auth()->attempt($credentials)) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => "pIncorrect Pin \n\n Please try again!"
                ], 500);
            }

            if (Auth::user()->status == 5) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => 'You can not login at the moment, Please contact  support',

                ], 500);
            }

            $get_token = OauthAccessToken::where('user_id', Auth::id())->first()->user_id ?? null;

            if($get_token != null){
                OauthAccessToken::where('user_id', Auth::id())->delete();
            }



            $feature = Feature::where('id', 1)->first();
            $token = auth()->user()->createToken('API Token')->accessToken;
            $virtual_account = virtual_account();
            $user = Auth()->user();
            $user['token'] = $token;
            $user['user_virtual_account_list'] = $virtual_account;
            $user['terminal_info'] = terminal_info();
            $setting = Setting::select('google_url', 'ios_url', 'version')
                ->first();

            return response()->json([
                'status' => $this->success,
                'data' => $user,
                'permission' => $feature,
                'isNewDevice' => false,
                'setting' => $setting


            ], 200);


        }

            if($request->pin  == null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Pin can not be empty"
                ], 500);

            }


            if($request->password  == null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Password can not be empty"
                ], 500);

            }

            if($request->email  == null || $request->phone  == null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Phone or email can not be empty"
                ], 500);

            }

    }


    public function email_login(Request $request)
    {


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

        $get_device_id = Auth::user()->device_id ?? null;
        $get_deviceIdentifier = Auth::user()->deviceIdentifier ?? null;
        $get_deviceName = Auth::user()->deviceName ?? null;

        $get_ip = Auth::user()->ip_address ?? null;

        $get_token = OauthAccessToken::where('user_id', Auth::id())->first()->user_id ?? null;

        if($get_token != null){
            OauthAccessToken::where('user_id', Auth::id())->delete();
        }


        $get_device_id = User::where('device_id', $request->device_id)
            ->first()->device_id ?? null;

        if ($get_device_id == null) {

            $update = User::where('id', Auth::id())
                ->update([
                    'device_id' => $request->device_id ?? null,
                ]);
        }

        if ($get_deviceName == null) {

            $update = User::where('id', Auth::id())
                ->update([
                    'deviceName' => $request->deviceName ?? null,
                ]);
        }

        if ($get_deviceIdentifier == null) {

            $update = User::where('id', Auth::id())
                ->update([
                    'deviceIdentifier' => $request->deviceIdentifier ?? null,
                ]);
        }



               User::where('id', Auth::id())
                ->update([
                    'ip_address' => $request->ip() ?? null,
                ]);


        if ($get_deviceIdentifier != null) {

            $current_deviceIdentifier = Auth::user()->deviceIdentifier;
            $new_deviceIdentifier = $request->deviceIdentifier;

            if ($new_deviceIdentifier != $current_deviceIdentifier) {

                $message = Auth::user()->first_name . " " . Auth::user()->last_name . " trying to login  on another device";
                send_notification($message);

                $user = Auth()->user();
                return response()->json([

                    'status' => $this->failed,
                    'data' => $user,
                    'isNewDevice' => true,
                    'message' => 'New device detected, login with the old device or switch to this device',

                ], 200);
            }
        }


        if (Auth::user()->status == 5) {


            return response()->json([

                'status' => $this->failed,
                'message' => 'You can login at the moment,Please contact  support',

            ], 500);
        }

        $get_device_id = User::where('device_id', $request->device_id)
            ->first()->device_id ?? null;

        if ($get_device_id == null) {

            $update = User::where('id', Auth::id())
                ->update([
                    'device_id' => $request->device_id ?? null,
                ]);
        }

        $feature = Feature::where('id', 1)->first();



        $token = auth()->user()->createToken('API Token')->accessToken;

        $virtual_account = virtual_account();

        $user = Auth()->user();
        $user['token'] = $token;
        $user['user_virtual_account_list'] = $virtual_account;
        $user['terminal_info'] = terminal_info();


        $tid_config = tid_config();




        $is_kyc_verified = Auth::user()->is_kyc_verified;
        $status = Auth::user()->status;
        $is_phone_verified = Auth::user()->is_phone_verified;
        $is_email_verified = Auth::user()->is_email_verified;
        $is_identification_verified = Auth::user()->is_identification_verified;


        if ($status !== 2 && $is_kyc_verified == 1 && $is_phone_verified == 1 && $is_email_verified == 1 && $is_identification_verified == 1) {

            $update = User::where('id', Auth::id())
                ->update([
                    'status' => 2
                ]);
        }






        $setting = Setting::select('google_url', 'ios_url', 'version')
            ->first();


        return response()->json([
            'status' => $this->success,
            'data' => $user,
            'permission' => $feature,
            'isNewDevice' => false,
            'setting' => $setting,
            'tid_config' => $tid_config



        ], 200);



    }


    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        $ur = User::where('id', Auth::id())->first() ?? null;
        // if ($ur != null) {
        //     User::where('id', Auth::id())->update(['session_time' => Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00")), 'session' => 0 ]);
        // }
        return response()->json([
            'status' => $this->success,
            'message' => "Successfully logged out"
        ], 200);
    }



    public function update_device_identifier(Request $request)
    {

        $email = $request->email;
        $deviceIdentifier = $request->deviceIdentifier;
        $deviceName = $request->deviceName;
        $code = $request->code;

        if($code == null){
        return response()->json([
            'status' => $this->failed,
            'message' => 'Code can not be empty',
        ], 500);
        }


        if($email == null){
            return response()->json([
                'status' => $this->failed,
                'message' => 'Email can not be empty',
            ], 500);
        }

        $get_code = User::where('email', $email)->first()->sms_code ?? null;

        if ($code == $get_code) {
            $update = User::where('email', $email)
                ->update([
                    'deviceIdentifier' => $deviceIdentifier,
                    'deviceName' => $deviceName
                ]);
            return response()->json([
                'status' => $this->success,
                'deviceName' => $deviceName,
                'message' => "You have successfully added " . $deviceName . " as your primary device. Login to continue"
            ], 200);
        }

        return response()->json([
            'status' => $this->failed,
            'message' => 'Invalid OTP code, try again',
        ], 500);

    }
}
