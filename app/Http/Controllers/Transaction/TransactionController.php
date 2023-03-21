<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\ErrandKey;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Charge;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mail;

class TransactionController extends Controller
{

    public $success = true;
    public $failed = false;





    public function transfer_charges(){


        try{



            $transfer_charge = Charge::where('title', 'transfer_fee')
            ->first()->amount;

            return response()->json([
                'status' => $this->success,
                'data' => $transfer_charge,
            ], 200);






        }catch (\Exception$th) {
            return $th->getMessage();
        }




    }

    public function bank_transfer(Request $request)
    {

        try {
            $erran_api_key = errand_api_key();

            $wallet = $request->wallet;
            $amount = $request->amount;
            $destinationAccountNumber = $request->account_number;
            $destinationBankCode = $request->code;
            $destinationAccountName = $request->customer_name;
            $longitude = $request->longitude;
            $latitude = $request->latitude;
            $get_description = $request->narration;
            $pin = $request->pin;



            $referenceCode = "ENK-" . random_int(1000000, 999999999);



            $description = $get_description ?? "Fund for $destinationAccountName";



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

            if ($amount < 100) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Amount must not be less than NGN 100',

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
            $data = array(

                "amount" => $amount,
                "destinationAccountNumber"=>$destinationAccountNumber,
                "destinationBankCode" => $destinationBankCode,
                "destinationAccountName" => $destinationAccountName,
                "longitude" => $longitude,
                "latitude" => $latitude,
                "description" => $description

            );

            $post_data = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/ApiFundTransfer',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $erran_api_key",
                    'Content-Type: application/json',
                ),
            ));

            $var = curl_exec($curl);

            dd($var);

            curl_close($curl);

            $var = json_decode($var);

            dd($var);



        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function get_banks()
    {

        try {

            $errand_key = ErrandKey::where('id', 1)->first()->errand_key ?? null;

            if ($errand_key == null) {
                $response1 = errand_api_key();
                $update = ErrandKey::where('id', 1)
                    ->update([
                        'errand_key' => $response1[0],
                    ]);
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/ApiGetBanks',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $errand_key",
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            $result = $var->data;

            if ($var->code == 200) {

                return response()->json([
                    'status' => $this->success,
                    'data' => $result,
                ], 200);

            }

            // $code = $var->code ?? null;

            //     $response1 = $var->data ?? null;
            //     $respose2 = 'ERA 001 Please try again later';

            //     if($code == null){

            //         return response()->json([
            //             'status' => $this->failed,
            //             'data' => $erran_api_key
            //         ], 500);

            //     }elseif($var->code == 200){

            //         return response()->json([
            //             'status' => $this->success,
            //             'data' => $response1
            //         ], 200);

            //     }else{
            //         return response()->json([
            //             'status' => $this->failed,
            //             'data' => $response2
            //         ], 500);
            //     }

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }


    public function resolve_bank(request $request){

        try{

            $bank_code = $request->bank_code;
            $account_number = $request->account_number;
            //$bvn = $request->bvn;



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


                $customer_name = $var->data->name ?? null;
                $error = $var->error->message ?? null;


                if($var->code == 200){

                    return response()->json([
                        'status' => $this->success,
                        'customer_name' => $customer_name,

                    ],200);

                }

                return response()->json([
                    'status' => $this->failed,
                    'message' => $error,

                ],500);


        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }



    public function cash_out_webhook(Request $request)
    {

        try {

            $header = $request->header('errand-pay-header');

            $StatusCode = $request->StatusCode;
            $StatusDescription = $request->StatusDescription;
            $SerialNumber = $request->SerialNumber;
            $Amount = $request->Amount;
            $Currency = $request->Currency;
            $TransactionDate = $request->TransactionDate;
            $TransactionTime = $request->TransactionTime;
            $TransactionType = $request->TransactionType;
            $ServiceCode = $request->ServiceCode;
            $TransactionReference = $request->TransactionReference;
            $Fee = $request->Fee;
            $PostingType = $request->PostingType;
            $TerminalID = $request->AdditionalDetails['TerminalID'];

            $key = env('ERIP');

            $trans_id = "ENK-" . random_int(100000, 999999);
            $verify1 = hash('sha512', $key);

            if ($verify1 == $header) {

                if ($StatusCode == 00) {

                    $main_wallet = User::where('serial_no', $SerialNumber)
                        ->first()->main_wallet ?? null;

                    $user_id = User::where('serial_no', $SerialNumber)
                        ->first()->id ?? null;

                    if ($main_wallet == null && $user_id == null) {

                        return response()->json([
                            'status' => false,
                            'message' => 'Customer not registred on Enkpay',
                        ], 500);

                    }

                    //credit
                    $updated_amount = $main_wallet + $Amount;
                    $main_wallet = User::where('serial_no', $SerialNumber)
                        ->update([
                            'main_wallet' => $updated_amount,
                        ]);

                    if ($TransactionType == 'CashOut') {

                        //update Transactions
                        $trasnaction = new Transaction();
                        $trasnaction->user_id = $user_id;
                        $trasnaction->ref_trans_id = $trans_id;
                        $trasnaction->e_ref = $TransactionReference;
                        $trasnaction->transaction_type = $TransactionType;
                        $trasnaction->credit = $Amount;
                        $trasnaction->note = "Credit eeceived from POS Terminal";
                        $trasnaction->fee = $Fee;
                        $trasnaction->balance = $updated_amount;
                        $trasnaction->terminal_id = $TerminalID;
                        $trasnaction->serial_no = $SerialNumber;
                        $trasnaction->status = 1;
                        $trasnaction->save();

                    }

                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Account Credited",
                        'toreceiver' => 'toluadejimi@gmail.com',
                        'amount' => $Amount,
                        'serial' => $SerialNumber,
                    );

                    Mail::send('emails.transaction.terminal-credit', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });

                    return response()->json([
                        'status' => true,
                        'message' => 'Tranasaction Successsfull',
                    ], 200);

                }

            }

            return response()->json([
                'status' => false,
                'message' => 'Key not Authorized',
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function cash_in_webhook(Request $request)
    {

        try {



            $header = $request->header('errand-pay-header');

            $StatusCode = $request->StatusCode;
            $StatusDescription = $request->StatusDescription;
            $VirtualCustomerAccount = $request->VirtualCustomerAccount;
            $Amount = $request->Amount;
            $Currency = $request->Currency;
            $TransactionDate = $request->TransactionDate;
            $TransactionTime = $request->TransactionTime;
            $TransactionType = $request->TransactionType;
            $ServiceCode = $request->ServiceCode;
            $Fee = $request->Fee;
            $PostingType = $request->PostingType;
            $TransactionReference = $request->TransactionReference;

            $key = env('ERIP');

            $trans_id = "ENK-" . random_int(100000, 999999);
            $verify1 = hash('sha512', $key);

            if ($verify1 == $header) {

                if ($StatusCode == 00) {

                    $main_wallet = User::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->main_wallet ?? null;

                    $user_id = User::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->id ?? null;

                    if ($main_wallet == null && $user_id == null) {

                        return response()->json([
                            'status' => false,
                            'message' => 'V Account not registred on Enkpay',
                        ], 500);

                    }

                    //credit
                    $updated_amount = $main_wallet + $Amount;
                    $main_wallet = User::where('v_account_no', $VirtualCustomerAccount)
                        ->update([
                            'main_wallet' => $updated_amount,
                        ]);

                    if ($TransactionType == 'FundWallet') {

                        //update Transactions
                        $trasnaction = new Transaction();
                        $trasnaction->user_id = $user_id;
                        $trasnaction->ref_trans_id = $trans_id;
                        $trasnaction->e_ref = $TransactionReference;
                        $trasnaction->transaction_type = $TransactionType;
                        $trasnaction->credit = $Amount;
                        $trasnaction->note = "Credit received from Transfer";
                        $trasnaction->fee = $Fee;
                        $trasnaction->balance = $updated_amount;
                        $trasnaction->status = 1;
                        $trasnaction->save();

                    }

                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Virtual Account Credited",
                        'toreceiver' => 'toluadejimi@gmail.com',
                        'amount' => $Amount,
                        'serial' => $user_id,
                    );

                    Mail::send('emails.transaction.terminal-credit', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });

                    return response()->json([
                        'status' => true,
                        'message' => 'Tranasaction Successsfull',
                    ], 200);

                }

            }

            return response()->json([
                'status' => false,
                'message' => 'Key not Authorized',
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }


    public function balance_webhook(Request $request)
    {

        try {

            $IP = $_SERVER['SERVER_ADDR'];

            $serial_number = $request->serial_number;
            $amount = $request->amount;
            $pin = $request->pin;
            $transaction_type = $request->transaction_type;
            $serviceCode = $request->serviceCode;
            $reference = $request->reference;

            $oip = env('ERIP');

            $trans_id = "ENK-" . random_int(100000, 999999);

            $user_id = User::where('serial_no', $serial_number)
                ->first()->id ?? null;

            if ($user_id == null) {

                return response()->json([
                    'status' => false,
                    'message' => 'Serial_no not found on our system',
                ], 500);

            }

            if ($transaction_type == 'inward') {

                $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;

                $balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;

                $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;

                if ($status == 1) {
                    $agent_status = "Active";
                } else {
                    $agent_status = "InActive";

                }

                return response()->json([

                    'is_pin_valid' => true,
                    'balance' => number_format($balance, 2),
                    'agent_status' => $agent_status,

                ]);

            }

            if ($serviceCode == 'FT1') {

                $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;

                $balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;

                $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;

                if ($status == 1) {
                    $agent_status = "Active";
                } else {
                    $agent_status = "InActive";

                }

                if (Hash::check($pin, $get_pin)) {
                    $is_pin_valid = true;
                } else {
                    $is_pin_valid = false;
                }

                if ($is_pin_valid == true) {

                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $user_id;
                    $trasnaction->ref_trans_id = $reference;
                    $trasnaction->transaction_type = $transaction_type;
                    $trasnaction->debit = $amount;
                    $trasnaction->status = 0;
                    $trasnaction->save();

                }

                return response()->json([

                    'is_pin_valid' => $is_pin_valid,
                    'balance' => number_format($balance, 2),
                    'agent_status' => $agent_status,

                ]);

            }

            if ($serviceCode == 'BLE1') {

                $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;

                $balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;

                $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;

                if ($status == 1) {
                    $agent_status = "Active";
                } else {
                    $agent_status = "InActive";

                }

                if (Hash::check($pin, $get_pin)) {
                    $is_pin_valid = true;
                } else {
                    $is_pin_valid = false;
                }

                return response()->json([

                    'is_pin_valid' => $is_pin_valid,
                    'balance' => number_format($balance, 2),
                    'agent_status' => $agent_status,

                ]);

            }

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function transactiion_status(Request $request)
    {

        try {

            $ref_no = $request->ref_no;
            //$b_code = $request->b_code;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.errandpay.com/epagentservice/api/v1/GetStatus?reference=$ref_no&$b_code",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(

                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            //    $code = $var->code ?? null;

            //     $response1 = $var->data ?? null;
            //     $respose2 = 'ERA 001 Please try again later';

            //     if($code == null){

            //         return response()->json([
            //             'status' => $this->failed,
            //             'data' => $erran_api_key
            //         ], 500);

            //     }elseif($var->code == 200){

            //         return response()->json([
            //             'status' => $this->success,
            //             'data' => $response1
            //         ], 200);

            //     }else{
            //         return response()->json([
            //             'status' => $this->failed,
            //             'data' => $response2
            //         ], 500);
            //     }

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function fund_transfer_webhook(Request $request)
    {

        try {

            $StatusCode = $request->StatusCode;
            $StatusDescription = $request->StatusDescription;
            $SerialNumber = $request->SerialNumber;
            $Amount = $request->Amount;
            $Currency = $request->Currency;
            $TransactionReference = $request->TransactionReference;
            $TransactionDate = $request->TransactionDate;
            $TransactionTime = $request->TransactionTime;
            $TransactionType = $request->TransactionType;
            $ServiceCode = $request->ServiceCode;
            $Fee = $request->Fee;
            $PostingType = $request->PostingType;
            $DestinationAccountName = $request->AdditionalDetails['DestinationAccountName'];
            $DestinationAccountNumber = $request->AdditionalDetails['DestinationAccountNumber'];
            $DestinationBankName = $request->AdditionalDetails['DestinationBankName'];

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function wallet_check(Request $request)
    {

        try {

            $serial_number = $request->serial_number;
            $pin = $request->pin;
            $transaction_type = "inward";

            $status = User::where('serial_no', $serial_number)
                ->first()->is_active;

            $balance = User::where('serial_no', $serial_number)
                ->first()->main_wallet;

            $get_pin = User::where('serial_no', $serial_number)
                ->first()->pin;

            if ($status == 1) {
                $agent_status = "Active";
            } else {
                $agent_status = "InActive";

            }

            if (Hash::check($pin, $get_pin)) {
                $is_pin_valid = true;
            } else {
                $is_pin_valid = false;
            }

            return response()->json([

                'status' => true,
                'is_pin_valid' => $is_pin_valid,
                'balance' => number_format($balance, 2),
                'agent_status' => $agent_status,

            ]);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function pool_account()
    {

        try {

            $api = errand_api_key();

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/ApiGetBalance',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'epKey: ep_live_jFrIZdxqSzAdraLqbvhUfVYs',
                    "Authorization: Bearer $api",
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);

            $var = json_decode($var);

            $code = $var->code ?? null;

            if ($code == null) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => "Network Issue, Please try again later",

                ]);

            }

            if ($var->code == 200) {

                return response()->json([

                    'status' => true,
                    'balance' => number_format($var->data->balance, 2),
                    'account_number' => $var->data->accountNumber,

                ]);
            }

        } catch (\Exception$th) {
            return $th->getMessage();
        }
    }

    public function get_all_transactions(Request $request)
    {

        try {

            $all_transactions = Transaction::where('user_id', Auth::id())
                ->get();

            return response()->json([

                'status' => $this->success,
                'data' => $all_transactions,

            ]);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }



    public function get_token(Request $request)
    {

        try {

            $token = errand_api_key();

            return response()->json([

                'status' => $this->success,
                'data' => $token,

            ]);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }
























}
