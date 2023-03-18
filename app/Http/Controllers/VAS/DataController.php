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

      
        $auth = env('VTAUTH');

        $request_id = date('YmdHis') . Str::random(4);

        $serviceid = $request->service_id;

        $biller_code = preg_replace('/[^0-9]/', '', $request->biller_code);

        $phone = preg_replace('/[^0-9]/', '', $request->biller_code);

        $variation_code = $request->variation_code;

        $amount = preg_replace('/[^0-9]/', '', $request->variation_code);

        $transfer_pin = $request->pin;

        $user_wallet_banlance = EMoney::where('user_id', Auth::user()->id)
            ->first()->current_balance;

        $getpin = Auth()->user();
        $user_pin = $getpin->pin;

        if (Hash::check($transfer_pin, $user_pin) == false) {
            return back()->with('error', 'Invalid Pin');
        }

        if ($amount < 100) {
            return back()->with('error', 'Amount must not be less than NGN 100');
        }

        if ($amount > $user_wallet_banlance) {

            return back()->with('error', 'Insufficient Funds, Fund your wallet');

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

        $trx_id = $var->requestId;

        if ($var->response_description == 'TRANSACTION SUCCESSFUL') {

            $user_amount = EMoney::where('user_id', Auth::id())
                ->first()->current_balance;

            $debit = $user_amount - $amount;
            $update = EMoney::where('user_id', Auth::id())
                ->update([
                    'current_balance' => $debit,
                ]);

            $transaction = new Transaction();
            $transaction->ref_trans_id = Str::random(10);
            $transaction->user_id = Auth::id();
            $transaction->transaction_type = "cash_out";
            $transaction->type = "vas";
            $transaction->debit = $amount;
            $transaction->note = "Data Purchase to $phone";
            $transaction->save();

            $email = User::where('id', Auth::id())
                ->first()->email;

            $f_name = User::where('id', Auth::id())
                ->first()->f_name;

            $client = new Client([
                'base_uri' => 'https://api.elasticemail.com',
            ]);

            $res = $client->request('GET', '/v2/email/send', [
                'query' => [

                    'apikey' => "$api_key",
                    'from' => "$from",
                    'fromName' => 'Cardy',
                    'sender' => "$from",
                    'senderName' => 'Cardy',
                    'subject' => 'Airtime VTU Purchase',
                    'to' => "$email",
                    'bodyHtml' => view('airtime-notification', compact('f_name', 'amount', 'phone'))->render(),
                    'encodingType' => 0,

                ],
            ]);

            $body = $res->getBody();
            $array_body = json_decode($body);

            return back()->with('message', 'Data Purchase Successfull');

        }return back()->with('error', "Failed!! Please try again later");
    }









}
