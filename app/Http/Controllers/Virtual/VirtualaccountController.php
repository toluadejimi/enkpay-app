<?php

namespace App\Http\Controllers\Virtual;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Transaction;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mail;

class VirtualaccountController extends Controller
{

    public $success = true;
    public $failed = false;

    public function create_account(request $request)
    {

        try {

            // $errand_key = errand_api_key();
            //$errand_user_id = errand_id();
            $bvn = user_bvn() ?? null;
            $phone = Auth::user()->phone;
            $name = first_name() . " " . last_name();

            if (user_status() == 0) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'User has neem restricted on ENKPAY',
                ], 500);

            }

            if (user_status() == 1) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please complete your KYC',
                ], 500);

            }

            if (Auth::user()->v_account_number !== null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'You already own account number',
                ], 500);

            }

            if ($bvn == null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'BVN not verified, Kindly update your BVN',
                ], 500);

            }

            $curl = curl_init();
            $data = array(

                // "user_id" => $errand_user_id,
                "customerBvn" => $bvn,
                "phoneNumber" => $phone,
                "customerName" => $name,

            );

            // $databody = json_encode($data);

            // curl_setopt_array($curl, array(
            //     CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/CreateVirtualAccount',
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => '',
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 0,
            //     CURLOPT_FOLLOWLOCATION => true,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => 'POST',
            //     CURLOPT_POSTFIELDS => $databody,
            //     CURLOPT_HTTPHEADER => array(
            //         'Content-Type: application/json',
            //         'Accept: application/json',
            //         "Authorization: Bearer $errand_key",
            //     ),
            // ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            $status = 200; //$var->code ?? null;
            $acct_no = "0006363535353"; //$var->data->accountNumber ?? null;
            $acct_name = "Adejimi"; //$var->data->accountName ?? null;

            $bank = "VFD MICROFINANCE BANK";

            $data1 = array([
                'acct_no' => $acct_no,
                'acct_name' => $acct_name,
                'bank' => $bank
            ]);

            $data2 = (object) $data1[0];


            if ($status == 200) {

                $update = User::where('id', Auth::id())
                    ->update([
                        'v_account_no' => $acct_no,
                        'v_account_name' => $acct_name,
                    ]);

                return response()->json([

                    'status' => $this->success,
                    'data' => $data2,

                ], 200);

            }

            return response()->json([

                'status' => $this->failed,
                'data' => $var->error->message,

            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function get_created_account()
    {

        try {

            $errand_key = errand_api_key();

            $b_code = env('BCODE');

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.errandpay.com/epagentservice/api/v1/GetSubAccounts?businessCode=$b_code",
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

            if ($var->code == 200) {

                return response()->json([

                    'status' => $this->success,
                    'data' => $var->data,

                ], 200);

            }

            return response()->json([

                'status' => $this->failed,
                'data' => $var->error->message,

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
            $sender_account_no = $request->originatorAccountNumber;
            $sender_name = $request->originatorAccountName;
            $sender_bank = $request->originatorBank;

            $key = env('ERIP');

            $deposit_charges = Charge::where('id', 2)->first()->amount;

            $trans_id = "ENK-" . random_int(100000, 999999);
            $verify1 = hash('sha512', $key);

            if ($verify1 == $header) {

                if ($StatusCode == 00) {

                    $deposit_charges = Charge::where('id', 2)->first()->amount;

                    $main_wallet = User::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->main_wallet ?? null;

                    $user_id = User::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->id ?? null;

                    $user_email = User::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->email ?? null;

                    $first_name = User::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->first_name ?? null;

                    $last_name = User::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->last_name ?? null;

                    $check_status = User::where('v_account_no', $VirtualCustomerAccount)->first()->status ?? null;

                    if ($main_wallet == null && $user_id == null) {

                        return response()->json([
                            'status' => false,
                            'message' => 'V Account not registred on Enkpay',
                        ], 500);

                    }

                    if ($check_status == 2) {

                        return response()->json([
                            'status' => $this->failed,
                            'message' => 'Account has been Restricted on ENKPAY',
                        ], 500);

                    }

                    //credit
                    $enkpay_debit = $Amount - $deposit_charges;
                    $updated_amount = $main_wallet + $enkpay_debit;
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
                        $trasnaction->type = $TransactionType;
                        $trasnaction->transaction_type = "VirtualFundWallet";
                        $trasnaction->main_type = "Transfer";
                        $trasnaction->credit = $enkpay_debit;
                        $trasnaction->note = "Credit received from Transfer";
                        $trasnaction->fee = $Fee;
                        $trasnaction->e_charges = $deposit_charges;
                        $trasnaction->trx_date = $TransactionDate;
                        $trasnaction->trx_time = $TransactionTime;
                        $trasnaction->sender_name = $sender_name;
                        $trasnaction->sender_bank = $sender_bank;
                        $trasnaction->sender_account_no = $sender_account_no;
                        $trasnaction->balance = $updated_amount;
                        $trasnaction->status = 1;
                        $trasnaction->save();

                    }

                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Virtual Account Credited",
                        'toreceiver' => 'toluadejimi@gmail.com',
                        'amount' => $enkpay_debit,
                        'serial' => $user_id,
                    );

                    Mail::send('emails.transaction.terminal-credit', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });

                    //send to user

                    if ($user_email !== null) {

                        $data = array(
                            'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                            'subject' => "Virtual Account Credited",
                            'toreceiver' => $user_email,
                            'amount' => $Amount,
                            'first_name' => $first_name,
                        );

                        Mail::send('emails.transaction.virtual-credit', ["data1" => $data], function ($message) use ($data) {
                            $message->from($data['fromsender']);
                            $message->to($data['toreceiver']);
                            $message->subject($data['subject']);
                        });
                    }

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

    public function get_virtual_account(request $request)
    {

        try {

            $bank = "VFD MICROFINANCE BANK";

            $get_account = User::select('v_account_no', 'v_account_name')->where('id', Auth::id())
                ->first() ?? null;

            $account = $get_account;
            $account['bank'] = $bank;

            if ($account !== null) {
                return response()->json([

                    'status' => $this->success,
                    'data' => $account,

                ], 200);
            }

            return response()->json([

                'status' => $this->failed,
                'data' => "Contact support to create your bank account",

            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function virtual_acct_history(Request $request)
    {

        try {

            $errand_key = errand_api_key();

            $b_code = env('BCODE');

            $acct_no = $request->acct_no;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.errandpay.com/epagentservice/api/v1/GetSubAccountHistory?businessCode=$b_code&accountNumber=$acct_no&pageNumber=1&pageSize=50",
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

            dd($var, $errand_key, $acct_no);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

}
