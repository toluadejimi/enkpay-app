<?php

namespace App\Http\Controllers\VAS;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Power;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mail;
use Session;

class PowerController extends Controller
{

    public $success = true;
    public $failed = false;




    public function get_eletric_company(request $request){


        $data1 = Power::select('name', 'code')->get();


        return response()->json([
            'status' => $this->success,
            'data' =>  $data1,
        ], 200);



    }



    public function verify_account(request $request){


        try {

            $auth = env('VTAUTH');

            $billersCode = $request->biller_code;
            $serviceID = $request->service_id;
            $type = $request->type;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://vtpass.com/api/merchant-verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'billersCode' => $billersCode,
                    'serviceID' => $serviceID,
                    'type' => $type,
                ),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic $auth=",
                    'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            //dd($var);



            $status = $var->content->WrongBillersCode ?? null;

            $status1 = $var->content->error ?? null;





            if ($status == true) {

                return response()->json([
                    'status' => $this->failed,
                    'message' =>  $status1,
                ], 500);

            }


            if( $status1 !== null){

                return response()->json([
                    'status' => $this->failed,
                    'message' =>  $status1,
                ], 500);

            }



            if ($var->code == 000) {

                $customer_name = $var->content->Customer_Name;
                $eletric_address = $var->content->Address;
                $meter_no = $var->content->Meter_Number ?? $var->content->MeterNumber;

                $update = User::where('id', Auth::id())
                    ->update([
                        'meter_number' => $meter_no,
                        'eletric_company' => $serviceID,
                        'eletric_type' => $type,
                        'eletric_address' => $eletric_address,

                    ]);


                    return response()->json([
                        'status' => $this->success,
                        'data' =>  $customer_name,
                    ], 200);

            }



    } catch (\Exception$th) {
        return $th->getMessage();
    }

    }


    public function buy_power(request $request){


        try {



                $auth = env('VTAUTH');

                $request_id = date('YmdHis') . Str::random(4);

                $serviceid = User::where('id', Auth::id())
                    ->first()->eletric_company;

                $biller_code = User::where('id', Auth::id())
                    ->first()->meter_number;

                $variation_code = User::where('id', Auth::id())
                    ->first()->eletric_type;

                $phone = User::where('id', Auth::id())
                    ->first()->phone;

                $amount = $request->amount;

                $transfer_pin = $request->pin;

                $eletricity_charges = Charge::where('title', 'eletricity_charges')
                    ->first()->amount;

                $user_wallet_banlance = EMoney::where('user_id', Auth::user()->id)
                    ->first()->current_balance;

                $getpin = Auth()->user();
                $user_pin = $getpin->pin;

                if (Hash::check($transfer_pin, $user_pin) == false) {
                    return back()->with('error', 'Invalid Pin');
                }




                if ($amount < 1000) {
                    return back()->with('error', 'Amount must not be less than NGN 1000');
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
                        'serviceID' => $serviceid,
                        'billersCode' => $biller_code,
                        'variation_code' => $variation_code,
                        'amount' => $amount,
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


                $token = $var->purchased_code;

                if ($var->response_description == 'TRANSACTION SUCCESSFUL') {

                    $user_amount = EMoney::where('user_id', Auth::id())
                        ->first()->current_balance;

                    $new_amount = $amount + $eletricity_charges;
                    $debit = $user_amount - $new_amount;
                    $update = EMoney::where('user_id', Auth::id())
                        ->update([
                            'current_balance' => $debit,
                        ]);

                    $transaction = new Transaction();
                    $transaction->ref_trans_id = Str::random(10);
                    $transaction->user_id = Auth::id();
                    $transaction->transaction_type = "cash_out";
                    $transaction->debit = $new_amount;
                    $transaction->type = 'vas';
                    $transaction->note = "Token Purchase - $token";
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
                            'subject' => 'Eletricity Token Purchase',
                            'to' => "$email",
                            'bodyHtml' => view('eletricity-with-token-notification', compact('f_name', 'new_amount', 'token'))->render(),
                            'encodingType' => 0,

                        ],
                    ]);

                    //send recepit
                    $email = User::where('id', Auth::id())
                        ->first()->email;

                    $recepit = random_int(10000, 99999);

                    $date = date('Y-m-d H:i:s');

                    $f_name = User::where('id', Auth::id())
                        ->first()->f_name;

                    $l_name = User::where('id', Auth::id())
                        ->first()->l_name;

                    $eletric_address = User::where('id', Auth::id())
                        ->first()->eletric_address;

                    $phone = User::where('id', Auth::id())
                        ->first()->phone;

                    $data = array(
                        'fromsender' => 'notify@admin.cardy4u.com', 'CARDY',
                        'subject' => "Recepit for Eletricity Token Purchase",
                        'toreceiver' => $email,
                        'recepit' => $recepit,
                        'date' => $date,
                        'f_name' => $f_name,
                        'l_name' => $l_name,
                        'eletric_address' => $eletric_address,
                        'phone' => $phone,
                        'token' => $token,
                        'new_amount' => $new_amount,
                    );

                    Mail::send('eletricty-recepit', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });

                    return back()->with('message', ' Purchase Successfull, Check your email for Token');

                }return back()->with('error', "Failed!! Please try again later");




    } catch (\Exception$th) {
        return $th->getMessage();
    }

}



}
