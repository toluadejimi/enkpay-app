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
use App\Models\ErrandKey;
use Mail;




class ProfileController extends Controller
{

public $success = true;
public $failed = false;


public function user_info(request $request){


    try{

        $GetToken = $request->header('Authorization');

        $string = $GetToken;
        $toBeRemoved = "Bearer ";
        $token = str_replace($toBeRemoved, "", $string);

        $user = Auth::user();
        $user['token']=$token;

        return response()->json([
            'status' => $this->success,
            'data' => $user,

        ],200);

    } catch (\Exception $th) {
        return $th->getMessage();
    }


    }


    public function update_user(request $request){


        try{





            //$data1 = $response1[1]



            $errand_key = ErrandKey::where('id', 1)->first()->errand_key ?? null;

            if($errand_key == null){
                $response1 = errand_api_key();
                $update = ErrandKey::where('id', 1)
                ->update([
                    'errand_key' => $response1[0],
                ]);
            }



            $databody = array(

                'userId' => Auth::id(),
                'customerBvn' => Auth::user()->identification_number,
                'phoneNumber' => Auth::user()->phone,
                'customerName' => Auth::user()->first_name. " " .Auth::user()->last_name,


            );

            $body = json_encode($databody);
            $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/CreateVirtualAccount',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    "Authorization: Bearer $errand_key"
                ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);


                $var = json_decode($var);

                dd($var);










        } catch (\Exception $th) {
            return $th->getMessage();
        }


    }


    public function update_info(request $request){

        try{

            $data = $request->all();


            $update = User::where('id', Auth::id())
            ->update([

                'identification_type' => $request->$data['identification_type'],
                'identification_number' => $request->$data['identification_number'],

            ]);



            $databody = array(

                'userId' => Auth::id(),
                'kycType' => "BVN",
                'token' => Auth::user()->identification_type,
                'bankCode' => null,

                identification_number


            );

            $body = json_encode($databody);
            $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/GetKycDetails',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    "Authorization: Bearer $errand_key"
                ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);


                $var = json_decode($var);

                dd($var);




        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }


    public function verify_info(request $request){

        try{

            $bank_code = $request->bank_code;
            $account_number = $request->account_number;
            $bvn = $request->bvn;



            $databody = array(

                'accountNumber' => $account_number,
                'institutionCode' => $bank_code,
                'channel' => "Bank",

            );

            $body = json_encode($databody);
            $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/AccountNameVerification',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);
                $var = json_decode($var);


                $get_string = User::where('id', Auth::id())
                ->first()->first_name;
                $get_string2 = User::where('id', Auth::id())
                ->first()->last_name;

                $verify_name = $var->data->name;

                $first_name = strtoupper($get_string);
                $last_name = strtoupper($get_string2);


                if (str_contains($verify_name, $first_name) && str_contains($verify_name, $last_name) ) {

                    $update = User::where('id', Auth::id())
                    ->update([
                    'is_identification_verified' => 1,
                    'bvn' => $bvn,

                    ]);

                    return response()->json([
                        'status' => $this->success,
                        'message' => "Account has been successfully verified",

                    ],200);
                }

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Sorry we could not verify your account information",

                ],500);


        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }


    public function update_account_info(request $request){


        try{

        $first_name = $request->first_name;
        $last_name  = $request->last_name;
        $address = $request->address;
        $state = $request->state;
        $city = $request->city;
        $lga = $request->lga;


        $update = User::where('id', Auth::id())
        ->update([

            'first_name' => $first_name,
            'last_name' => $last_name,
            'address_line1' => $address,
            'state' => $state,
            'city' => $city,
            'lga' => $lga

        ]);


        return response()->json([
            'status' => $this->success,
            'message' => "Account has been successfully updated",

        ],200);

    } catch (\Exception $th) {
        return $th->getMessage();
    }

    }




    public function forgot_pin(Request $request)
    {

        try{

        $email = $request->email;

        $check = User::where('email', $email)
            ->first()->email ?? null;

        $first_name = User::where('email', $email)
            ->first()->first_name ?? null;

        if ($check == $email) {

            //send email
            $data = array(
                'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                'subject' => "Reset Pin",
                'toreceiver' => $email,
                'first_name' => $first_name,
                'link' => url('') . "/forgot_pin/?email=$email",
            );

            Mail::send('emails.notify.pinlink', ["data1" => $data], function ($message) use ($data) {
                $message->from($data['fromsender']);
                $message->to($data['toreceiver']);
                $message->subject($data['subject']);
            });

            return response()->json([
                'status' => $this->success,
                'message' => 'Check your inbox or spam for instructions',
            ], 200);

        } else {

            return response()->json([

                'status' => $this->failed,
                'message' => 'User not found on our system',

            ], 500);

        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => $this->failedStatus,
            'message' => $e->getMessage(),
        ], 500);
    }

}


    public function view_agent_account(Request $request){

        try{

        $serial_no = $request->serialNumber;

        $check_serial = User::where('serial_no', $serial_no)
        ->first()->serial_no ?? null;


        if($check_serial == null){

            return response()->json([
                'status' => $this->failed,
                'message' => "Account no available on ENKPAY",
            ],500);

        }

        $firstName = User::where('serial_no', $serial_no)
        ->first()->first_name ?? null;

        $lastName = User::where('serial_no', $serial_no)
        ->first()->last_name ?? null;

        $bvn = User::where('serial_no', $serial_no)
        ->first()->identification_number ?? null;

        $accountNumber = User::where('serial_no', $serial_no)
        ->first()->v_account_no ?? null;

        $bankName = "VFD MICROFINANCE BANK";

        $data = User::where('serial_no', $serial_no)->first();

        $data_array = array();
        $data_array[0] =  [
            "firstName"=> $data->first_name,
            "lastName"=> $data->last_name,
            "bvn"=> $data->identification_no,
            "accountNumber"=> $data->v_account_no,
            "bankName" => $bankName
        ];





        return response()->json([
            'code' => 200,
            'message' => "success",
            'data' => $data_array

        ],200);



        } catch (\Exception $th) {
            return $th->getMessage();
        }



    }







}








