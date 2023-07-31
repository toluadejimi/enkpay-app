<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Terminal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class EnkpayposController extends Controller
{
    //ENPAY POS

    public function enkpayPos(request $request)
    {


        $encryptedStr = $request->data;

        $encrypted =  decryption($encryptedStr);
        $resust = json_decode($encrypted);
        $jsonData = rtrim($encrypted, "\x04");



        $jsonString = iconv('UTF-8', 'ISO-8859-1//IGNORE', $jsonData);
        $decodedData = json_decode($jsonString, true);

        dd($encrypted, $decodedData);


        $RRN = $resust->RRN;

       


        $Amount = $request->amount;
        $cardName = $request->cardName;
        $deviceNO = $request->deviceNO;
        $MaskedPAN = $request->pan;
        $SerialNumber = $request->terminalID;
        $TransactionType = $request->transactionType;
        $TransactionReference = $request->RRN;
        $stan = $request->STAN;
        $id = $request->id;
        $status = $request->status;
        $response_code = $request->responseCode;
        $ServiceCode = $request->ServiceCode;



        //     "merchantNo": "23345566",
        //   "terminalNo": "8767B834",
        //   "merchantName": "ENKWAVE SOLUTION LTD",
        //   "deviceSN": "12345677",

        // $eip = env('EIP');
        //$eip = '127.0.0.1';

        $trans_id = "ENK-" . random_int(100000, 999999);

        //$verify1 = hash('sha512', $key);

        $comission = Charge::where('title', 'both_commission')
            ->first()->amount;

        // if ($eip == $ip) {

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

            $removed_comission = $Amount - $both_commmission;

            $enkpay_profit = $both_commmission - $errandPay_commission_amount;
        }

        //$enkpay_cashOut_fee = $amount - $enkpay_commision_amount ;

        $updated_amount = $main_wallet + $removed_comission;

        $main_wallet = User::where('id', $user_id)
            ->update([
                'main_wallet' => $updated_amount,
            ]);

        if ($TransactionType == 'PURCHASE') {

            //update Transactions
            $trasnaction = new Transaction();
            $trasnaction->user_id = $user_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->e_ref = $TransactionReference;
            $trasnaction->transaction_type = $TransactionType;
            $trasnaction->credit = round($removed_comission, 2);
            $trasnaction->e_charges = $enkpay_profit;
            $trasnaction->title = "POS Transasction";
            $trasnaction->note = "ENKPAY POS | $MaskedPAN | $cardName ";
            $trasnaction->amount = $Amount;
            $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
            $trasnaction->balance = $updated_amount;
            $trasnaction->sender_name = $cardName;
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
            'message' => 'Transaction Successful',
        ], 200);
        // } else {

        // $parametersJson = json_encode($request->all());
        // $headers = json_encode($request->headers->all());
        // $message = 'Key not Authorized';
        // $ip = $request->ip();

        // $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
        // send_notification($result);

        // return response()->json([
        //     'status' => false,
        //     'message' => 'Key not Authorized',
        // ], 401);
        // }
    }
}
