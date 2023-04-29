<?php

namespace App\Http\Controllers\Virtual;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Transaction;
use App\Models\User;
use Auth;
use DateTimeZone;
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

            $errand_key = errand_api_key();
            $errand_user_id = errand_id();
            $bvn = user_bvn() ?? null;

            if (Auth::user()->b_name == null) {
                $name = first_name() . " " . last_name();
            } else {
                $name = Auth::user()->b_name;
            }

            if (Auth::user()->b_phone == null) {
                $phone = Auth::user()->phone;
            } else {
                $phone = Auth::user()->b_phone;
            }

            if (user_status() == 0) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'User has been restricted on ENKPAY',
                ], 500);

            }

            if (user_status() == 1) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please complete your KYC',
                ], 500);

            }

            if (user_bvn() == null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'We need your BVN to generate an account for you',
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

                "userId" => $errand_user_id,
                "customerBvn" => $bvn,
                "phoneNumber" => $phone,
                "customerName" => $name,

            );

            $databody = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/CreateVirtualAccount',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $databody,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: Bearer $errand_key",
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            $status = $var->code ?? null;
            $acct_no = $var->data->accountNumber ?? null;
            $acct_name = $var->data->accountName ?? null;

            $bank = "VFD MICROFINANCE BANK";

            $data1 = array([
                'acct_no' => $acct_no,
                'acct_name' => $acct_name,
                'bank' => $bank,
            ]);

            $data2 = (object) $data1[0];

            if ($status == 200) {

                $user = User::find(Auth::id());
                $user->v_account_no = $acct_no;
                $user->v_account_name = $acct_name;
                $user->save();
            } else {

                return response()->json([

                    'status' => $this->failed,
                    'message' => $var->error->message,

                ], 500);

            }

            $curl = curl_init();
            $data = array(
                "account_name" => $name,
                "bvn" => $bvn,
            );

            $databody = json_encode($data);
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://154.113.16.142:8088/appdevapi/api/PiPCreateReservedAccountNumber',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $databody,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Client-Id: dGVzdF9Qcm92aWR1cw==',
                    'X-Auth-Signature: BE09BEE831CF262226B426E39BD1092AF84DC63076D4174FAC78A2261F9A3D6E59744983B8326B69CDF2963FE314DFC89635CFA37A40596508DD6EAAB09402C7',
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            $status = $var->responseCode ?? null;
            $p_acct_no = $var->account_number ?? null;
            $p_acct_name = $var->account_name ?? null;

            $bank = "PROVIDUS BANK";

            if ($status == 00) {

                $user = User::find(Auth::id());
                $user->p_account_no = $p_acct_no;
                $user->p_account_name = $p_acct_name;
                $user->save();

                $get_user = User::find(Auth::id())->first();
                return response()->json([

                    'status' => $this->success,
                    'message' => "Your account has been created successfully",
                    'data' => $get_user,

                ], 200);

            } else {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Error please try again after some time',

                ], 500);

            }

        } catch (\Exception $th) {
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

        } catch (\Exception $th) {
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
            $sender_account_no = $request->OriginatorAccountNumber;
            $sender_name = $request->OriginatorAccountName;
            $sender_bank = $request->OriginatorBank;

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

                    if ($check_status == 3) {

                        return response()->json([
                            'status' => $this->failed,
                            'message' => 'Account has been Restricted on ENKPAY',
                        ], 500);

                    }

                    $enkpay_profit = $deposit_charges - 10;

                    $message_amount = $Amount - 10;

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
                        $trasnaction->title = "Wallet Funding";
                        $trasnaction->main_type = "Transfer";
                        $trasnaction->credit = $enkpay_debit;
                        $trasnaction->note = "$sender_name | Wallet Funding";
                        $trasnaction->fee = $Fee;
                        $trasnaction->amount = $Amount;
                        $trasnaction->e_charges = $deposit_charges;
                        $trasnaction->enkPay_Cashout_profit = $enkpay_profit;
                        $trasnaction->trx_date = $TransactionDate;
                        $trasnaction->trx_time = $TransactionTime;
                        $trasnaction->sender_name = $sender_name;
                        $trasnaction->sender_bank = $sender_bank;
                        $trasnaction->sender_account_no = $sender_account_no;
                        $trasnaction->balance = $updated_amount;
                        $trasnaction->status = 1;
                        $trasnaction->save();

                        $errand_key = errand_api_key();

                        $b_code = env('BCODE');

                        $acct_no = $request->acct_no;

                        $curl = curl_init();

                        $datetime = new \DateTime ("now", new DateTimeZone("Europe/Bucharest"));

                        $date1 = $datetime->format('Y-m-d');
                        $date2 = $datetime->format('H:i:s');

                        $data = array(

                            "Amount" => $Amount,
                            "DateOfTransaction" => $date1 . "T" . $date2 . "+" . "01:00",
                            "SenderAccountNumber" => $sender_account_no,
                            "SenderAccountName" => $sender_name,
                            "OriginatorBank" => $sender_bank,
                            "RecipientAccountNumber" => $VirtualCustomerAccount,
                            "RecipientAccountName" => $first_name . " " . $last_name,

                        );

                        $post_data = json_encode($data);

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/Webhook/Notify',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => $post_data,
                            CURLOPT_HTTPHEADER => array(
                                'epKey: ep_live_jFrIZdxqSzAdraLqbvhUfVYs',
                                'Content-Type: application/json',
                            ),
                        ));

                        $var = curl_exec($curl);
                        curl_close($curl);
                        $var = json_decode($var);

                    }

                    $message = "Your Pool account has been credited |  $message_amount | from Virtual account";

                    send_notification($message);

                    //send to user

                    if ($user_email !== null) {

                        $data = array(
                            'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                            'subject' => "Virtual Account Credited",
                            'toreceiver' => $user_email,
                            'amount' => $enkpay_debit,
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

        } catch (\Exception $th) {
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

        } catch (\Exception $th) {
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

        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }

    //PROVIDUS VIRTUAL ACCOUNT

    public function providusCashIn(request $request)
    {

        try {

            $header = $request->header('X-Auth-Signature');

            $sessionId = $request->sessionId;
            $accountNumber = $request->accountNumber;
            $tranRemarks = $request->tranRemarks;
            $settledAmount = $request->settledAmount;
            $transactionAmount = $request->transactionAmount;
            $feeAmount = $request->feeAmount;
            $TransactionTime = $request->TransactionTime;
            $initiationTranRef = $request->initiationTranRef;
            $settlementId = $request->settlementId;
            $sourceAccountNumber = $request->sourceAccountNumber;
            $PostingType = $request->PostingType;
            $TransactionReference = $request->TransactionReference;
            $sourceAccountName = $request->sourceAccountName;
            $sourceBankName = $request->sourceBankName;
            $channelId = $request->channelId;
            $tranDateTime = $request->tranDateTime;

            $key = env('POKEY');

            $deposit_charges = Charge::where('id', 2)->first()->amount;

            $trans_id = "ENK-" . random_int(100000, 999999);

            $verify1 = hash('sha512', $key);

            $verify2 = strtoupper($verify1);

            if ($verify2 == $header) {

                $deposit_charges = Charge::where('id', 2)->first()->amount;

                $main_wallet = User::where('p_account_no', $accountNumber)
                    ->first()->main_wallet ?? null;

                $user_id = User::where('p_account_no', $accountNumber)
                    ->first()->id ?? null;

                $user_email = User::where('p_account_no', $accountNumber)
                    ->first()->email ?? null;

                $first_name = User::where('p_account_no', $accountNumber)
                    ->first()->first_name ?? null;

                $last_name = User::where('p_account_no', $accountNumber)
                    ->first()->last_name ?? null;

                $check_status = User::where('p_account_no', $accountNumber)->first()->status ?? null;

                $VirtualCustomerAccount = User::where('p_account_no', $accountNumber)->first()->v_account_no ?? null;

                $get_session = Transaction::where('e_ref', $settlementId)->first()->e_ref ?? null;

                if ($main_wallet == null && $user_id == null) {

                    return response()->json([
                        'status' => false,
                        'message' => 'V Account not registred on Enkpay',
                    ], 500);

                }

                if ($get_session == $settlementId) {

                    return response()->json([
                        'requestSuccessful' => true,
                        'sessionId' => $sessionId,
                        'responseMessage' => 'duplicate transaction',
                        'responseCode' => "01",
                    ], 200);

                }

                if ($check_status == 3) {

                    return response()->json([
                        'status' => $this->failed,
                        'message' => 'Account has been Restricted on ENKPAY',
                    ], 500);

                }

                // $enkpay_profit = $deposit_charges - 10;

                // $message_amount = $Amount - 10;

                //credit
                // $enkpay_debit = $Amount - $deposit_charges;
                $updated_amount = $main_wallet + $settledAmount;
                $main_wallet = User::where('p_account_no', $accountNumber)
                    ->update([
                        'main_wallet' => $updated_amount,
                    ]);

                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $settlementId;
                $trasnaction->type = "PROVIDUS FUNDING";
                $trasnaction->transaction_type = "VirtualFundWallet";
                $trasnaction->title = "Wallet Funding";
                $trasnaction->main_type = "Transfer";
                $trasnaction->credit = $settledAmount;
                $trasnaction->note = "$sourceAccountName | Wallet Funding";
                $trasnaction->fee = $feeAmount;
                $trasnaction->amount = $transactionAmount;
                $trasnaction->e_charges = $deposit_charges;
                $trasnaction->enkPay_Cashout_profit = 0;
                $trasnaction->trx_date = $tranDateTime;
                $trasnaction->p_sessionId = $sessionId;
                $trasnaction->trx_time = $tranDateTime;
                $trasnaction->sender_name = $sourceAccountName;
                $trasnaction->sender_bank = $sourceBankName;
                $trasnaction->sender_account_no = $sourceAccountNumber;
                $trasnaction->balance = $updated_amount;
                $trasnaction->status = 1;
                $trasnaction->save();

                $errand_key = errand_api_key();

                $b_code = env('BCODE');

                $acct_no = $request->acct_no;

                $curl = curl_init();

                $datetime = new \DateTime ("now", new DateTimeZone("Europe/Bucharest"));

                $date1 = $datetime->format('Y-m-d');
                $date2 = $datetime->format('H:i:s');

                $data = array(

                    "Amount" => $transactionAmount,
                    "DateOfTransaction" => $date1 . "T" . $date2 . "+" . "01:00",
                    "SenderAccountNumber" => $sourceAccountNumber,
                    "SenderAccountName" => $sourceAccountName,
                    "OriginatorBank" => $sourceBankName,
                    "RecipientAccountNumber" => $VirtualCustomerAccount,
                    "RecipientAccountName" => $first_name . " " . $last_name,

                );

                $post_data = json_encode($data);

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/Webhook/Notify',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $post_data,
                    CURLOPT_HTTPHEADER => array(
                        'epKey: ep_live_jFrIZdxqSzAdraLqbvhUfVYs',
                        'Content-Type: application/json',
                    ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);
                $var = json_decode($var);

                $message = "Your Pool account has been credited |  $transactionAmount | from Virtual account";

                send_notification($message);

                //send to user

                if ($user_email !== null) {

                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Virtual Account Credited",
                        'toreceiver' => $user_email,
                        'amount' => $transactionAmount,
                        'first_name' => $first_name,
                    );

                    Mail::send('emails.transaction.virtual-credit', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });
                }

                return response()->json([
                    'requestSuccessful' => true,
                    'sessionId' => $sessionId,
                    'responseMessage' => 'success',
                    'responseCode' => "00",
                ], 200);

            }

            $parametersJson = json_encode($request->all());
            $headers = json_encode($request->headers->all());
            $message = 'Key not Authorized';
            $ip = $request->ip();

            $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
            send_notification($result);


            return response()->json([
                'requestSuccessful' => true,
                'sessionId' => $sessionId,
                'responseMessage' => 'Key not authorized',
                'responseCode' => "02",
            ], 200);

        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }

}
