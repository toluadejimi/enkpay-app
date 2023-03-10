<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
use GuzzleHttp\Client as GuzzleClient;
use Mail;




class RegisterationController extends Controller
{

    public $success = true;
    public $failed = false;

    public function phone_verification(Request $request){

        try {


            $phone_no = $request->phone_no;

            $sms_code = random_int(1000, 9999);






            $check_phone_verification = User::where('phone', $phone_no)->first()->is_phone_verified ?? null;
            $check_phone = User::where('phone', $phone_no)->first()->phone ?? null;
            $check_status = User::where('phone', $phone_no)->first()->status ?? null;





            if($check_phone == $phone_no && $check_status == 2){

                return response()->json([
                    'status' => $this->failed,
                        'message' => 'Phone number has been Restricted on ENKPAY'
                       ], 500);

            }

            if($check_phone == $phone_no && $check_phone_verification == 1){

                return response()->json([
                    'status' => $this->failed,
                        'message' => 'Phone Number Already Exist'
                       ], 500);

            }



            if($check_phone == null && $check_phone_verification == null){

                $user = new User();
                $user->phone = $phone_no;
                $user->sms_code = $sms_code;
                $user->save();

                $token = $user->createToken('API Token')->accessToken;


                 $headers = [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer FLWSECK-043cf4e9dd848683c6b157c234ba2fb8-X',
                ];

                    $client = new GuzzleClient([
                        'headers' => $headers,
                    ]);


                    $response = $client->request('POST', 'https://api.ng.termii.com/api/sms/send',
                    [
                          'body' => json_encode([
                            "api_key" => "TLxF6Jauos8AJq6pKztkbxaQJbQjZzs43vJLOsXk8fHcUez3mBolehZGGzTwnF",
                            "to" => $phone_no,
                            "from" => "N-Alert",
                            "sms" => "Your Enkwave confirmation code is $sms_code. active for 5 minutes, one-time use only",
                            "type" => "plain",
                            "channel" => "dnd"

                    ]),

                    ]);

                    $body = $response->getBody();
                    $result = json_decode($body);

                    dd($result);


                // return response()->json([
                //     'status'=> $this->success,
                //     'message' => 'OTP Code has been sent succesfully',
                // ],200);

            }


            if($check_phone == $phone_no && $check_phone_verification == 0){

                $update_code = User::where('phone', $phone_no)
                ->update([
                    'sms_code'=> $sms_code
                ]);

                $curl = curl_init();
                $data = array(

                    "api_key" => "TLxF6Jauos8AJq6pKztkbxaQJbQjZzs43vJLOsXk8fHcUez3mBolehZGGzTwnF",
                     "to" => $phone_no,
                     "from" => "ENKWAVE",
                     "sms" => "Your Verification Code is $sms_code",
                     "type" => "plain",
                     "channel" => "generic"

                );

                $post_data = json_encode($data);

                curl_setopt_array($curl, array(
               // CURLOPT_URL => "https://api.ng.termii.com/api/sms/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
                ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);

                $var = json_decode($var);

                return response()->json([
                    'status'=> $this->success,
                    'message' => 'OTP Code has been sent succesfully'
                ],200);

            }


        } catch (\Exception $e) {
            return $e->getMessage();
        }



    }

    public function email_verification(Request $request){

        try {


            $email = $request->email;

            $sms_code = random_int(1000, 9999);


            $check_status = User::where('email', $email)->first()->status ?? null;
            $check_email = User::where('email', $email)->first()->email ?? null;
            $check_email_verification = User::where('email', $email)->first()->is_email_verified ?? null;



            if($check_email == $email && $check_status == 2){

                return response()->json([
                    'status' => $this->failed,
                        'message' => 'Account has been Restricted on ENKPAY'
                       ], 500);

            }

            if($check_email == $email && $check_email_verification == 1){

                return response()->json([
                    'status' => $this->failed,
                        'message' => 'Email Already Exist'
                       ], 500);

            }


            if($check_email == null && $check_email_verification == null){

                $user = new User();
                $user->email = $email;
                $user->sms_code = $sms_code;
                $user->save();

                $token = $user->createToken('API Token')->accessToken;


                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "One Time Password",
                    'toreceiver' => $email,
                    'sms_code' => $sms_code
                );

                Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });


                return response()->json([
                    'status'=> $this->success,
                    'message' => "OTP Code has been sent succesfully to $email",
                ],200);

            }


            if($check_email == $email && $check_email_verification == 0){

                $update_code = User::where('email', $email)
                ->update([
                    'sms_code'=> $sms_code
                ]);


                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "One Time Password",
                    'toreceiver' => $email,
                    'sms_code' => $sms_code
                );

                Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });





                return response()->json([
                    'status'=> $this->success,
                    'message' => 'OTP Code has been sent succesfully'
                ],200);

            }


        } catch (\Exception $e) {
            return $e->getMessage();
        }



    }



    public function resend_email_otp(Request $request){


        try {


            $email = $request->email;

            $sms_code = random_int(1000, 9999);

            $check_email_verification = User::where('email', $email)->first()->is_phone_verified ?? null;
            $check_email = User::where('email', $email)->first()->email ?? null;


            if($check_email == $email && $check_email_verification == 0){

                $update_code = User::where('email', $email)
                ->update([
                    'sms_code'=> $sms_code
                ]);


                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "One Time Password",
                    'toreceiver' => $email,
                    'sms_code' => $sms_code
                );

                Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });


                return response()->json([
                    'status'=> $this->success,
                    'message' => 'OTP Code has been sent succesfully'
                ],200);

            }



        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }





    public function resend_otp(Request $request){


        try {


            $phone_no = $request->phone_no;

            $sms_code = random_int(1000, 9999);

            $check_phone_verification = User::where('phone', $phone_no)->first()->is_phone_verified ?? null;
            $check_phone = User::where('phone', $phone_no)->first()->phone ?? null;


            if($check_phone == $phone_no && $check_phone_verification == 0){

                $update_code = User::where('phone', $phone_no)
                ->update([
                    'sms_code'=> $sms_code
                ]);

                $curl = curl_init();
                $data = array(

                    "api_key" => "TLxF6Jauos8AJq6pKztkbxaQJbQjZzs43vJLOsXk8fHcUez3mBolehZGGzTwnF",
                     "to" => $phone_no,
                     "from" => "ENKWAVE",
                     "sms" => "Your Verification Code is $sms_code",
                     "type" => "plain",
                     "channel" => "generic"

                );

                $post_data = json_encode($data);

                curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ng.termii.com/api/sms/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
                ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);

                $var = json_decode($var);

                return response()->json([
                    'status'=> $this->success,
                    'message' => 'OTP Code has been sent succesfully'
                ],200);

            }



        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }


    public function verify_number_otp(Request $request){



        try {

        $phone_no = $request->phone_no;
        $code = $request->code;

        $get_code = User::where('phone', $phone_no)->first()->sms_code;

        if($code == $get_code){

            $update = User::where('phone', $phone_no)
            ->update([

                'is_phone_verified' => 1,

            ]);

            return response()->json([
                'status'=> $this->success,
                'message' => 'OTP Code verified successfully'
            ],200);

        }   return response()->json([
            'status'=> $this->failed,
            'message' => 'Invalid code, try again'
        ],500);

    } catch (\Exception $th) {
       return $th->getMessage();
    }
    }


    public function verify_email_otp(Request $request){



        try {

        $email = $request->email;
        $code = $request->code;

        $get_code = User::where('email', $email)->first()->sms_code;

        if($code == $get_code){

            $update = User::where('email', $email)
            ->update([

                'is_phone_verified' => 1,

            ]);

            return response()->json([
                'status'=> $this->success,
                'message' => 'OTP Code verified successfully'
            ],200);

        }   return response()->json([
            'status'=> $this->failed,
            'message' => 'Invalid code, try again'
        ],500);

    } catch (\Exception $th) {
       return $th->getMessage();
    }
    }


    public function register(Request $request){



        try{

            $phone_no = $request->phone_no;
            $first_name = $request->first_name;
            $last_name = $request->last_name;
            $dob = $request->dob;
            $email = $request->email;
            $gender = $request->gender;
            $street = $request->street;
            $city = $request->city;
            $state = $request->state;
            $lga = $request->lga;
            $password = $request->password;
            $pin = $request->pin;
            $devide_id = $request->devide_id;



            $update = User::where('phone', $phone_no)
            ->update([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'dob' => $dob,
                'gender' => $gender,
                'email' => $email,
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'lga' =>$lga,
                'password' => bcrypt($password),
                'pin' => bcrypt($pin),
                'device_id' => $devide_id


            ]);



            return response()->json([
                'status'=> $this->success,
                'message' => 'Your account has been successfully created',
            ],200);

        } catch (\Exception $e) {
            return $e->getMessage();
        }






    }






}






