<?php

namespace App\Http\Controllers\VAS;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mail;
use Session;

class DataController extends Controller
{

    public $success = true;
    public $failed = false;



    public function get_data(){



        try {


            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=mtn-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_mtn_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=glo-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_glo_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=airtel-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_airtel_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=etisalat-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_9mobile_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=smile-direct');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_smile_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=spectranet');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_spectranet_network = $result->content->variations;


            return response()->json([


                'status' => $this->success,
                'mtn_data' =>  $get_mtn_network,
                'glo_data' =>  $get_glo_network,
                'airtel_data' =>  $get_airtel_network,
                '9mobile_data' =>  $get_9mobile_network,
                'smile_data' =>  $get_smile_network,
                'spectranet_data' =>  $get_spectranet_network

            ], 200);



        } catch (\Exception$th) {
            return $th->getMessage();
        }








    }




    public function buy_data(Request $request)
    {


        try{


        $auth = env('VTAUTH');

        $request_id = date('YmdHis') . Str::random(4);

        $serviceid = $request->service_id;

        $biller_code = preg_replace('/[^0-9]/', '', $request->biller_code);

        $phone = preg_replace('/[^0-9]/', '', $request->biller_code);

        $variation_code = $request->variation_code;

        $amount = preg_replace('/[^0-9]/', '', $request->variation_code);

        if ($wallet == 'main_account') {
            $user_wallet_banlance = main_account();
        } else {
            $user_wallet_banlance = bonus_account();
        }

        $user_pin = Auth()->user()->pin;

        if (Hash::check($pin, $user_pin) == false) {

            return response()->json([

                'status' => $this->failed,
                'message' => 'Invalid Pin, Please try again',

            ], 500);
        }

        if ($amount > $user_wallet_banlance) {

            if (!empty(user_email())) {

                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Low Balance",
                    'toreceiver' => user_email(),
                    'first_name' => first_name(),
                    'amount' => $amount,
                    'phone' => $phone,
                    'balance' => $user_wallet_banlance,

                );

                Mail::send('emails.notify.lowbalalce', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });
            }

            return response()->json([

                'status' => $this->failed,
                'message' => 'Insufficient Funds, Fund your wallet',

            ], 500);

        }




        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://vtpass.com/api/pay',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'request_id' => $request_id,
                'variation_code' => $variation_code,
                'serviceID' => $serviceid,
                'amount' => $amount,
                'biller_code' => $biller_code,
                'phone' => $phone,
            ),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $auth=",
                'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);


        $trx_id = $var->requestId ?? null;

            if ($var->response_description == 'TRANSACTION SUCCESSFUL') {

                $debit = $user_wallet_banlance - $amount;

                if ($wallet == 'main_account') {

                    $update = User::where('id', Auth::id())
                        ->update([
                            'main_wallet' => $debit,
                        ]);

                } else {
                    $update = User::where('id', Auth::id())
                        ->update([
                            'bonus_wallet' => $debit,
                        ]);
                }


                if ($wallet == 'main_account') {

                    $balance = $user_wallet_banlance - $amount;

                } else {

                    $balance = $user_wallet_banlance - $amount;

                }

                $transaction = new Transaction();
                $transaction->user_id = Auth::id();
                $transaction->ref_trans_id = $referenceCode;
                $transaction->transaction_type = "DATA BUNDLE";
                $transaction->type = "vas";
                $transaction->balance = $balance;
                $transaction->debit = $amount;
                $transaction->status = 1;
                $transaction->note = "Data Bundle Purchase to $phone";
                $transaction->save();

                if (!empty(user_email())) {
                    //send email
                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Airtime Purchase",
                        'toreceiver' => user_email(),
                        'first_name' => first_name(),
                        'amount' => $amount,
                        'phone' => $phone,

                    );

                    Mail::send('emails.vas.airtime', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });

                }

                return response()->json([

                    'status' => $this->success,
                    'message' => 'Data Bundle Purchase Successfull',

                ], 200);

            }

            return response()->json([

                'status' => $this->failed,
                'message' => 'Service unavilable please try again later',

            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }





}