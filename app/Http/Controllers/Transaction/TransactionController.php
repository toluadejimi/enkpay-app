<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\ErrandKey;
use App\Models\Transaction;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mail;

class TransactionController extends Controller
{

    public $success = true;
    public $failed = false;

    public function bank_transfer(Request $request)
    {

        try {

            $erran_api_key = errand_api_key();

            $epkey = env('EPKEY');

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

            $transfer_charges = Charge::where('id', 1)->first()->amount;

            $user_email = user_email();
            $first_name = first_name();

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
                    'message' => 'Insufficient Funds, fund your account',

                ], 500);

            }

            //Debit
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

            $curl = curl_init();
            $data = array(

                "amount" => $amount,
                "destinationAccountNumber" => $destinationAccountNumber,
                "destinationBankCode" => $destinationBankCode,
                "destinationAccountName" => $destinationAccountName,
                "longitude" => $longitude,
                "latitude" => $latitude,
                "description" => $description,

            );

            $post_data = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/ApiFundTransfer',
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
                    "EpKey: $epkey",
                    'Content-Type: application/json',
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);

            $var = json_decode($var);

            $message = $var->error->message ?? null;

            $trans_id = "ENK-" . random_int(100000, 999999);

            $TransactionReference = $var->reference ?? null;

            if ($var->code == 200) {

                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = Auth::id();
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $TransactionReference;
                $trasnaction->type = "InterBankTransfer";
                $trasnaction->main_type = "Transfer";
                $trasnaction->transaction_type = "BankTransfer";
                $trasnaction->debit = $amount;
                $trasnaction->note = "Bank Transfer to other banks";
                $trasnaction->fee = 0;
                $trasnaction->e_charges = $transfer_charges;
                $trasnaction->trx_date = date("Y/m/d");
                $trasnaction->trx_time = date("h:i:s");
                $trasnaction->receiver_name = $destinationAccountName;
                $trasnaction->reveiver_account_no = $destinationAccountNumber;
                $trasnaction->balance = $debit;
                $trasnaction->status = 0;
                $trasnaction->save();

                if ($user_email !== null) {

                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Bank Transfer",
                        'toreceiver' => $user_email,
                        'amount' => $amount,
                        'first_name' => $first_name,
                    );

                    Mail::send('emails.transaction.banktransfer', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });
                }

                return response()->json([

                    'status' => $this->success,
                    'reference' => $TransactionReference,
                    'message' => "Transaction Processing",

                ], 200);

            } else {

                //credit
                $credit = $user_wallet_banlance + $amount;

                if ($wallet == 'main_account') {

                    $update = User::where('id', Auth::id())
                        ->update([
                            'main_wallet' => $credit,
                        ]);

                }

                if ($wallet == 'bonus_account') {

                    $update = User::where('id', Auth::id())
                        ->update([
                            'bonus_wallet' => $credit,
                        ]);
                }

                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Error From Transfer",
                    'toreceiver' => "toluadejimi@gmail.com",
                    'message' => $message,

                );

                Mail::send('emails.notify.error', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Service not reachable, please try again later',

                ], 500);

            }

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function get_wallet()
    {

        try {

            $account = select_account();

            return response()->json([

                'status' => $this->success,
                'account' => $account,

            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function transfer_properties()
    {

        try {

            $account = select_account();

            $errand_key = ErrandKey::where('id', 1)->first()->errand_key ?? null;

            $transfer_charge = Charge::where('title', 'transfer_fee')
                ->first()->amount;

            if ($errand_key == null) {
                $response1 = errand_api_key();
                $update = ErrandKey::where('id', 1)
                    ->update([
                        'errand_key' => $response1[0],
                    ]);
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/ApiGetBanks',
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

                    'account' => $account,
                    'transfer_charge' => $transfer_charge,
                    'banks' => $var->data,

                ], 200);

            }

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function resolve_bank(request $request)
    {

        try {

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
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/AccountNameVerification',
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

            if ($var->code == 200) {

                return response()->json([
                    'status' => $this->success,
                    'customer_name' => $customer_name,

                ], 200);

            }

            return response()->json([
                'status' => $this->failed,
                'message' => $error,

            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function resolve_enkpay_account(request $request)
    {

        try {

            $phone = $request->phone;

            $get_phone = User::where('phone', $phone)->first()->phone ?? null;
            $check_user = User::where('id', Auth::id())->first()->phone ?? null;
            $customer_f_name = User::where('phone', $phone)->first()->first_name ?? null;
            $customer_l_name = User::where('phone', $phone)->first()->last_name ?? null;
            $customer_name = $customer_f_name . " " . $customer_l_name;

            if ($get_phone == null) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => "Customer not registred on Enkpay",
                ], 500);
            }

            if ($phone == $check_user) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "You can not send money to yourself",
                ], 500);

            }

            return response()->json([
                'status' => $this->success,
                'customer_name' => $customer_name,
            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function enkpay_transfer(request $request)
    {

        try {

            $phone = $request->phone;
            $amount = $request->amount;
            $wallet = $request->wallet;
            $pin = $request->pin;

            //receiver info
            $receiver_main_wallet = User::where('phone', $phone)->first()->main_wallet ?? null;
            $receiver_bonus_wallet = User::where('phone', $phone)->first()->bonus_wallet ?? null;
            $receiver_id = User::where('phone', $phone)->first()->id ?? null;
            $receiver_email = User::where('phone', $phone)->first()->email ?? null;
            $receiver_f_name = User::where('phone', $phone)->first()->first_name ?? null;
            $receiver_l_name = User::where('phone', $phone)->first()->first_name ?? null;
            $receiver_full_name = $receiver_f_name . " " . $receiver_l_name;

            //sender info
            $sender_f_name = first_name() ?? null;
            $sender_l_name = last_name() ?? null;
            $sender_full_name = $sender_f_name . " " . $sender_l_name;

            $trans_id = "ENK-" . random_int(100000, 999999);

            //check

            if ($phone == user_phone()) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "You can not send money to yourself",
                ], 500);

            }

            //Debit Transaction

            if ($wallet == 'main_account') {
                $sender_balance = main_account();
            } else {
                $sender_balance = bonus_account();
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

            if ($amount > $sender_balance) {

                if (!empty(user_email())) {
                    // $data = array(
                    //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    //     'subject' => "Low Balance",
                    //     'toreceiver' => user_email(),
                    //     'first_name' => first_name(),
                    //     'amount' => $amount,
                    //     'balance' => $sender_balance,

                    // );

                    // Mail::send('emails.notify.lowbalalce', ["data1" => $data], function ($message) use ($data) {
                    //     $message->from($data['fromsender']);
                    //     $message->to($data['toreceiver']);
                    //     $message->subject($data['subject']);
                    // });
                }

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, fund your account',

                ], 500);

            }

            //Debit Sender

            $debit = $sender_balance - $amount;

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

            //save debit for sender
            $trasnaction = new Transaction();
            $trasnaction->user_id = Auth::id();
            $trasnaction->from_user_id = Auth::id();
            $trasnaction->to_user_id = $receiver_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->transaction_type = "EnkPayTransfer";
            $trasnaction->type = "InAppTransfer";
            $trasnaction->main_type = "Transfer";
            $trasnaction->debit = $amount;
            $trasnaction->note = "Bank Transfer to Enk Pay User";
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->trx_date = date("Y/m/d");
            $trasnaction->trx_time = date("h:i:s");
            $trasnaction->receiver_name = $receiver_full_name;
            $trasnaction->reveiver_account_no = $phone;
            $trasnaction->balance = $debit;
            $trasnaction->status = 1;
            $trasnaction->save();

            //credit receiver

            $credit = $receiver_main_wallet + $amount;

            $update = User::where('phone', $phone)
                ->update([
                    'main_wallet' => $credit,
                ]);

            //save credit for receiver
            $trasnaction = new Transaction();
            $trasnaction->user_id = $receiver_id;
            $trasnaction->from_user_id = Auth::id();
            $trasnaction->to_user_id = $receiver_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->transaction_type = "EnkPayTransfer";
            $trasnaction->main_type = "Transfer";
            $trasnaction->type = "InAppTransfer";
            $trasnaction->credit = $amount;
            $trasnaction->note = "Bank Transfer to Enk Pay User";
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->trx_date = date("Y/m/d");
            $trasnaction->trx_time = date("h:i:s");
            $trasnaction->sender_name = $sender_full_name;
            $trasnaction->sender_account_no = user_phone();
            $trasnaction->balance = $credit;
            $trasnaction->status = 1;
            $trasnaction->save();

            //sender email

            if (!empty(user_email())) {

                // $data = array(
                //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                //     'subject' => "Debit Notification",
                //     'toreceiver' => user_email(),
                //     'first_name' => first_name(),
                //     'amount' => $amount,

                // );

                // Mail::send('emails.transaction.sender', ["data1" => $data], function ($message) use ($data) {
                //     $message->from($data['fromsender']);
                //     $message->to($data['toreceiver']);
                //     $message->subject($data['subject']);
                // });

            }

            //receiver email

            if (!empty($receiver_email)) {

                // $data = array(
                //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                //     'subject' => "Credit Notification",
                //     'toreceiver' => $receiver_email,
                //     'first_name' => $receiver_f_name,
                //     'amount' => $amount,

                // );

                // Mail::send('emails.transaction.receiver', ["data1" => $data], function ($message) use ($data) {
                //     $message->from($data['fromsender']);
                //     $message->to($data['toreceiver']);
                //     $message->subject($data['subject']);
                // });

                return response()->json([

                    'status' => $this->success,
                    'message' => 'Transfer Successful',

                ], 200);
            }

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function verify_pin(request $request)
    {

        try {

            $pin = $request->pin;

            $get_pin = User::where('id', Auth::id())
                ->first()->pin;

            if (Hash::check($pin, $get_pin)) {
                return response()->json([
                    'status' => $this->success,
                    'data' => "Pin Verified",
                ], 200);

            } else {
                return response()->json([
                    'status' => $this->failed,
                    'message' => "Invalid pin please try again",
                ], 500);
            }

        } catch (\Exception$th) {
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

        $comission = Charge::where('id', 3)
            ->first()->amount;

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

                //Both Commission
                $amount1 = $comission / 100;
                $amount2 = $amount1 * $Amount;
                $amount = number_format($amount2, 3);


                //enkpay commission
                $commison_subtract = $comission - 0.425;
                $enkPayPaypercent =  $commison_subtract / 100;
                $enkPay_amount =  $enkPayPaypercent * $Amount;
                $enkpay_commision_amount = number_format($enkPay_amount, 3);




                //errandpay commission
                $errandPaypercent =  0.425 / 100;
                $errand_amount =  $errandPaypercent * $Amount;
                $errandPay_commission_amount = number_format($errand_amount, 3);



                $enkpay_cashOut_fee = $amount -$enkpay_commision_amount ;


                $removed_comission = $Amount - $amount;
                $updated_amount = $main_wallet + $removed_comission;

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
                    $trasnaction->credit = $removed_comission;
                    $trasnaction->e_charges = $amount;
                    $trasnaction->note = "Credit received from POS Terminal";
                    $trasnaction->fee = $Fee;
                    $trasnaction->enkPay_Cashout_profit = $enkpay_commision_amount;
                    $trasnaction->balance = $updated_amount;
                    $trasnaction->terminal_id = $TerminalID;
                    $trasnaction->serial_no = $SerialNumber;
                    $trasnaction->status = 1;
                    $trasnaction->save();

                }


                $amount4 = number_format($amount);
                $message = "NGN $amount4 enter pool Account by $user_id using Card on Terminal";
                send_notification($message);


                // $data = array(
                //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                //     'subject' => "Account Credited",
                //     'toreceiver' => 'toluadejimi@gmail.com',
                //     'amount' => $removed_comission,
                //     'serial' => $SerialNumber,
                // );

                // Mail::send('emails.transaction.terminal-credit', ["data1" => $data], function ($message) use ($data) {
                //     $message->from($data['fromsender']);
                //     $message->to($data['toreceiver']);
                //     $message->subject($data['subject']);
                // });

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

            //$IP = $_SERVER['SERVER_ADDR'];

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

            if ($transaction_type == 'outward' && $serviceCode == 'FT1') {

                $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;

                $main_balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;

                $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;

                if ($status == 1) {
                    $check_agent_status = "Active";
                } else {
                    $check_agent_status = "InActive";
                }

                if (Hash::check($pin, $get_pin)) {
                    $user_pin = 1;
                } else {
                    $user_pin = 0;
                }

                //check balance
                $user_balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;

                if ($user_balance >= $amount) {
                    $processTransaction1 = true;
                } else {
                    $processTransaction1 = false;
                }

                if ($user_pin == 1) {
                    $processTransaction2 = true;
                } else {
                    $processTransaction2 = false;
                }

                if ($processTransaction1 == true && $processTransaction2 == true) {

                    $user_balance = User::where('serial_no', $serial_number)
                        ->first()->main_wallet;

                    $debit = $user_balance - $amount;

                    $update_balance = User::where('serial_no', $serial_number)
                        ->update([
                            'main_wallet' => $debit,
                        ]);

                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $user_id;
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $reference;
                    $trasnaction->transaction_type = "TerminalBankTransfer";
                    $trasnaction->type = $transaction_type;
                    $trasnaction->debit = $amount;
                    $trasnaction->main_type = 'Transfer';
                    $trasnaction->balance = $debit;
                    $trasnaction->e_charges = 25;
                    $trasnaction->serial_no = $serial_number;
                    $trasnaction->status = 1;
                    $trasnaction->save();

                    $amount4 = number_format($amount, 2);
                    $message = "NGN $amount4 left pool Account by $user_id using Transfer";
                    send_notification($message);

                    return response()->json([

                        'is_pin_valid' => true,
                        'balance' => number_format($debit, 2),
                        'agent_status' => "Active",

                    ]);

                } else {

                    return response()->json([

                        'is_pin_valid' => true,
                        'balance' => number_format($user_balance, 2),
                        'agent_status' => "Active",

                    ]);

                }

            }

            if ($transaction_type == 'outward' && $serviceCode == 'BAT1') {

                $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;

                $main_balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;

                $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;

                if ($status == 1) {
                    $check_agent_status = "Active";
                } else {
                    $check_agent_status = "InActive";
                }

                if (Hash::check($pin, $get_pin)) {
                    $user_pin = 1;
                } else {
                    $user_pin = 0;
                }

                //check balance
                $user_balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;

                if ($user_balance >= $amount) {
                    $processTransaction1 = true;
                } else {
                    $processTransaction1 = false;
                }

                if ($user_pin == 1) {
                    $processTransaction2 = true;
                } else {
                    $processTransaction2 = false;
                }

                if ($processTransaction1 == true && $processTransaction2 == true) {

                    $user_balance = User::where('serial_no', $serial_number)
                        ->first()->main_wallet;

                    $debit = $user_balance - $amount;

                    $update_balance = User::where('serial_no', $serial_number)
                        ->update([
                            'main_wallet' => $debit,
                        ]);

                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $user_id;
                    $trasnaction->ref_trans_id =$trans_id;
                    $trasnaction->e_ref = $reference;
                    $trasnaction->transaction_type = "VASfromTerminal";
                    $trasnaction->type = $transaction_type;
                    $trasnaction->debit = $amount;
                    $trasnaction->balance = $debit;
                    $trasnaction->e_charges = 0;
                    $trasnaction->serial_no = $serial_number;
                    $trasnaction->status = 1;
                    $trasnaction->save();

                    $amount4 = number_format($amount);
                    $message = "NGN $amount4 left pool Account by $user_id using VAS";
                    send_notification($message);

                    return response()->json([

                        'is_pin_valid' => true,
                        'balance' => number_format($debit, 2),
                        'agent_status' => "Active",

                    ]);

                } else {

                    return response()->json([

                        'is_pin_valid' => true,
                        'balance' => number_format($user_balance, 2),
                        'agent_status' => "Active",

                    ]);

                }

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

            $errand_key = errand_api_key();

            $b_code = env('BCODE');

            $ref_no = $request->ref_no;

            $e_ref = Transaction::where('ref_trans_id', $ref_no)
            ->first()->e_ref;

            $url = "https://api.errandpay.com/epagentservice/api/v1/GetStatus?reference=$e_ref&businessCode=$b_code";


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.errandpay.com/epagentservice/api/v1/GetStatus?reference=$e_ref&businessCode=$b_code",
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


            $ms = $var->error->message ?? null;

            if($var->code == 200){

                $update = Transaction::where('ref_trans_id', $ref_no)
                ->update(['status' => 1]);

                return response()->json([

                    'status' => true,
                    'message' => "Transaction Successful"

                ], 200);

            }

            return response()->json([

                'status' => false,
                'message' => $ms

            ], 500);

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

            $all_transactions = Transaction::latest()->where('user_id', Auth::id())
                ->get();

            return response()->json([

                'status' => $this->success,
                'data' => $all_transactions,

            ], 200);

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

    public function pos(Request $request)
    {

        try {

            $pos_trasnactions = Transaction::latest()
                ->where([
                    'user_id' => Auth::id(),
                    'transaction_type' => 'CashOut',
                ])->take(10)->get();

            return response()->json([

                'status' => $this->success,
                'data' => $pos_trasnactions,

            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }
    }

    public function transfer(Request $request)
    {

        try {

            $transfer_trasnactions = Transaction::orderBy("id", "DESC")
                ->where([
                    'user_id' => Auth::id(),
                    'main_type' => 'Transfer',
                ])
                ->take(20)->get();

            return response()->json([

                'status' => $this->success,
                'data' => $transfer_trasnactions,

            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }
    }

    public function vas(Request $request)
    {

        try {

            $transfer_trasnactions = Transaction::orderBy("id", "DESC")
                ->where([
                    'user_id' => Auth::id(),
                    'type' => 'vas',
                ])
                ->take(20)->get();

            return response()->json([

                'status' => $this->success,
                'data' => $transfer_trasnactions,

            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }
    }

}
