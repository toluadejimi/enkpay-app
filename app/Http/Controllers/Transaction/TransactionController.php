<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\EmailSend;
use App\Models\ErrandKey;
use App\Models\FailedTransaction;
use App\Models\Feature;
use App\Models\PendingTransaction;
use App\Models\Setting;
use App\Models\Terminal;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Ttmfb;
use App\Models\User;
use App\Models\VfdBank;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Ladumor\OneSignal\OneSignal;
use Mail;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Response;
use App\Events\NewMessage;
use App\Notifications\DepositAlert;
use App\Notifications\SampleNotification;
use Illuminate\Support\Facades\Notification;



class TransactionController extends Controller
{

    public $success = true;
    public $failed = false;

    public function bank_transfer(Request $request)
    {

        try {


            $pos_trx = Feature::where('id', 1)->first()->pos_transfer ?? null;
            if ($pos_trx == 0) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Transfer is not available at the moment, \n\n Please try again after some time",
                ], 500);
            }





            $set = Setting::where('id', 1)->first();

            //VFD BANK
            if ($set->bank == 'vfd') {

                $chk = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;

                if ($fa != null) {

                    if ($chk->user_id == Auth::id()) {


                        $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $fa->created_at);
                        $currentTime = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
                        # count difference in minutes
                        $minuteDiff = $anchorTime->diffInMinutes($currentTime);


                        if ($minuteDiff >= 3) {
                            FailedTransaction::where('user_id', Auth::id())->delete();
                        }
                    }
                }


                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa != null) {

                    if ($fa->attempt == 1) {
                        return response()->json([
                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                        ], 500);
                    }
                }

                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa != null) {

                    if ($fa->attempt == 1) {
                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                        ], 500);
                    }
                }


                $wallet = $request->wallet;
                $amount = $request->amount;
                $destinationAccountNumber = $request->account_number;
                $destinationBankCode = $request->code;
                $destinationAccountName = $request->customer_name;
                $longitude = $request->longitude;
                $latitude = $request->latitude;
                $receiver_name = $request->customer_name;
                $get_description = $request->narration;
                $pin = $request->pin;

                $referenceCode = trx();

                $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
                $bank_name = VfdBank::select('bankName')->where('code', $destinationBankCode)->first()->bankName ?? null;
                $amoutCharges = $amount + $transfer_charges;



                // $ckid = PendingTransaction::where('user_id', Auth::id())->first()->user_id ?? null;
                // if ($ckid == Auth::id()) {

                //     $message = Auth::user()->first_name . " " . Auth::user()->last_name . " | has reached this double endpoint";
                //     send_notification($message);

                //     return response()->json([
                //         'status' => $this->failed,
                //         'message' => 'Please wait for some time and try again',

                //     ], 500);
                // }



                if (Auth::user()->status == 5) {


                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You can not make transfer at the moment, Please contact  support',

                    ], 500);
                }

                if (Auth::user()->status != 2) {

                    $message = Auth::user()->first_name . " " . Auth::user()->last_name . " | Unverified Account trying withdraw";
                    send_notification($message);

                    return response()->json([
                        'status' => $this->failed,
                        'message' => 'Please verify your account to enjoy enkpay full service',
                    ], 500);
                }


                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa !== null) {


                    if ($fa->attempt == 1) {
                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait and try again later',

                        ], 500);
                    }
                }


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

                if (Auth::user()->b_number == 6) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You dont have the permission to make transfer',

                    ], 500);
                }

                if ($amount < 100) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Amount must not be less than NGN 100',

                    ], 500);
                }


                if ($amount > 250000) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You can not transfer more than NGN 250,000.00 at a time',

                    ], 500);
                }

                if (Auth()->user()->status == 1 && $amount > 20000) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Please Complete your KYC',

                    ], 500);
                }



                if ($wallet == 'main_account') {


                    if ($amoutCharges > Auth::user()->main_wallet) {

                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Insufficient Funds, fund your main wallet',

                        ], 500);
                    }
                } else {

                    if ($amoutCharges > Auth::user()->bonus_wallet) {

                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Insufficient Funds, fund your main wallet',

                        ], 500);
                    }
                }

                if ($amoutCharges > $user_wallet_banlance) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Insufficient Funds, fund your account',

                    ], 500);
                }





                $status = 200;
                $message = "Error from Errand Pay";
                $enkpay_profit = $transfer_charges - 10;
                $trans_id = trx();

                if ($status == 200) {

                    //Debit
                    $debited_amount = $transfer_charges + $amount;

                    if ($wallet == 'main_account') {

                        User::where('id', Auth::id())->decrement('main_wallet', $debited_amount);
                    } else {
                        User::where('id', Auth::id())->decrement('bonus_wallet', $debited_amount);
                    }


                    $balance = User::where('id', Auth::id())->first()->main_wallet;
                    $user_balance =  $balance - $debited_amount;

                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->type = "InterBankTransfer";
                    $trasnaction->main_type = "Transfer";
                    $trasnaction->transaction_type = "BankTransfer";
                    $trasnaction->title = "Bank Transfer";
                    $trasnaction->debit = $amoutCharges;
                    $trasnaction->amount = $amount;
                    $trasnaction->note = "BANK TRANSFER TO | $receiver_name | $destinationAccountNumber | $bank_name  ";
                    $trasnaction->fee = 0;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $destinationAccountName;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->balance = $balance;
                    $trasnaction->status = 0;
                    $trasnaction->save();




                    //update Transactions
                    $trasnaction = new PendingTransaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->debit = $amoutCharges;
                    $trasnaction->amount = $amount;
                    $trasnaction->bank_code = $destinationBankCode;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $destinationAccountName;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->receiver_name = $balance;
                    $trasnaction->status = 0;
                    $trasnaction->save();


                    $curl = curl_init();
                    $data = array(

                    "user_id" => Auth::id(),
                    "ref_trans_id" => $trans_id,
                    "debit" => $amoutCharges,
                    "amount" => $amount,
                    "bank_code" => $destinationBankCode,
                    "receiver_name" => $destinationAccountName,
                    "receiver_account_no" => $destinationAccountNumber,
                    "receiver_name" => $destinationAccountName,
                    "status" => 0,

                    );

                    $post_data = json_encode($data);

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://transfer.bplux.store/api/transfer',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $post_data,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                        ),
                    ));

                    $var = curl_exec($curl);
                    curl_close($curl);
                    $var = json_decode($var);

                    $status = $var->status ?? null;


                    if($status == 200){

                    //Transfers
                    $trasnaction = new Transfer();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->type = "EPBankTransfer";
                    $trasnaction->main_type = "Transfer";
                    $trasnaction->transaction_type = "BankTransfer";
                    $trasnaction->title = "Bank Transfer";
                    $trasnaction->debit = $amoutCharges;
                    $trasnaction->amount = $amount;
                    $trasnaction->note = "BANK TRANSFER TO | $receiver_name | $destinationAccountNumber | $bank_name  ";
                    $trasnaction->bank_code = $destinationBankCode;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $receiver_name;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->receiver_bank = $bank_name;
                    $trasnaction->balance = $balance;
                    $trasnaction->status = 0;
                    $trasnaction->save();



                    $email = new EmailSend();
                    $email->receiver_email = Auth::user()->email;
                    $email->amount = $amount;
                    $email->first_name = $first_name;
                    $email->save();




                    $wallet = Auth::user()->main_wallet;
                    $name = Auth::user()->first_name . " " . Auth::user()->last_name;
                    $ip = $request->ip();
                    $message = $name . "| Request to transfer NGN " . $amount . " | " . $bank_name . " | " . $destinationAccountNumber . " User balance | $user_balance ";
                    $result = "Message========> " . $message . "\n\nIP========> " . $ip;
                    send_notification($result);



                    return response()->json([
                        'status' => $this->success,
                        'message' => "Transaction Processing",

                    ], 200);


                    }



                    if ($wallet == 'main_account') {

                        $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
                        $User_wallet_banlance = User::where('id', Auth::id())->first()->main_wallet;

                        $credit = $User_wallet_banlance + $amount + $transfer_charges;
                        $update = User::where('id', Auth::id())
                            ->update([
                                'main_wallet' => $credit,
                            ]);


                    } else {

                        $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
                        $User_wallet_banlance = User::where('id', Auth::id())->first()->bonus_wallet;

                        $credit = $User_wallet_banlance + $amount + $transfer_charges;
                        $update = User::where('id', Auth::id())
                            ->update([
                                'bonus_wallet' => $credit,
                            ]);
                    }



                    $trasnaction = new Transaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->transaction_type = "Reversal";
                    $trasnaction->debit = 0;
                    $trasnaction->amount = $amount;
                    $trasnaction->serial_no = 0;
                    $trasnaction->title = "Reversal";
                    $trasnaction->note = "Reversal";
                    $trasnaction->fee = 25;
                    $trasnaction->balance = $credit;
                    $trasnaction->main_type = "Reversal";
                    $trasnaction->status = 3;
                    $trasnaction->save();


                    $usr = User::where('id', Auth::id())->first();
                    $message = "Transaction reversed | Our API error";
                    $full_name = $usr->first_name . "  " . $usr->last_name;


                    $result = " Message========> " . $message . "\n\nCustomer Name========> " . $full_name;
                    send_notification($result);


                    return response()->json([
                        'status' => $this->failed,
                        'message' => "Transaction Reversed",

                    ], 500);





                }
            }


            //TTMFB
            if ($set->bank == 'ttmfb') {

                $chk = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;

                if ($fa != null) {

                    if ($chk->user_id == Auth::id()) {


                        $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $fa->created_at);
                        $currentTime = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
                        # count difference in minutes
                        $minuteDiff = $anchorTime->diffInMinutes($currentTime);


                        if ($minuteDiff >= 3) {
                            FailedTransaction::where('user_id', Auth::id())->delete();
                        }
                    }
                }


                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa != null) {

                    if ($fa->attempt == 1) {
                        return response()->json([
                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                        ], 500);
                    }
                }

                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa != null) {

                    if ($fa->attempt == 1) {
                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                        ], 500);
                    }
                }


                $wallet = $request->wallet;
                $amount = $request->amount;
                $destinationAccountNumber = $request->account_number;
                $destinationBankCode = $request->code;
                $destinationAccountName = $request->customer_name;
                $longitude = $request->longitude;
                $latitude = $request->latitude;
                $receiver_name = $request->customer_name;
                $get_description = $request->narration;
                $pin = $request->pin;

                $referenceCode = trx();

                $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
                $bank_name = VfdBank::select('bankName')->where('code', $destinationBankCode)->first()->bankName ?? null;
                $amoutCharges = $amount + $transfer_charges;





                // $ckid = PendingTransaction::where('user_id', Auth::id())->first()->user_id ?? null;
                // if ($ckid == Auth::id()) {

                //     $message = Auth::user()->first_name . " " . Auth::user()->last_name . " | has reached this double endpoint";
                //     send_notification($message);

                //     return response()->json([
                //         'status' => $this->failed,
                //         'message' => 'Please wait for some time and try again',

                //     ], 500);
                // }



                if (Auth::user()->status == 5) {


                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You can not make transfer at the moment, Please contact  support',

                    ], 500);
                }

                if (Auth::user()->status != 2) {

                    $message = Auth::user()->first_name . " " . Auth::user()->last_name . " | Unverified Account trying withdraw";
                    send_notification($message);

                    return response()->json([
                        'status' => $this->failed,
                        'message' => 'Please verify your account to enjoy enkpay full service',
                    ], 500);
                }


                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa !== null) {


                    if ($fa->attempt == 1) {
                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait and try again later',

                        ], 500);
                    }
                }


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

                if (Auth::user()->b_number == 6) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You dont have the permission to make transfer',

                    ], 500);
                }

                if ($amount < 100) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Amount must not be less than NGN 100',

                    ], 500);
                }


                if ($amount > 250000) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You can not transfer more than NGN 250,000.00 at a time',

                    ], 500);
                }

                if (Auth()->user()->status == 1 && $amount > 20000) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Please Complete your KYC',

                    ], 500);
                }



                if ($wallet == 'main_account') {


                    if ($amoutCharges > Auth::user()->main_wallet) {

                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Insufficient Funds, fund your main wallet',

                        ], 500);
                    }
                } else {

                    if ($amoutCharges > Auth::user()->bonus_wallet) {

                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Insufficient Funds, fund your main wallet',

                        ], 500);
                    }
                }

                if ($amoutCharges > $user_wallet_banlance) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Insufficient Funds, fund your account',

                    ], 500);
                }



                $status = 200;
                $enkpay_profit = $transfer_charges - 10;

                $debited_amount = $transfer_charges + $amount;
                //$trans_id = trx();


                $chk_bal = ttmfb_balance() ?? 0;

                if($chk_bal < $debited_amount){

                    $name = Auth::user()->first_name . " " . Auth::user()->last_name;
                    $message = $name . "| Error " . "| insufficient funds ".  number_format($chk_bal, 2);
                    $result = "Message========> " . $message ;
                    send_notification($result);

                    return response()->json([
                        'status' => $this->failed,
                        'message' => "Service not available at the moment, \n please wait and try again later",

                    ], 500);

                }

                if ($status == 200) {

                    $trans_id = guid();
                    //Debit


                    if ($wallet == 'main_account') {

                        User::where('id', Auth::id())->decrement('main_wallet', $debited_amount);
                    } else {
                        User::where('id', Auth::id())->decrement('bonus_wallet', $debited_amount);
                    }


                    $balance = User::where('id', Auth::id())->first()->main_wallet;
                    $user_balance =  $balance - $debited_amount;

                    //update Transactions
                    $trasnaction = new PendingTransaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->debit = $amoutCharges;
                    $trasnaction->amount = $amount;
                    $trasnaction->bank_code = $destinationBankCode;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $destinationAccountName;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->receiver_name = $balance;
                    $trasnaction->status = 0;
                    $trasnaction->save();


                    $username = env('MUSERNAME');
                    $prkey = env('MPRKEY');
                    $sckey = env('MSCKEY');

                    $unixTimeStamp = timestamp();
                    $sha = sha512($unixTimeStamp.$prkey);
                    $authHeader = 'magtipon ' . $username . ':' . base64_encode(hex2bin($sha));



                    $ref = sha512($trans_id.$prkey);

                    $signature = base64_encode(hex2bin($ref));
                    $name = Auth::user()->first_name . " " . Auth::user()->last_name;



                    $databody = array(

                        "Amount" => $amount,
                        "RequestRef" => $trans_id,
                        "CustomerDetails" => array(
                            "Fullname" => "ENKWAVE - ($name)",
                            "MobilePhone" => "",
                            "Email" => ""
                        ),
                        "BeneficiaryDetails" => array(
                            "Fullname" => "$receiver_name",
                            "MobilePhone" => "",
                            "Email" => ""
                        ),
                        "BankDetails" => array(
                            "BankType" => "comm",
                            "BankCode" => $destinationBankCode,
                            "AccountNumber" => $destinationAccountNumber,
                            "AccountType" => "10"
                        ),

                        "Signature" => $signature,
                    );


                $post_data = json_encode($databody);


                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'http://magtipon.buildbankng.com/api/v1/transaction/fundstransfer',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $post_data,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: $authHeader",
                        "Timestamp: $unixTimeStamp",
                        'Content-Type: application/json',
                    ),
                ));

                $var = curl_exec($curl);
                $result = json_decode($var);
                $status = $result->ResponseCode ?? null;
                $e_ref = $result->RemoteRef ?? null;

                curl_close($curl);
                    if($status == 90000){


                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $e_ref;
                    $trasnaction->type = "InterBankTransfer";
                    $trasnaction->main_type = "Transfer";
                    $trasnaction->transaction_type = "BankTransfer";
                    $trasnaction->title = "Bank Transfer";
                    $trasnaction->debit = $amoutCharges;
                    $trasnaction->amount = $amount;
                    $trasnaction->note = "BANK TRANSFER TO | $receiver_name | $destinationAccountNumber  \n $e_ref  ";
                    $trasnaction->fee = 0;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $destinationAccountName;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->balance = $balance;
                    $trasnaction->status = 1;
                    $trasnaction->save();



                    //Transfers
                    $trasnaction = new Transfer();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $e_ref;
                    $trasnaction->type = "TTBankTransfer";
                    $trasnaction->main_type = "Transfer";
                    $trasnaction->transaction_type = "BankTransfer";
                    $trasnaction->title = "Bank Transfer";
                    $trasnaction->debit = $amoutCharges;
                    $trasnaction->amount = $amount;
                    $trasnaction->note = "BANK TRANSFER TO | $receiver_name | $destinationAccountNumber | $e_ref  ";
                    $trasnaction->bank_code = $destinationBankCode;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $receiver_name;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->receiver_bank = $bank_name;
                    $trasnaction->balance = $balance;
                    $trasnaction->status = 1;
                    $trasnaction->save();



                    $email = new EmailSend();
                    $email->receiver_email = Auth::user()->email;
                    $email->amount = $amount;
                    $email->first_name = $first_name;
                    $email->save();




                    $wallet = Auth::user()->main_wallet - $amount;
                    $name = Auth::user()->first_name . " " . Auth::user()->last_name;
                    $ip = $request->ip();
                    $message = $name . "| Transfred " . $amount . " | " . $bank_name . " | " . $destinationAccountNumber . " User balance | ". number_format($user_balance, 2);
                    $result = "Message========> " . $message . "\n\nIP========> " . $ip;
                    send_notification($result);


                    PendingTransaction::where('user_id', Auth::id())->delete() ?? null;

                    User::where('id', Auth::id())->increment('bonus_wallet', 1);


                    return response()->json([
                        'status' => $this->success,
                        'message' => "Transaction Completed \n You earned 1 NGN bonus",

                    ], 200);


                    }



                    if ($wallet == 'main_account') {

                        $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
                        $User_wallet_banlance = User::where('id', Auth::id())->first()->main_wallet;

                        $credit = $User_wallet_banlance + $amount + $transfer_charges;
                        $update = User::where('id', Auth::id())
                            ->update([
                                'main_wallet' => $credit,
                            ]);


                    } else {

                        $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
                        $User_wallet_banlance = User::where('id', Auth::id())->first()->bonus_wallet;

                        $credit = $User_wallet_banlance + $amount + $transfer_charges;
                        $update = User::where('id', Auth::id())
                            ->update([
                                'bonus_wallet' => $credit,
                            ]);
                    }



                    $trasnaction = new Transaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->transaction_type = "Reversal";
                    $trasnaction->debit = 0;
                    $trasnaction->amount = $amount;
                    $trasnaction->serial_no = 0;
                    $trasnaction->title = "Reversal";
                    $trasnaction->note = "Reversal";
                    $trasnaction->fee = 25;
                    $trasnaction->balance = $credit;
                    $trasnaction->main_type = "Reversal";
                    $trasnaction->status = 3;
                    $trasnaction->save();

                    PendingTransaction::where('user_id', Auth::id())->delete() ?? null;


                    $usr = User::where('id', Auth::id())->first();
                    $message = "Transaction reversed | Our API error";
                    $full_name = $usr->first_name . "  " . $usr->last_name;


                    $result = " Message========> " . $message . "\n\nCustomer Name========> " . $full_name;
                    send_notification($result);


                    return response()->json([
                        'status' => $this->failed,
                        'message' => "Transaction Reversed",

                    ], 500);





                }
            }



            //PROVIDUS BANK

            if ($set->bank == 'pbank') {

                $chk = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;

                if ($fa != null) {

                    if ($chk->user_id == Auth::id()) {


                        $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $fa->created_at);
                        $currentTime = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
                        # count difference in minutes
                        $minuteDiff = $anchorTime->diffInMinutes($currentTime);


                        if ($minuteDiff >= 3) {
                            FailedTransaction::where('user_id', Auth::id())->delete();
                        }
                    }
                }


                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa != null) {

                    if ($fa->attempt == 1) {
                        return response()->json([
                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                        ], 500);
                    }
                }

                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa != null) {

                    if ($fa->attempt == 1) {
                        return response()->json([
                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                        ], 500);
                    }
                }



                $erran_api_key = errand_api_key();

                $epkey = env('EPKEY');

                $wallet = $request->wallet;
                $amount = $request->amount;
                $destinationAccountNumber = $request->account_number;
                $destinationBankCode = $request->code;
                $destinationAccountName = $request->receiver_bank;
                $longitude = $request->longitude;
                $latitude = $request->latitude;
                $get_description = $request->narration;
                $pin = $request->pin;

                $referenceCode = trx();

                $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;

                $amoutCharges = $amount + $transfer_charges;


                if (Auth::user()->status == 7) {


                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You can not make transfer at the moment, Please contact  support',

                    ], 500);
                }


                $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
                if ($fa !== null) {


                    if ($fa->attempt == 1) {
                        return response()->json([

                            'status' => $this->failed,
                            'message' => 'Service not available at the moment, please wait and try again later',

                        ], 500);
                    }
                }


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

                if (Auth::user()->b_number == 6) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You dont have the permission to make transfer',

                    ], 500);
                }

                if ($amount < 100) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Amount must not be less than NGN 100',

                    ], 500);
                }

                if (Auth()->user()->status == 1 && $amount > 20000) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Please Complete your KYC',

                    ], 500);
                }

                if ($amoutCharges > $user_wallet_banlance) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Insufficient Funds, fund your account',

                    ], 500);
                }

                //Debit
                $debited_amount = $transfer_charges + $amount;
                $debit = $user_wallet_banlance - $debited_amount;

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
                    // CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/ApiFundTransfer',
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

                $error = $var->error->message ?? null;

                $message = "Error from Errand Pay | $error ";

                $trans_id = trx();

                $TransactionReference = $var->data->reference ?? null;

                $enkpay_profit = $transfer_charges - 10;

                $status = $var->code ?? null;

                //$status = 209;

                if ($status == 200) {

                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $TransactionReference;
                    $trasnaction->type = "InterBankTransfer";
                    $trasnaction->main_type = "Transfer";
                    $trasnaction->transaction_type = "BankTransfer";
                    $trasnaction->title = "Bank Transfer";
                    $trasnaction->debit = $debited_amount;
                    $trasnaction->amount = $amount;
                    $trasnaction->note = "Bank Transfer to other banks";
                    $trasnaction->fee = 0;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->trx_date = date("Y/m/d");
                    $trasnaction->trx_time = date("h:i:s");
                    $trasnaction->receiver_name = $destinationAccountName;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
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
                    $credit = $user_wallet_banlance + $amount - $amount;

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


                    //save failed Transactions

                    $chk = FailedTransaction::where('user_id', Auth::id())->first()->user_id ?? null;
                    if ($chk == null) {

                        $f = new FailedTransaction();
                        $f->user_id = Auth::id();
                        $f->attempt = 1;
                        $f->created_at = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
                        $f->save();
                    }



                    $parametersJson = json_encode($request->all());
                    $headers = json_encode($request->headers->all());
                    $full_name = Auth::user()->first_name . "  " . Auth::user()->last_name;

                    $ip = $request->ip();

                    $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\n Full Name=======> " . $full_name . "\n\nIP========> " . $ip;
                    send_notification($result);

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Service not available at the moment, please wait and try again later',

                    ], 500);
                }
            }



            //Manuel

            if ($set->bank == 'manuel') {
                $wallet = $request->wallet;
                $amount = $request->amount;
                $destinationAccountNumber = $request->account_number;
                $destinationBankCode = $request->code;
                $longitude = $request->longitude;
                $latitude = $request->latitude;
                $get_description = $request->narration;
                $receiver_name = $request->customer_name;
                $pin = $request->pin;

                $account_number = $destinationAccountNumber;
                $bank_code = $destinationBankCode;



                $destinationAccountName = null; //resolve_bank($account_number, $bank_code);

                $bank_name = VfdBank::select('bankName')->where('code', $destinationBankCode)->first()->bankName ?? null;
                $referenceCode = trx();
                $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;

                $amoutCharges = $amount + $transfer_charges;


                if (Auth::user()->status == 5) {


                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You can not make transfer at the moment, Please contact  support',

                    ], 500);
                }




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

                if (Auth::user()->b_number == 6) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'You dont have the permission to make transfer',

                    ], 500);
                }

                if ($amount < 100) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Amount must not be less than NGN 100',

                    ], 500);
                }

                if (Auth()->user()->status == 1 && $amount > 20000) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Please Complete your KYC',

                    ], 500);
                }

                if ($amoutCharges > $user_wallet_banlance) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Insufficient Funds, fund your account',

                    ], 500);
                }

                //Debit
                $debited_amount = $transfer_charges + $amount;
                $debit = $user_wallet_banlance - $debited_amount;

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


                $trans_id = trx();


                $enkpay_profit = $transfer_charges - 10;
                $status = 200;

                $data = 'manuel';

                if ($status == 200) {

                    //update Transactions
                    $trasnaction = new Transfer();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->type = "EPBankTransfer";
                    $trasnaction->main_type = "Transfer";
                    $trasnaction->transaction_type = "BankTransfer";
                    $trasnaction->title = "Bank Transfer";
                    $trasnaction->debit = $debited_amount;
                    $trasnaction->amount = $amount;
                    $trasnaction->note = "BANK TRANSFER TO | $destinationAccountName | $destinationAccountNumber | $bank_name  ";
                    $trasnaction->fee = 0;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $receiver_name;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->receiver_bank = $bank_name;
                    $trasnaction->balance = $debit;
                    $trasnaction->status = 1;
                    $trasnaction->save();


                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = Auth::id();
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->type = "EPBankTransfer";
                    $trasnaction->main_type = "Transfer";
                    $trasnaction->transaction_type = "BankTransfer";
                    $trasnaction->title = "Bank Transfer";
                    $trasnaction->debit = $debited_amount;
                    $trasnaction->amount = $amount;
                    $trasnaction->note = "BANK TRANSFER TO | $destinationAccountName | $destinationAccountNumber | $bank_name  ";
                    $trasnaction->fee = 0;
                    $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                    $trasnaction->receiver_name = $receiver_name;
                    $trasnaction->receiver_account_no = $destinationAccountNumber;
                    $trasnaction->receiver_bank = $bank_name;
                    $trasnaction->balance = $debit;
                    $trasnaction->status = 1;
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

                    $name = Auth::user()->first_name . " " . Auth::user()->last_name;

                    $ip = $request->ip();
                    $message = $name . "| Request to transfer NGN " . $amount . " | " . $bank_name . " | " . $destinationAccountNumber;
                    $result = "Message========> " . $message . "\n\nIP========> " . $ip;
                    send_notification($result);

                    return response()->json([
                        'status' => $this->success,
                        'message' => "Transaction Processing",
                    ], 200);
                } else {


                    // $balance = User::where('id', Auth::id())->first()->main_wallet;
                    // $trasnaction = new Transaction();
                    // $trasnaction->user_id = Auth::id();
                    // $trasnaction->ref_trans_id = $trans_id;
                    // $trasnaction->type = "Reversal";
                    // $trasnaction->main_type = "Reversal";
                    // $trasnaction->transaction_type = "Reversal";
                    // $trasnaction->title = "Reversal";
                    // $trasnaction->credit = $debited_amount;
                    // $trasnaction->amount = $amount;
                    // $trasnaction->note = "Reversal  for | $destinationAccountName | $destinationAccountNumber | $bank_name  ";
                    // $trasnaction->fee = 0;
                    // $trasnaction->enkpay_Cashout_profit = 0;
                    // $trasnaction->receiver_name = $receiver_name;
                    // $trasnaction->receiver_account_no = $destinationAccountNumber;
                    // $trasnaction->receiver_bank = $bank_name;
                    // $trasnaction->balance = $debit;
                    // $trasnaction->status = 3;
                    // $trasnaction->save();


                    // if ($wallet == 'main_account') {
                    //     User::where('id', Auth::id()->first()->increment('main_wallet', $amount));
                    // }

                    // if ($wallet == 'bonus_account') {
                    //     User::where('id', Auth::id()->first()->increment('bonus_wallet', $amount));
                    // }

                    $full_name = Auth::user()->first_name . " " . Auth::user()->last_name;

                    $amount4 = number_format($debited_amount, 2);
                    $message = "$trans_id | NGN $amount4 has been hit an error  $full_name";
                    send_notification($message);

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Service not available at the moment, please wait and try again later',

                    ], 500);
                }
            }
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function self_cash_out(Request $request)
    {

        $pos_trx = Feature::where('id', 1)->first()->pos_transfer ?? null;
        if ($pos_trx == 0) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Transfer is not available at the moment, \n\n Please try again after some time",
            ], 500);
        }




        $ckid = PendingTransaction::where('user_id', Auth::id())->first()->user_id ?? null;
        if ($ckid == Auth::id()) {

            $message = Auth::user()->first_name . " " . Auth::user()->last_name . " | has reached this double endpoint";
            send_notification($message);

            return response()->json([

                'status' => $this->failed,
                'message' => 'Please wait for some time and try again',

            ], 500);
        }


        if (Auth::user()->status == 7) {


            return response()->json([

                'status' => $this->failed,
                'message' => 'You can not make transfer at the moment, Please contact  support',

            ], 500);
        }




        $set = Setting::where('id', 1)->first();

        //VFD BANK
        if ($set->bank == 'vfd') {

            $chk = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
            $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;

            if ($fa != null) {

                if ($chk->user_id == Auth::id()) {


                    $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $fa->created_at);
                    $currentTime = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
                    # count difference in minutes
                    $minuteDiff = $anchorTime->diffInMinutes($currentTime);


                    if ($minuteDiff >= 3) {
                        FailedTransaction::where('user_id', Auth::id())->delete();
                    }
                }
            }


            $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
            if ($fa != null) {

                if ($fa->attempt == 1) {
                    return response()->json([
                        'status' => $this->failed,
                        'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                    ], 500);
                }
            }

            $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
            if ($fa != null) {

                if ($fa->attempt == 1) {
                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Service not available at the moment, please wait for about 2 mins and try again',
                    ], 500);
                }
            }




            $wallet = $request->wallet;
            $amount = $request->amount;
            $destinationAccountNumber = Auth::user()->c_account_number ?? null;
            $destinationBankCode = Auth::user()->c_bank_code;
            $destinationAccountName = Auth::user()->c_account_name;
            $longitude = $request->longitude;
            $latitude = $request->latitude;
            $receiver_name = $request->customer_name;
            $get_description = "Self Cash out to bank account";
            $pin = $request->pin;

            $referenceCode = trx();

            $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
            $bank_name = VfdBank::select('bankName')->where('code', $destinationBankCode)->first()->bankName ?? null;
            $amoutCharges = $amount + $transfer_charges;



            $ckid = PendingTransaction::where('user_id', Auth::id())->first()->user_id ?? null;
            if ($ckid == Auth::id()) {

                $message = Auth::user()->first_name . " " . Auth::user()->last_name . " | has reached this double endpoint";
                send_notification($message);

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please wait for some time and try again',

                ], 500);
            }



            if (Auth::user()->status == 5) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => 'You can not make transfer at the moment, Please contact  support',

                ], 500);
            }

            if (Auth::user()->status != 2) {

                $message = Auth::user()->first_name . " " . Auth::user()->last_name . " | Unverified Account trying withdraw";
                send_notification($message);

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please verify your account to enjoy enkpay full service',
                ], 500);
            }


            $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
            if ($fa !== null) {


                if ($fa->attempt == 1) {
                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Service not available at the moment, please wait and try again later',

                    ], 500);
                }
            }


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

            if (Auth()->user()->status == 1 && $amount > 20000) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please Complete your KYC',

                ], 500);
            }



            if ($wallet == 'main_account') {


                if ($amoutCharges > Auth::user()->main_wallet) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Insufficient Funds, fund your main wallet',

                    ], 500);
                }
            } else {

                if ($amoutCharges > Auth::user()->bonus_wallet) {

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Insufficient Funds, fund your main wallet',

                    ], 500);
                }
            }

            if ($amoutCharges > $user_wallet_banlance) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, fund your account',

                ], 500);
            }





            $status = 200;
            $message = "Error from Errand Pay";
            $enkpay_profit = $transfer_charges - 10;
            $trans_id = trx();

            if ($status == 200) {

                //Debit
                $debited_amount = $transfer_charges + $amount;

                if ($wallet == 'main_account') {

                    User::where('id', Auth::id())->decrement('main_wallet', $debited_amount);
                } else {
                    User::where('id', Auth::id())->decrement('bonus_wallet', $debited_amount);
                }


                $balance = User::where('id', Auth::id())->first()->main_wallet;
                $user_balance =  $balance - $debited_amount;

                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = Auth::id();
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->type = "InterBankTransfer";
                $trasnaction->main_type = "Transfer";
                $trasnaction->transaction_type = "BankTransfer";
                $trasnaction->title = "Bank Transfer";
                $trasnaction->debit = $amoutCharges;
                $trasnaction->amount = $amount;
                $trasnaction->note = "BANK TRANSFER TO | $receiver_name | $destinationAccountNumber | $bank_name  ";
                $trasnaction->fee = 0;
                $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                $trasnaction->receiver_name = $destinationAccountName;
                $trasnaction->receiver_account_no = $destinationAccountNumber;
                $trasnaction->balance = $balance;
                $trasnaction->status = 0;
                $trasnaction->save();




                //update Transactions
                $trasnaction = new PendingTransaction();
                $trasnaction->user_id = Auth::id();
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->debit = $amoutCharges;
                $trasnaction->amount = $amount;
                $trasnaction->bank_code = $amount;
                $trasnaction->bank_code = $destinationBankCode;
                $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                $trasnaction->receiver_name = $destinationAccountName;
                $trasnaction->receiver_account_no = $destinationAccountNumber;
                $trasnaction->receiver_name = $balance;
                $trasnaction->status = 0;
                $trasnaction->save();

                //Transfers
                $trasnaction = new Transfer();
                $trasnaction->user_id = Auth::id();
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->type = "EPBankTransfer";
                $trasnaction->main_type = "Transfer";
                $trasnaction->transaction_type = "BankTransfer";
                $trasnaction->title = "Bank Transfer";
                $trasnaction->debit = $amoutCharges;
                $trasnaction->amount = $amount;
                $trasnaction->note = "BANK TRANSFER TO | $receiver_name | $destinationAccountNumber | $bank_name  ";
                $trasnaction->bank_code = $destinationBankCode;
                $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                $trasnaction->receiver_name = $receiver_name;
                $trasnaction->receiver_account_no = $destinationAccountNumber;
                $trasnaction->receiver_bank = $bank_name;
                $trasnaction->balance = $balance;
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


                $wallet = Auth::user()->main_wallet;
                $name = Auth::user()->first_name . " " . Auth::user()->last_name;
                $ip = $request->ip();
                $message = $name . "| Request to transfer NGN " . $amount . " | " . $bank_name . " | " . $destinationAccountNumber . " User balance | $user_balance ";
                $result = "Message========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);


                return response()->json([
                    'status' => $this->success,
                    'message' => "Transaction Processing",

                ], 200);
            }
        }







        if ($set->bank == 'pbank') {


            $chk = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
            $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;

            if ($fa != null) {

                if ($chk->user_id == Auth::id()) {


                    $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $fa->created_at);
                    $currentTime = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
                    # count difference in minutes
                    $minuteDiff = $anchorTime->diffInMinutes($currentTime);


                    if ($minuteDiff >= 2) {
                        FailedTransaction::where('user_id', Auth::id())->delete();
                    }
                }
            }


            $fa = FailedTransaction::where('user_id', Auth::id())->first() ?? null;
            if ($fa != null) {

                if ($fa->attempt = 1) {
                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Service not available at the moment, please wait for about 2 mins and try again',

                    ], 500);
                }
            }




            $account_number = Auth::user()->c_account_number ?? null;
            $bank_code = Auth::user()->c_bank_code;
            $account_name = Auth::user()->c_account_name;

            $erran_api_key = errand_api_key();

            $epkey = env('EPKEY');



            $wallet = $request->wallet;
            $amount = $request->amount;
            $destinationAccountNumber = $account_number;
            $destinationBankCode = $bank_code;
            $destinationAccountName = $account_name;
            $longitude = $request->longitude;
            $latitude = $request->latitude;
            $get_description = "Cash out to " . "|" . $destinationAccountNumber . " | " . $destinationAccountName;
            $pin = $request->pin;

            $referenceCode = trx();

            $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;


            $amoutCharges = $amount + $transfer_charges;


            $user_email = user_email();
            $first_name = first_name();

            $description = $get_description ?? "Fund for $destinationAccountName";

            if ($account_number == null) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please update your account information.',

                ], 500);
            }

            if (Auth::user()->b_number == 6) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'You dont have the permission to make transfer',

                ], 500);
            }

            if ($bank_code == null) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please update your account information.',

                ], 500);
            }

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

            if (Auth()->user()->status == 1 && $amount > 20000) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please Complete your KYC',

                ], 500);
            }

            if ($amoutCharges > $user_wallet_banlance) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, fund your account',

                ], 500);
            }

            //Debit
            $charged_amount = $amount + $transfer_charges;
            $debit = $user_wallet_banlance - $charged_amount;
            $enkpay_profit = $transfer_charges - 10;

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
                // CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/ApiFundTransfer',
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

            $message = "Error from Bank Transfer" . "  |  " . $var->error->message ?? null;

            $trans_id = trx();

            $TransactionReference = $var->data->reference ?? null;

            $status = $var->code ?? null;

            if ($status == 200) {

                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = Auth::id();
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $TransactionReference;
                $trasnaction->type = "SelfCashOutTransfer";
                $trasnaction->title = "Transfer to Self";
                $trasnaction->main_type = "Transfer";
                $trasnaction->transaction_type = "BankTransfer";
                $trasnaction->title = "Bank Transfer";
                $trasnaction->debit = $charged_amount;
                $trasnaction->amount = $amount;
                $trasnaction->note = "Cash out to " . "|" . $destinationAccountNumber . " | " . $destinationAccountName;
                $trasnaction->fee = 0;
                $trasnaction->enkPay_Cashout_profit = $enkpay_profit;
                $trasnaction->trx_date = date("Y/m/d");
                $trasnaction->trx_time = date("h:i:s");
                $trasnaction->receiver_name = $destinationAccountName;
                $trasnaction->receiver_account_no = $destinationAccountNumber;
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
                $credit = $user_wallet_banlance + $amount - $amount;

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

                $chk = FailedTransaction::where('user_id', Auth::id())->first()->user_id ?? null;
                if ($chk == null) {

                    $f = new FailedTransaction();
                    $f->user_id = Auth::id();
                    $f->attempt = 1;
                    $f->created_at = Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:00"));
                    $f->save();
                }




                $parametersJson = json_encode($request->all());
                $headers = json_encode($request->headers->all());
                $full_name = Auth::user()->first_name . "  " . Auth::user()->last_name;
                $ip = $request->ip();

                $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\n Full Name=======> " . $full_name . "\n\nIP========> " . $ip;
                send_notification($result);

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Service not reachable, please try again later',

                ], 500);
            }
        }


        if ($set->bank == 'manuel') {

            $account_number = Auth::user()->c_account_number ?? null;
            $bank_code = Auth::user()->c_bank_code;
            $account_name = resolve_bank($account_number, $bank_code);
            $bank_name = VfdBank::select('bankName')->where('code', $bank_code)->first()->bankName ?? null;




            $erran_api_key = errand_api_key();

            $epkey = env('EPKEY');



            $wallet = $request->wallet;
            $amount = $request->amount;
            $destinationAccountNumber = $account_number;
            $destinationBankCode = $bank_code;
            $destinationAccountName = $account_name;
            $longitude = $request->longitude;
            $latitude = $request->latitude;
            $get_description = "Cash out to " . "|" . $destinationAccountNumber . " | " . $destinationAccountName;
            $pin = $request->pin;

            $referenceCode = trx();

            $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;


            $amoutCharges = $amount + $transfer_charges;


            $user_email = user_email();
            $first_name = first_name();

            $description = $get_description ?? "Fund for $destinationAccountName";

            if ($account_number == null) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please update your account information.',

                ], 500);
            }

            if (Auth::user()->b_number == 6) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'You dont have the permission to make transfer',

                ], 500);
            }

            if ($bank_code == null) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please update your account information.',

                ], 500);
            }

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

            if (Auth()->user()->status == 1 && $amount > 20000) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please Complete your KYC',

                ], 500);
            }

            if ($amoutCharges > $user_wallet_banlance) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, fund your account',

                ], 500);
            }

            //Debit
            $charged_amount = $amount + $transfer_charges;
            $debit = $user_wallet_banlance - $charged_amount;
            $enkpay_profit = $transfer_charges - 10;





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

            $status = 200;

            if ($status == 200) {

                //update Transactions
                $trasnaction = new Transfer();
                $trasnaction->user_id = Auth::id();
                $trasnaction->ref_trans_id = $referenceCode;
                $trasnaction->type = "EPBankTransfer";
                $trasnaction->main_type = "Transfer";
                $trasnaction->transaction_type = "BankTransfer";
                $trasnaction->title = "Bank Transfer";
                $trasnaction->debit = $charged_amount;
                $trasnaction->amount = $amount;
                $trasnaction->note = "BANK TRANSFER TO | $destinationAccountName | $destinationAccountNumber | $bank_name  ";
                $trasnaction->fee = 0;
                $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                $trasnaction->receiver_name = $destinationAccountName;
                $trasnaction->receiver_account_no = $destinationAccountNumber;
                $trasnaction->receiver_bank = $bank_name;
                $trasnaction->balance = $debit;
                $trasnaction->status = 1;
                $trasnaction->save();


                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = Auth::id();
                $trasnaction->ref_trans_id = $referenceCode;
                $trasnaction->type = "EPBankTransfer";
                $trasnaction->main_type = "Transfer";
                $trasnaction->transaction_type = "BankTransfer";
                $trasnaction->title = "Bank Transfer";
                $trasnaction->debit = $charged_amount;
                $trasnaction->amount = $amount;
                $trasnaction->note = "BANK TRANSFER TO | $destinationAccountName | $destinationAccountNumber | $bank_name  ";
                $trasnaction->fee = 0;
                $trasnaction->enkpay_Cashout_profit = $enkpay_profit;
                $trasnaction->receiver_name = $destinationAccountName;
                $trasnaction->receiver_account_no = $destinationAccountNumber;
                $trasnaction->receiver_bank = $bank_name;
                $trasnaction->balance = $debit;
                $trasnaction->status = 1;
                $trasnaction->save();



                $ip = $request->ip();
                $message = "Request to transfer $amount | $destinationAccountName | $bank_name | $destinationAccountName ";
                $result = "Message========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);


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
                    'message' => "Transaction Processing",

                ], 200);
            } else {


                // $balance = User::where('id', Auth::id())->first()->main_wallet;
                // $trasnaction = new Transaction();
                // $trasnaction->user_id = Auth::id();
                // $trasnaction->ref_trans_id = $trans_id;
                // $trasnaction->type = "Reversal";
                // $trasnaction->main_type = "Reversal";
                // $trasnaction->transaction_type = "Reversal";
                // $trasnaction->title = "Reversal";
                // $trasnaction->credit = $charged_amount;
                // $trasnaction->amount = $amount;
                // $trasnaction->note = "Reversal  for | $destinationAccountName | $destinationAccountNumber | $bank_name  ";
                // $trasnaction->fee = 0;
                // $trasnaction->enkpay_Cashout_profit = 0;
                // $trasnaction->receiver_name = $receiver_name;
                // $trasnaction->receiver_account_no = $destinationAccountNumber;
                // $trasnaction->receiver_bank = $bank_name;
                // $trasnaction->balance = $debit;
                // $trasnaction->status = 3;
                // $trasnaction->save();


                // if ($wallet == 'main_account') {
                //     User::where('id', Auth::id()->first()->increment('main_wallet', $amount));
                // }

                // if ($wallet == 'bonus_account') {
                //     User::where('id', Auth::id()->first()->increment('bonus_wallet', $amount));
                // }

                $full_name = Auth::user()->first_name . " " . Auth::user()->last_name;

                $amount4 = number_format($charged_amount, 2);
                $message = "$trans_id | NGN $amount4 has hit error  $full_name";
                send_notification($message);

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Service not available at the moment, please wait and try again later',

                ], 500);
            }
        }

        // } catch (\Exception $th) {
        //     return $th->getMessage();
        // }
    }

    public function get_wallet()
    {

        try {

            $account = select_account();

            return response()->json([

                'status' => $this->success,
                'account' => $account,

            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function transfer_properties()
    {

        try {



            $set = Setting::where('id', 1)->first();
            if ($set->bank == 'vfd') {
                $data = 'vfd';
            } else {
                $data = 'pbank';
            }

            $banks = get_banks($data);


            $account = select_account();


            $transfer_charge = Charge::where('title', 'transfer_fee')
                ->first()->amount;

            $status = 200;


            if ($status == 200) {

                return response()->json([

                    'account' => $account,
                    'transfer_charge' => $transfer_charge,
                    'banks' => $banks,

                ], 200);
            }
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function selfcashout_properties()
    {

        try {

            $account = select_account();

            $charges = Charge::where('title', 'transfer_fee')
                ->first()->amount;

            return response()->json([

                'account' => $account,
                'transfer_charge' => $charges,

            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function resolve_bank(request $request)
    {

        try {

            $bank_code = $request->bank_code;
            $account_number = $request->account_number;
            //$bvn = $request->bvn;

            $resolve = resolve_bank($bank_code, $account_number);

            return response()->json([
                'status' => true,
                'customer_name' => $resolve,

            ], 200);
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function enkpay_transfer(request $request)
    {

        try {


            if (Auth::user()->status == 7) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => "You can not make any transaction at the moment, \n\n Please contact  support",

                ], 500);
            }


            $ck_ip = User::where('id', Auth::id())->first()->ip_address ?? null;
            if ($ck_ip != $request->ip()) {

                $name = Auth::user()->first_name . " " . Auth::user()->last_name;
                $ip = $request->ip();
                $message = $name . "| Multiple Transaction Detected Mother fuckers";
                $result = "Message========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);

                return response()->json([

                    'status' => $this->failed,
                    'message' => "Multiple Transaction Detected \n\n Please Log out and Login then try again",

                ], 500);
            }

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
            $receiver_l_name = User::where('phone', $phone)->first()->last_name ?? null;
            $receiver_status = User::where('phone', $phone)->first()->status ?? null;
            $receiver_full_name = $receiver_f_name . "  " . $receiver_l_name;

            //sender info
            $sender_f_name = first_name() ?? null;
            $sender_l_name = last_name() ?? null;
            $sender_full_name = $sender_f_name . "  " . $sender_l_name;

            $trans_id = trx();

            //check

            if ($phone == user_phone()) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "You can not send money to yourself",
                ], 500);
            }

            if ($amount >= 20000 && user_status() == 1) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please Complete your KYC',

                ], 500);
            }

            if ($receiver_main_wallet == null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "User not available on ENKPAY",
                ], 500);
            }

            if ($receiver_status !== 2) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "User not verified",
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
            $trasnaction->title = "Enkpay Transfer";
            $trasnaction->type = "InAppTransfer";
            $trasnaction->main_type = "Transfer";
            $trasnaction->debit = $amount;
            $trasnaction->amount = $amount;
            $trasnaction->note = "ENKPAY TRANSFER | $phone ";
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->trx_date = date("Y/m/d");
            $trasnaction->trx_time = date("h:i:s");
            $trasnaction->receiver_name = $receiver_full_name;
            $trasnaction->receiver_account_no = $phone;
            $trasnaction->balance = $debit;
            $trasnaction->status = 1;
            $trasnaction->save();

            // //second database

            // $trasnaction = new Transaction2();
            // $trasnaction->setConnection('mysql_second');
            // $trasnaction->user_id = Auth::id();
            // $trasnaction->from_user_id = Auth::id();
            // $trasnaction->to_user_id = $receiver_id;
            // $trasnaction->ref_trans_id = $trans_id;
            // $trasnaction->transaction_type = "EnkPayTransfer";
            // $trasnaction->title = "Enkpay Transfer";
            // $trasnaction->type = "InAppTransfer";
            // $trasnaction->main_type = "Transfer";
            // $trasnaction->debit = $amount;
            // $trasnaction->note = "Bank Transfer to Enk Pay User";
            // $trasnaction->fee = 0;
            // $trasnaction->e_charges = 0;
            // $trasnaction->trx_date = date("Y/m/d");
            // $trasnaction->trx_time = date("h:i:s");
            // $trasnaction->receiver_name = $receiver_full_name;
            // $trasnaction->receiver_account_no = $phone;
            // $trasnaction->balance = $debit;
            // $trasnaction->status = 1;
            // $trasnaction->save();

            //credit receiver

            $user_phone = user_phone();
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
            $trasnaction->title = "Enkpay Transfer";
            $trasnaction->main_type = "Transfer";
            $trasnaction->type = "InAppTransfer";
            $trasnaction->credit = $amount;
            $trasnaction->note = "ENKPAY TRANSFER | $user_phone ";
            $trasnaction->fee = 0;
            $trasnaction->amount = $amount;
            $trasnaction->e_charges = 0;
            $trasnaction->trx_date = date("Y/m/d");
            $trasnaction->trx_time = date("h:i:s");
            $trasnaction->sender_name = $sender_full_name;
            $trasnaction->sender_account_no = user_phone();
            $trasnaction->balance = $credit;
            $trasnaction->status = 1;
            $trasnaction->save();

            // $trasnaction = new Transaction2();
            // $trasnaction->setConnection('mysql_second');
            // $trasnaction->user_id = $receiver_id;
            // $trasnaction->from_user_id = Auth::id();
            // $trasnaction->to_user_id = $receiver_id;
            // $trasnaction->ref_trans_id = $trans_id;
            // $trasnaction->transaction_type = "EnkPayTransfer";
            // $trasnaction->title = "Enkpay Transfer";
            // $trasnaction->main_type = "Transfer";
            // $trasnaction->type = "InAppTransfer";
            // $trasnaction->credit = $amount;
            // $trasnaction->note = "Bank Transfer to Enk Pay User";
            // $trasnaction->fee = 0;
            // $trasnaction->e_charges = 0;
            // $trasnaction->trx_date = date("Y/m/d");
            // $trasnaction->trx_time = date("h:i:s");
            // $trasnaction->sender_name = $sender_full_name;
            // $trasnaction->sender_account_no = user_phone();
            // $trasnaction->balance = $credit;
            // $trasnaction->status = 1;
            // $trasnaction->save();

            //sender email

            if (!empty(user_email())) {

                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Debit Notification",
                    'toreceiver' => user_email(),
                    'first_name' => first_name(),
                    'amount' => $amount,
                    'receiver' => $receiver_full_name,

                );

                Mail::send('emails.transaction.sender', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });
            }

            //receiver email

            if (!empty($receiver_email)) {

                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Credit Notification",
                    'toreceiver' => $receiver_email,
                    'first_name' => $receiver_f_name,
                    'amount' => $amount,
                    'sender' => $sender_full_name,

                );

                Mail::send('emails.transaction.receiver', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });
            }

            return response()->json([

                'status' => $this->success,
                'message' => 'Transfer Successful',

            ], 200);
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function cash_out_webhook(Request $request)
    {


        // // $parametersJson = json_encode($request->all());
        // // $message = 'Log 1';
        // // $ip = $request->ip();

        // $result = "Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
        // send_notification($result);




        $header = $request->header('errand-pay-header');
        $ip = $request->ip();

        //pos transaction pu1


        if ($request->ServiceCode == 'PU1') {

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
            $MaskedPAN = $request->AdditionalDetails['MaskedPAN'];

            $eip = env('EIP');
            //$eip = '127.0.0.1';

            $trans_id = trx();

            //$verify1 = hash('sha512', $key);

            $comission = Charge::where('title', 'both_commission')
                ->first()->amount;

            if ($eip == $ip) {

                //Get user ID
                $user_id = Terminal::where('serial_no', $SerialNumber)
                    ->first()->user_id ?? null;

                //Main Wallet
                $main_wallet = User::where('id', $user_id)
                    ->first()->main_wallet ?? null;

                $type = User::where('id', $user_id)
                    ->first()->type ?? null;

                if ($main_wallet == null && $user_id == null) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Customer not registred on Enkpay',
                    ], 500);
                }

                //Both Commission
                $amount1 = $comission / 100;
                $amount2 = $amount1 * $Amount;
                $both_commmission = number_format($amount2, 3);

                //enkpay commission
                $commison_subtract = $comission - 0.425;
                $enkPayPaypercent = $commison_subtract / 100;
                $enkPay_amount = $enkPayPaypercent * $Amount;
                $enkpay_commision_amount = number_format($enkPay_amount, 3);

                //errandpay commission
                $errandPaypercent = 0.425 / 100;
                $errand_amount = $errandPaypercent * $Amount;
                $errandPay_commission_amount = number_format($errand_amount, 3);

                $business_commission_cap = Charge::where('title', 'business_cap')
                    ->first()->amount;

                $agent_commission_cap = Charge::where('title', 'agent_cap')
                    ->first()->amount;

                if ($both_commmission >= $agent_commission_cap && $type == 1) {

                    $removed_comission = $Amount - $agent_commission_cap;

                    $enkpay_profit = $agent_commission_cap - 75;
                } elseif ($both_commmission >= $business_commission_cap && $type == 3) {

                    $removed_comission = $Amount - $business_commission_cap;

                    $enkpay_profit = $business_commission_cap - 75;
                } else {

                    $removed_comission = (int)$Amount - (int)$both_commmission;

                    $enkpay_profit = (int)$both_commmission - (int)$errandPay_commission_amount;
                }

                //$enkpay_cashOut_fee = $amount - $enkpay_commision_amount ;

                $updated_amount = $main_wallet + $removed_comission;

                $main_wallet = User::where('id', $user_id)
                    ->update([
                        'main_wallet' => $updated_amount,
                    ]);

                if ($TransactionType == 'Purchase') {

                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $user_id;
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $TransactionReference;
                    $trasnaction->transaction_type = $TransactionType;
                    $trasnaction->credit = round($removed_comission, 2);
                    $trasnaction->e_charges = $enkpay_profit;
                    $trasnaction->title = "POS Transasction";
                    $trasnaction->note = "EP POS | $MaskedPAN ";
                    $trasnaction->fee = $Fee;
                    $trasnaction->amount = $Amount;
                    $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
                    $trasnaction->balance = $updated_amount;
                    $trasnaction->terminal_id = $TerminalID;
                    $trasnaction->serial_no = $SerialNumber;
                    $trasnaction->sender_account_no = $MaskedPAN;
                    $trasnaction->status = 1;
                    $trasnaction->save();
                }

                $f_name = User::where('id', $user_id)->first()->first_name ?? null;
                $l_name = User::where('id', $user_id)->first()->last_name ?? null;

                $ip = $request->ip();
                $amount4 = number_format($removed_comission, 2);
                $result = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | using Card POS" . "\n\nIP========> " . $ip;
                send_notification($result);

                return response()->json([
                    'status' => true,
                    'message' => 'Tranasaction Successsfull',
                ], 200);
            } else {

                $parametersJson = json_encode($request->all());
                $headers = json_encode($request->headers->all());
                $message = 'Key not Authorized';
                $ip = $request->ip();

                $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);

                return response()->json([
                    'status' => false,
                    'message' => 'Key not Authorized',
                ], 401);
            }
        }

        //pos transaction co1

        if (
            $request->ServiceCode == 'CO1'
            || $request->ServiceCode == "C01"
        ) {


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
            $MaskedPAN = $request->AdditionalDetails['MaskedPAN'];

            $eip = env('EIP');
            //$eip = '127.0.0.1';

            $trans_id = trx();

            //$verify1 = hash('sha512', $key);

            $comission = Charge::where('title', 'both_commission')
                ->first()->amount;

            if ($eip == $ip) {

                //Get user ID
                $user_id = Terminal::where('serial_no', $SerialNumber)
                    ->first()->user_id ?? null;

                //Main Wallet
                $main_wallet = User::where('id', $user_id)
                    ->first()->main_wallet ?? null;

                $type = User::where('id', $user_id)
                    ->first()->type ?? null;

                if ($main_wallet == null && $user_id == null) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Customer not registred on Enkpay',
                    ], 500);
                }

                //Both Commission
                $amount1 = $comission / 100;
                $amount2 = $amount1 * $Amount;
                $both_commmission = number_format($amount2, 3);

                //enkpay commission
                $commison_subtract = $comission - 0.425;
                $enkPayPaypercent = $commison_subtract / 100;
                $enkPay_amount = $enkPayPaypercent * $Amount;
                $enkpay_commision_amount = number_format($enkPay_amount, 3);

                //errandpay commission
                $errandPaypercent = 0.425 / 100;
                $errand_amount = $errandPaypercent * $Amount;
                $errandPay_commission_amount = number_format($errand_amount, 3);

                $business_commission_cap = Charge::where('title', 'business_cap')
                    ->first()->amount;

                $agent_commission_cap = Charge::where('title', 'agent_cap')
                    ->first()->amount;

                if ($both_commmission >= $agent_commission_cap && $type == 1) {

                    $removed_comission = $Amount - $agent_commission_cap;

                    $enkpay_profit = $agent_commission_cap - 75;
                } elseif ($both_commmission >= $business_commission_cap && $type == 3) {

                    $removed_comission = (int)$Amount - (int)$business_commission_cap;

                    $enkpay_profit = (int)$business_commission_cap - 75;
                } else {

                    $removed_comission = (int)$Amount - (int)$both_commmission;

                    $enkpay_profit = (int)$both_commmission - (int)$errandPay_commission_amount;
                }

                //$enkpay_cashOut_fee = $amount - $enkpay_commision_amount ;

                $updated_amount = $main_wallet + $removed_comission;

                $main_wallet = User::where('id', $user_id)
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
                    $trasnaction->credit = round($removed_comission, 2);
                    $trasnaction->e_charges = $enkpay_profit;
                    $trasnaction->title = "POS Transasction";
                    $trasnaction->note = "EP POS | $MaskedPAN ";
                    $trasnaction->fee = $Fee;
                    $trasnaction->amount = $Amount;
                    $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
                    $trasnaction->balance = $updated_amount;
                    $trasnaction->terminal_id = $TerminalID;
                    $trasnaction->serial_no = $SerialNumber;
                    $trasnaction->sender_account_no = $MaskedPAN;
                    $trasnaction->status = 1;
                    $trasnaction->save();
                }



                $f_name = User::where('id', $user_id)->first()->first_name ?? null;
                $l_name = User::where('id', $user_id)->first()->last_name ?? null;

                $ip = $request->ip();
                $amount4 = number_format($removed_comission, 2);
                $result = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | using Card POS" . "\n\nIP========> " . $ip;
                send_notification($result);


                return response()->json([
                    'status' => true,
                    'message' => 'Tranasaction Successsfull',
                ], 200);
            } else {

                $parametersJson = json_encode($request->all());
                $headers = json_encode($request->headers->all());
                $message = 'Key not Authorized';
                $ip = $request->ip();

                $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);

                return response()->json([
                    'status' => false,
                    'message' => 'Key not Authorized',
                ], 401);
            }
        }





        //Cable and Eletric

        if (($request->ServiceCode == 'BUB1') || ($request->ServiceCode == 'BUB2') || ($request->ServiceCode == 'BUB3') || ($request->ServiceCode == 'BUB4')

            || ($request->ServiceCode == 'BUB5') || ($request->ServiceCode == 'BUB6') || ($request->ServiceCode == 'BUB7') || ($request->ServiceCode == 'BUB8') || ($request->ServiceCode == 'BUB9') || ($request->ServiceCode == 'BUB10')

            || ($request->ServiceCode == 'BCT1') || ($request->ServiceCode == 'BCT2') || ($request->ServiceCode == 'BCT3')
        ) {

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
            $BillCategory = $request->AdditionalDetails['BillCategory'] ?? null;
            $BillService = $request->AdditionalDetails['BillService'] ?? null;
            $Beneficiary = $request->AdditionalDetails['Beneficiary'] ?? null;

            $trans_id = trx();

            $terminal_charge = Charge::where('title', 'terminal_charge')
                ->first()->amount;

            if ($StatusCode == 00) {

                //Get user ID
                $user_id = Terminal::where('serial_no', $SerialNumber)
                    ->first()->user_id ?? null;

                $main_wallet = User::where('id', $user_id)
                    ->first()->main_wallet ?? null;

                $type = User::where('id', $user_id)
                    ->first()->type ?? null;

                if ($main_wallet == null && $user_id == null) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Customer not registred on Enkpay',
                    ], 500);
                }

                //debit
                $debit_amount = $Amount + $terminal_charge;

                $debit_wallet = $main_wallet - $debit_amount;

                $main_wallet_update = User::where('id', $user_id)
                    ->update([
                        'main_wallet' => $debit_wallet,
                    ]);

                if ($TransactionType == 'BillsPayment') {

                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $user_id;
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $TransactionReference;
                    $trasnaction->transaction_type = $TransactionType;
                    $trasnaction->debit = $debit_amount;
                    $trasnaction->amount = $Amount;
                    $trasnaction->title = "Bills";
                    $trasnaction->note = "EP VAS | $BillService | $BillCategory | $Beneficiary ";
                    $trasnaction->fee = $Fee;
                    $trasnaction->enkPay_Cashout_profit = $terminal_charge;
                    $trasnaction->balance = $debit_wallet;
                    $trasnaction->serial_no = $SerialNumber;
                    $trasnaction->main_type = "EPvas";
                    $trasnaction->status = 1;
                    $trasnaction->save();
                }

                $ip = $request->ip();
                $amount4 = number_format($Amount, 2);
                $message = "NGN $Amount left pool account by $user_id for EPvas Transaction | EP VAS | $BillService | $BillCategory | $Beneficiary  ";
                $result = "Service========>" . $ServiceCode . "\n\nRefrence========>" . $TransactionReference . "\n\nSerial No========>" . $SerialNumber . "\n\nDate & Time========>" . $TransactionDate . " | " . $TransactionTime . "\n\nMessage========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);

                return response()->json([
                    'status' => true,
                    'message' => 'Tranasaction Successsfull',
                ], 200);
            }
        }

        //AIRTIME / DATA
        if (($request->ServiceCode == 'BAT1') || ($request->serviceCode == 'BAT1') || ($request->ServiceCode == 'BAT2') || ($request->ServiceCode == 'BAT3') || ($request->ServiceCode == 'BAT4')

            || ($request->ServiceCode == 'BMD1') || ($request->ServiceCode == 'BMD2') || ($request->ServiceCode == 'BMD3') || ($request->ServiceCode == 'BMD4')

            || ($request->ServiceCode == 'BMD5')
        ) {


            $StatusCode = $request->StatusCode;
            $StatusDescription = $request->StatusDescription;
            $SerialNumber = $request->SerialNumber ?? $request->serial_number;
            $Amount = $request->Amount ?? $request->amount;
            $Currency = $request->Currency;
            $TransactionDate = $request->TransactionDate;
            $TransactionTime = $request->TransactionTime;
            $TransactionType = $request->TransactionType ?? $request->transaction_type;
            $ServiceCode = $request->ServiceCode;
            $TransactionReference = $request->TransactionReference ?? $request->reference;
            $Fee = $request->Fee;
            $PostingType = $request->PostingType;
            $BillCategory = $request->AdditionalDetails['BillCategory'] ?? null;
            $BillService = $request->AdditionalDetails['BillService'] ?? null;
            $Beneficiary = $request->AdditionalDetails['Beneficiary'] ?? null;



            $key = env('ERIP');

            $trans_id = trx();

            $verify1 = hash('sha512', $key);

            $terminal_charge = Charge::where('title', 'terminal_charge')
                ->first()->amount;

            if ($StatusCode == 00) {

                //Get user ID
                $user_id = Terminal::where('serial_no', $SerialNumber)
                    ->first()->user_id ?? null;

                $main_wallet = User::where('id', $user_id)
                    ->first()->main_wallet ?? null;

                $type = User::where('id', $user_id)
                    ->first()->type ?? null;

                $f_name = User::where('id', $user_id)
                    ->first()->first_name ?? null;

                $l_name = User::where('id', $user_id)
                    ->first()->last_name ?? null;


                $full_name = $f_name . " " . $l_name;

                if ($main_wallet == null && $user_id == null) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Customer not registred on Enkpay',
                    ], 500);
                }


                if ($Amount > $main_wallet) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficent Funds',
                    ], 500);
                }

                //debit
                $debit_wallet = $main_wallet - $Amount;



                $main_wallet_update = User::where('id', $user_id)
                    ->update([
                        'main_wallet' => $debit_wallet,
                    ]);

                if ($TransactionType == 'BillsPayment') {

                    //update Transactions
                    Transaction::where('ref_trans_id', $TransactionReference)->update([
                        'status' => 1
                    ]);
                }

                $amount4 = number_format($Amount, 2);
                $message = "NGN $amount4 left pool Account by  $full_name for VAS | $BillService | $BillCategory | $Beneficiary ";
                send_notification($message);


                return response()->json([
                    'status' => true,
                    'message' => 'Tranasaction Successsfull',
                ], 200);
            }
        }

        //FUNDS TRANSFER

        if ($request->ServiceCode == 'FT1') {


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
            $DestinationAccountName = $request->AdditionalDetails['DestinationAccountName'] ?? null;
            $DestinationAccountNumber = $request->AdditionalDetails['DestinationAccountNumber'] ?? null;
            $DestinationBankName = $request->AdditionalDetails['DestinationBankName'] ?? null;

            $trans_id = trx();




            //Get user ID
            $user_id = Transaction::where('e_ref', $TransactionReference)
                ->first()->user_id ?? null;

            $f_name = User::where('id', $user_id)
                ->first()->first_name ?? null;

            $l_name = User::where('id', $user_id)
                ->first()->last_name ?? null;

            $full_name = $f_name . " " . $l_name;

            $main_wallet = User::where('id', $user_id)
                ->first()->main_wallet ?? null;

            $type = User::where('id', $user_id)
                ->first()->type ?? null;

            if ($main_wallet == null && $user_id == null) {

                return response()->json([
                    'status' => false,
                    'message' => 'Customer not registred on Enkpay',
                ], 500);
            }



            $status = Transaction::where('e_ref', $TransactionReference)->first()->status ?? null;

            if ($status == 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tranasaction alredy confimed',
                ], 500);
            }





            //check transaction
            $e_ref = Transaction::where('e_ref', $TransactionReference)
                ->first()->e_ref ?? null;

            if ($e_ref == null) {

                return response()->json([
                    'status' => false,
                    'e_ref' => $TransactionReference,
                    'message' => 'Tranasaction not found',
                ], 500);
            }

            $update =  Transaction::where('e_ref', $TransactionReference)
                ->update([
                    'status' => 1,
                ]);



            //Clear pending
            PendingTransaction::where('e_ref', $TransactionReference)->delete() ?? null;


            //update Transactions
            $amount4 = number_format($Amount, 2);
            $message = "$TransactionReference | NGN $amount4 left pool Account by  $full_name";
            send_notification($message);

            return response()->json([
                'status' => true,
                'message' => 'Tranasaction Successsfull',
            ], 200);
        }



        //completed



        //Reversal

        if ($request->ServiceCode == 'RFT1') {

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
            $DestinationAccountName = $request->AdditionalDetails['DestinationAccountName'] ?? null;
            $DestinationAccountNumber = $request->AdditionalDetails['DestinationAccountNumber'] ?? null;
            $DestinationBankName = $request->AdditionalDetails['DestinationBankName'] ?? null;



            $trx = Transaction::where('e_ref', $request->TransactionReference)->first() ?? null;

            if ($trx == null) {

                return response()->json([
                    'status' => false,
                    'message' => 'Transaction not found',
                ], 500);
            }

            if ($trx->status == 1) {

                return response()->json([
                    'status' => false,
                    'message' => 'Transaction is completed',
                ], 500);
            }

            if ($trx->status == 3) {

                return response()->json([
                    'status' => false,
                    'message' => 'Transaction has been reversed',
                ], 500);
            }

            $ch = Transaction::where('e_ref', $TransactionReference)->where('status', 0)->update([
                'status' => 3,
            ]);


            $wallet = User::where('id', $trx->user_id)->first()->main_wallet;
            $update = $trx->debit + $wallet;
            User::where('id', $trx->user_id)->update(['main_wallet' => $update]);





            $balance = User::where('id', $trx->user_id)->first()->main_wallet;
            $trasnaction = new Transaction();
            $trasnaction->user_id = $trx->user_id;
            $trasnaction->ref_trans_id = $trx->ref_trans_id;
            $trasnaction->e_ref = $trx->e_ref;
            $trasnaction->transaction_type = "Reversal";
            $trasnaction->debit = 0;
            $trasnaction->amount = $trx->debit;
            $trasnaction->serial_no = $SerialNumber;
            $trasnaction->title = "Reversal";
            $trasnaction->note = "Reversal | $DestinationAccountName | $DestinationAccountNumber";
            $trasnaction->fee = $Fee;
            $trasnaction->balance = $balance;
            $trasnaction->main_type = "Reversal";
            $trasnaction->status = 3;
            $trasnaction->save();


            $f_name = User::where('id', $trx->user_id)
                ->first()->first_name ?? null;

            $l_name = User::where('id', $trx->user_id)
                ->first()->last_name ?? null;

            $full_name = $f_name . " " . $l_name;

            PendingTransaction::where('e_ref', $TransactionReference)->delete() ?? null;



            $amount4 = number_format($trx->debit, 2);
            $message = "$TransactionReference | NGN $amount4 has been reversed to  $full_name";
            send_notification($message);




            return response()->json([
                'status' => true,
                'message' => 'Reversed Successsfully',
            ], 200);
        }
    }

    public function balance_webhook(Request $request)
    {

        // try {

        //$IP = $_SERVER['SERVER_ADDR'];

        $SerialNumber = $request->serial_number;
        $amount = $request->amount;
        $pin = $request->pin;
        $transaction_type = $request->transaction_type;
        $serviceCode = $request->serviceCode;
        $reference = $request->reference;

        $oip = env('ERIP');

        $trans_id = trx();

        //Get user ID
        $user_id = Terminal::where('serial_no', $SerialNumber)
            ->first()->user_id ?? null;

        if ($user_id == null) {

            return response()->json([
                'status' => false,
                'message' => 'Serial_no not found on our system',
            ], 500);
        }

        if ($transaction_type == 'inward') {

            //Get user ID
            $user_id = Terminal::where('serial_no', $SerialNumber)
                ->first()->user_id ?? null;

            $status = Terminal::where('serial_no', $SerialNumber)
                ->first()->transfer_status;

            $balance = User::where('id', $user_id)
                ->first()->main_wallet;

            $get_pin = User::where('id', $user_id)
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



             return response()->json([
                    'status' => $this->failed,
                    'message' => "Not Available",
            ], 500);

            $pos_trx = Feature::where('id', 1)->first()->pos_transfer ?? null;
            if ($pos_trx == 0) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Transfer is not available at the moment, \n\n Please try again after some time",
                ], 500);
            }


            //Get user ID
            $user_id = Terminal::where('serial_no', $SerialNumber)
                ->first()->user_id ?? null;

            $status = Terminal::where('serial_no', $SerialNumber)
                ->first()->transfer_status;

            $get_pin = User::where('id', $user_id)
                ->first()->pin;

            $transfer_fee = Charge::where('title', 'transfer_fee')
                ->first()->amount;

            $f_name = User::where('id', $user_id)
                ->first()->first_name ?? null;

            $l_name = User::where('id', $user_id)
                ->first()->last_name ?? null;

            $full_name = $f_name . " " . $l_name;

            if ($status == 1) {
                $check_agent_status = "Active";
            } else {
                $check_agent_status = "InActive";
            }


            $user_balance = User::where('id', $user_id)
                ->first()->main_wallet;
            //chk pin

            if (Hash::check($pin, $get_pin) == false) {

                return response()->json([

                    'is_pin_valid' => false,
                    'balance' => number_format($user_balance, 2),
                    'agent_status' => $check_agent_status,

                ]);
            }



            //check balance and debit
            $user_balance = User::where('id', $user_id)
                ->first()->main_wallet;


            //$debit_amount = $amount + $transfer_fee;

            $enkpayprofit = $transfer_fee - 10;

            if ($user_balance >= $amount) {

                $debit = $user_balance - $amount;

                User::where('id', $user_id)
                    ->update([
                        'main_wallet' => $debit,
                    ]);

                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $reference;
                $trasnaction->transaction_type = "EP TRANSFER";
                $trasnaction->debit = $amount;
                $trasnaction->amount = $amount;
                $trasnaction->title = "POS Transfer";
                $trasnaction->balance = $debit;
                $trasnaction->main_type = "Transfer";
                $trasnaction->serial_no = $SerialNumber;
                $trasnaction->enkPay_Cashout_profit = $enkpayprofit;
                $trasnaction->save();



                // //pending trnasaction
                $trasnaction = new PendingTransaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $reference;
                $trasnaction->debit = 0;
                $trasnaction->amount = 0;
                $trasnaction->bank_code = 0;
                $trasnaction->bank_code = "POS TRANSFER";
                $trasnaction->enkpay_Cashout_profit = 0;
                $trasnaction->receiver_name = "POS TRANSFER";
                $trasnaction->receiver_account_no = 0;
                $trasnaction->receiver_name = 0;
                $trasnaction->status = 1;
                $trasnaction->save();


                $amount4 = number_format($amount, 2);
                $message = "NGN $amount4 is about to leave your pool Account by $full_name using EP Transfer";
                send_notification($message);

                return response()->json([

                    'is_pin_valid' => true,
                    'balance' => number_format($user_balance, 2),
                    'agent_status' => $check_agent_status,

                ]);
            } else {

                return response()->json([

                    'is_pin_valid' => true,
                    'balance' => number_format($user_balance, 2),
                    'agent_status' => $check_agent_status,

                ]);
            }
        }



        if (($request->serviceCode == 'BAT1') ||  ($request->serviceCode == 'BAT2') || ($request->serviceCode == 'BAT3') || ($request->serviceCode == 'BAT4')

            || ($request->serviceCode == 'BMD1') || ($request->serviceCode == 'BMD2') || ($request->serviceCode == 'BMD3') || ($request->serviceCode == 'BMD4')

            || ($request->serviceCode == 'BMD5')
        ) {

            $pos_trx = Feature::where('id', 1)->first()->pos_transfer ?? null;
            if ($pos_trx == 0) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Transfer is not available at the moment, \n\n Please try again after some time",
                ], 500);
            }


            //Get user ID
            $user_id = Terminal::where('serial_no', $SerialNumber)
                ->first()->user_id ?? null;

            $status = Terminal::where('serial_no', $SerialNumber)
                ->first()->transfer_status;

            $get_pin = User::where('id', $user_id)
                ->first()->pin;

            $f_name = User::where('id', $user_id)
                ->first()->first_name ?? null;

            $l_name = User::where('id', $user_id)
                ->first()->last_name ?? null;

            $full_name = $f_name . " " . $l_name;

            if ($status == 1) {
                $check_agent_status = "Active";
            } else {
                $check_agent_status = "InActive";
            }


            $user_balance = User::where('id', $user_id)
                ->first()->main_wallet;
            //chk pin

            if (Hash::check($pin, $get_pin) == false) {

                return response()->json([

                    'is_pin_valid' => false,
                    'balance' => number_format($user_balance, 2),
                    'agent_status' => $check_agent_status,

                ]);
            }



            //check balance and debit
            $user_balance = User::where('id', $user_id)
                ->first()->main_wallet;


            //$debit_amount = $amount + $transfer_fee;


            if ($user_balance >= $amount) {

                $debit = $user_balance - $amount;

                User::where('id', $user_id)
                    ->update([
                        'main_wallet' => $debit,
                    ]);

                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $reference;
                $trasnaction->transaction_type = "EP Bills";
                $trasnaction->debit = $amount;
                $trasnaction->amount = $amount;
                $trasnaction->title = "EP Bills";
                $trasnaction->balance = $debit;
                $trasnaction->main_type = "EPvas";
                $trasnaction->serial_no = $SerialNumber;
                $trasnaction->enkPay_Cashout_profit = 0;
                $trasnaction->save();


                $amount4 = number_format($amount, 2);
                $message = "NGN $amount4 is about to leave your pool Account by $full_name using VAS POS";
                send_notification($message);

                return response()->json([

                    'is_pin_valid' => true,
                    'balance' => number_format($user_balance, 2),
                    'agent_status' => $check_agent_status,

                ]);
            } else {

                return response()->json([

                    'is_pin_valid' => true,
                    'balance' => number_format($user_balance, 2),
                    'agent_status' => $check_agent_status,

                ]);
            }
        }








        if ($serviceCode == 'BLE1') {



            $pos_trx = Feature::where('id', 1)->first()->pos_transfer ?? null;
            if ($pos_trx == 0) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => "Transfer is not available at the moment, \n\n Please try again after some time",
                ], 500);
            }


            //Get user ID
            $user_id = Terminal::where('serial_no', $SerialNumber)
                ->first()->user_id ?? null;

            $status = Terminal::where('serial_no', $SerialNumber)
                ->first()->transfer_status;




            $balance = User::where('id', $user_id)
                ->first()->main_wallet;

            $get_pin = User::where('id', $user_id)
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
    }













    public function transactiion_status(Request $request)
    {

        $erran_api_key = errand_api_key();
        $epkey = env('EPKEY');


        try {

            $ref_no = $request->ref_no;

            if ($ref_no == null) {

                return response()->json([

                    'status' => false,
                    'message' => 'Transaction Not Found',

                ], 500);
            }


            $e_ref = Transaction::where('ref_trans_id', $ref_no)
                ->first()->e_ref;

            $e_ref_status = Transaction::where('ref_trans_id', $ref_no)
                ->first()->status;

            $b_code = env('BCODE');



            if ($e_ref_status == 0) {


                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.errandpay.com/epagentservice/api/v1/GetStatus?reference=$e_ref&businessCode=A00088",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer $erran_api_key"
                    ),
                ));

                $var = curl_exec($curl);

                curl_close($curl);
                $var = json_decode($var);
                $status = $var->data->statusCode ?? null;


                if ($status == 00) {



                    // Transaction::where('ref_trans_id', $ref_no)->update([

                    //     'status' => 1,

                    // ]);
                }
            }


            $amount = Transaction::where('ref_trans_id', $ref_no)
                ->first()->amount;

            $receiver_bank = Transaction::where('ref_trans_id', $ref_no)
                ->first()->receiver_bank;

            $receiver_name = Transaction::where('ref_trans_id', $ref_no)
                ->first()->receiver_name;

            $note = Transaction::where('ref_trans_id', $ref_no)
                ->first()->note;

            $date = Transaction::where('ref_trans_id', $ref_no)
                ->first()->created_at;

            $receiver_account_no = Transaction::where('ref_trans_id', $ref_no)
                ->first()->receiver_account_no;

            $status = Transaction::where('ref_trans_id', $ref_no)
                ->first()->status;

            return response()->json([

                'status' => true,
                'e_ref' => $e_ref,
                'amount' => $amount,
                'receiver_bank' => $receiver_bank,
                'receiver_name' => $receiver_name,
                'receiver_account_no' => $receiver_account_no,
                'date' => $date,
                'note' => $note,
                'status' => $status,
                'message' => "If receiver is not credited within 10mins, Please contact us with the EREF ",


            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }



    public function wallet_check(Request $request)
    {

        try {

            $SerialNumber = $request->serial_number;
            $pin = $request->pin;
            $transaction_type = "inward";


            //Get user ID
            $user_id = Terminal::where('serial_no', $SerialNumber)
                ->first()->user_id ?? null;

            $status = Terminal::where('serial_no', $SerialNumber)
                ->first()->transfer_status;

            $balance = User::where('id', $user_id)
                ->first()->main_wallet;

            $get_pin = User::where('id', $user_id)
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
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }


    public function  get_terminals(request $request)
    {


        try {

            $amountpaid = Terminal::where('user_id', Auth::id())->first()->amount ?? 0;
            $terminals = Terminal::select('serial_no', 'description', 'transfer_status')->where('user_id', Auth::id())
                ->get();


            return response()->json([

                'status' => $this->success,
                'data' => $terminals,
                'amountpaid' => $amountpaid,


            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function  get_terminal_transaction(request $request)
    {


        try {


            $serial_no = strval($request->serial_no);

            $total_transactions = Transaction::where('serial_no', $serial_no)->get()
            ->sum('credit');


            $daily_transactions = Transaction::where('serial_no', $serial_no)
            ->whereday('created_at', Carbon::today())->sum('credit');

            $terminal = Terminal::where('user_id', Auth::id())
                ->get();

               $history = Transaction::latest()->select('*')
                ->where('serial_no', $serial_no)
                ->whereMonth('created_at', Carbon::now()->month)
                ->get();

            return response()->json([

                'status' => $this->success,
                'total_transactions' => $total_transactions,
                'daily_transactions' => (int)$daily_transactions,
                'history' => $history,



            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }




    public function move_money(){

        $pool_b = get_pool();



        if($pool_b < 25){

            $result = " Message========> Amount is less than NGN 10";
            send_notification($result);

        }

        if($pool_b > 250025){


            $erran_api_key = errand_api_key();

            $epkey = env('EPKEY');

            $curl = curl_init();
            $data = array(

                "amount" => 250000,
                "destinationAccountNumber" => "5401005443",
                "destinationBankCode" => "101",
                "destinationAccountName" => "Enkwave",

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


            $error = $var->error->message ?? null;
            $TransactionReference = $var->data->reference ?? null;
            $status = $var->code ?? null;


            if ($status == 200){

                $result = " Message========> 250,000 has been sent out". "\nRef ======> $TransactionReference";
                send_notification($result);
            }

            $result = " Message========> $error";
            send_notification($result);





        }

        if($pool_b < 250025){

            $amount = $pool_b - 25;

            $erran_api_key = errand_api_key();

            $epkey = env('EPKEY');

            $curl = curl_init();
            $data = array(

                "amount" => $amount,
                "destinationAccountNumber" => "5401005443",
                "destinationBankCode" => "101",
                "destinationAccountName" => "Enkwave",

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


            $error = $var->error->message ?? null;
            $TransactionReference = $var->data->reference ?? null;
            $status = $var->code ?? null;


            if ($status == 200){

                $result = " Message========> $amount has been sent out". "\nRef ======> $TransactionReference";
                send_notification($result);
            }

            $result = " Message========> $error";
            send_notification($result);


        }




    }



    public function test_transaction(request $request)
    {


                    // $username = env('MUSERNAME');
                    // $prkey = env('MPRKEY');
                    // $sckey = env('MSCKEY');

                    // $unixTimeStamp = timestamp();
                    // $sha = sha512($unixTimeStamp.$prkey);
                    // $authHeader = 'magtipon ' . $username . ':' . base64_encode(hex2bin($sha));



                    //$ref = sha512($refid.$prkey);

                    //$signature = base64_encode(hex2bin($ref));


                    // $databody = array(

                    //     "Amount" => 2000,
                    //     "RequestRef" => ,
                    //     "CustomerDetails" => array(
                    //         "Fullname" => "Manager App",
                    //         "MobilePhone" => "08063412603",
                    //         "Email" => "apimanager@magtipon.com"
                    //     ),
                    //     "BeneficiaryDetails" => array(
                    //         "Fullname" => "Manager App",
                    //         "MobilePhone" => "08063412603",
                    //         "Email" => "apimanager@magtipon.com"
                    //     ),
                    //     "BankDetails" => array(
                    //         "BankType" => "comm",
                    //         "BankCode" => "011",
                    //         "AccountNumber" => "1010101010",
                    //         "AccountType" => "10"
                    //     ),

                    //     "Signature" => $signature,
                    // );


                //$post_data = json_encode($databody);


                // $curl = curl_init();

                // curl_setopt_array($curl, array(
                //     CURLOPT_URL => 'http://magtipon.buildbankng.com/api/v1/banks',
                //     CURLOPT_RETURNTRANSFER => true,
                //     CURLOPT_ENCODING => '',
                //     CURLOPT_MAXREDIRS => 10,
                //     CURLOPT_TIMEOUT => 0,
                //     CURLOPT_FOLLOWLOCATION => true,
                //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //     CURLOPT_CUSTOMREQUEST => 'GET',
                //     //CURLOPT_POSTFIELDS => $post_data,
                //     CURLOPT_HTTPHEADER => array(
                //         "Authorization: $authHeader",
                //         "Timestamp: $unixTimeStamp",
                //         'Content-Type: application/json',
                //     ),
                // ));

                // $var = curl_exec($curl);
                // $result = json_decode($var);
                // $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                // curl_close($curl);

                // $banks = $result->Banks;


                // foreach ($banks as $item) {
                //     $apiData = new Ttmfb();
                //     $apiData->bankName = $item->Name;
                //     $apiData->code = $item->CbnCode;
                //     // Map API response fields to your database columns

                //     $apiData->save();
                // }


                $notificationId = ['c51503d4-b8ba-4cf3-88dd-6927ffc71071'];


                    $fieldsh['include_player_ids'] = $notificationId;

                    $notificationMsgi = 'Hello !! It is a notification test.!';

                    $result = OneSignal::sendPush($fieldsh, $notificationMsgi);

                    dd($result);





    }









        public function pending_transaction(request $request){

            Transfer::where('ref_trans_id', $request->ref_trans_id)->update(['status' => 0, 'e_ref' => $request->TransactionReference]);
            Transaction::where('ref_trans_id', $request->ref_trans_id)->update(['status' => 0, 'e_ref' => $request->TransactionReference]);
            PendingTransaction::where('ref_trans_id', $request->ref_trans_id)->delete();
            $user_id = PendingTransaction::where('ref_trans_id', $request->ref_trans_id)->first()->user_id ?? null;
            PendingTransaction::where('user_id', $user_id)->delete();



        }



        public function transfer_reverse(request $request){

        $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
        $User_wallet_banlance = User::where('id', $request->user_id)->first()->main_wallet;

        $credit = $User_wallet_banlance + $request->amount + $transfer_charges;
        $update = User::where('id', Auth::id())
                ->update([
                    'main_wallet' => $credit,
                ]);

        $trasnaction = new Transaction();
        $trasnaction->user_id = $request->user_id;
        $trasnaction->ref_trans_id = $request->ref_trans_id;
        $trasnaction->transaction_type = "Reversal";
        $trasnaction->debit = 0;
        $trasnaction->amount = $request->amount;
        $trasnaction->serial_no = 0;
        $trasnaction->title = "Reversal";
        $trasnaction->note = "Reversal";
        $trasnaction->fee = 25;
        $trasnaction->balance = $credit;
        $trasnaction->main_type = "Reversal";
        $trasnaction->status = 3;
        $trasnaction->save();

        PendingTransaction::where('user_id', $request->user_id)->delete() ?? null;


        $usr = User::where('id', $request->user_id)->first();
        $message = "Transaction reversed | from bank api";
        $full_name = $usr->first_name . "  " . $usr->last_name;


        $result = " Message========> " . $message . "\n\nCustomer Name========> " . $full_name;
        send_notification($result);



        }









    }

