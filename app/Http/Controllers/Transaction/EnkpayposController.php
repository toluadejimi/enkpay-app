<?php

namespace App\Http\Controllers\Transaction;

use App\Models\TidConfig;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Charge;
use App\Models\PosLog;
use Defuse\Crypto\Key;
use App\Models\Terminal;
use Defuse\Crypto\Crypto;
use App\Models\SuperAgent;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EnkpayposController extends Controller
{
    //ENPAY POS



    public function enkpayPosLogs(request $request)
    {

        $message2 = json_encode($request->all());
        send_notification($message2);


        $key = $request->header('dataKey');
        $RRN = $request->RRN;
        $STAN = $request->STAN;
        $serialNO = $request->serialNO;
        $amount = $request->amount;
        $expireDate = $request->expireDate;
        $message = $request->message;
        $pan = $request->pan;
        $responseCode = $request->respCode;
        $terminalID = $request->terminalID;
        $transactionType = $request->transactionType;
        $cardName = $request->cardName;
        $userID = $request->UserID;
        $DataKey = env('DATAKEY');






        if ($key == null) {

            $result = "No Key Passed";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Empty Key',
            ], 500);
        }


        if ($key != $DataKey) {

            $result = "Invalid Key | $key";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Invalid Request',
            ], 500);
        }


        $userID = Terminal::where('serial_no', $serialNO)->first()->user_id ?? null;
        if ($userID == null) {


            $result = "No user found | for this serial $serialNO";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => "No user found with this serial | $serialNO",
            ], 500);
        }


        $rrn = PosLog::where('e_ref', $RRN)->first()->e_ref ?? null;

        if ($rrn == $RRN) {


            return response()->json([
                'status' => false,
                'message' => "Transaction already exist",
            ], 500);
        }





        //update Transactions
        $trasnaction = new PosLog();
        $trasnaction->user_id = $userID;
        $trasnaction->e_ref = $RRN;
        $trasnaction->transaction_type = $transactionType;
        $trasnaction->title = "POS Transaction Log";
        $trasnaction->amount = $amount;
        $trasnaction->sender_name = $pan;
        $trasnaction->serial_no = $terminalID;
        $trasnaction->sender_account_no = $pan;
        $trasnaction->status = 0;
        $trasnaction->note = "Initiated";
        $trasnaction->save();



        return response()->json([
            'status' => true,
            'message' => 'Log saved Successfully',
        ], 200);
    }

    public function enkpayPos(request $request)
    {


        $message2 = "POS====>>>>". json_encode($request->all());
        send_notification($message2);

        $key = $request->header('dataKey');
        $RRN = $request->RRN;
        $userID = $request->UserID;
        $serialNO = $request->serialNO;
        $STAN = $request->STAN;
        $tamount = $request->amount;
        $expireDate = $request->expireDate;
        $message = $request->responseMessage;
        $pan = $request->pan;
        $responseCode = $request->respCode;
        $terminalID = $request->terminalID;
        $transactionType = $request->transactionType;
        $cardName = $request->cardName;
        $DataKey = env('DATAKEY');







        $amount = PosLog::where('e_ref', $RRN)->first()->amount ?? null;



        if($amount ==  null){


            if ($key == null) {

                $result = "No Key Passed";
                send_notification($result);

                return response()->json([
                    'status' => false,
                    'message' => 'Empty Key',
                ], 500);
            }


            if ($key != $DataKey) {

                $result = "Invalid Key | $key";
                send_notification($result);

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Request',
                ], 500);
            }



            $userID = Terminal::where('serial_no', $serialNO)->first()->user_id ?? null;
            if ($userID == null) {

                $result = "No user found | for this serial $serialNO";
                send_notification($result);

                return response()->json([
                    'status' => false,
                    'message' => "No user found with this serial | $serialNO",
                ], 500);
            }




            $trans_id = trx();
            $comission = Charge::where('title', 'both_commission')
                ->first()->amount;

            $user_id = $userID;

            $main_wallet = User::where('id', $user_id)->first()->main_wallet ?? null;
            $type = User::where('id', $user_id)->first()->type ?? null;
            $businessID = Terminal::where('serial_no', $serialNO)->first()->business_id ?? null;
            $super_agent = User::where('business_id', $businessID)->first() ?? null;



            if ($responseCode == 00 && $super_agent != null) {
                if ($super_agent != null) {

                    if ($main_wallet == null && $user_id == null) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Customer not registered on Enkpay',
                        ], 500);
                    }

                    $super_agent_pos_charge = SuperAgent::where('user_id', $super_agent->id)->first()->pos_charge ?? null;
                    $register_under_id = SuperAgent::where('user_id', $super_agent->id)->first()->register_under_id ?? null;
                    $main_pos_charge = Charge::where('user_id', $super_agent->id)->where('title', 'pos_charge')->first()->amount ?? null;
                    $both_commissions =  $super_agent_pos_charge + $main_pos_charge;
                    $amount1 = $both_commissions / 100;
                    $amount2 = $amount1 * $tamount;
                    $both_commmission = round($amount2, 2);


                    $samount1 = $super_agent_pos_charge / 100;
                    $samount2 = $samount1 * $tamount;
                    $scommmission = round($samount2, 2);


                    $eamount1 = $main_pos_charge / 100;
                    $eamount2 = $eamount1 * $tamount;
                    $ecommmission = round($eamount2, 2);


                    $business_commission_cap = Charge::where('title', 'business_cap')
                        ->first()->amount;

                    $agent_commission_cap = Charge::where('title', 'agent_cap')
                        ->first()->amount;



                    if ($both_commmission >= 200) {
                        $amount_after_comission = $tamount - 200;
                        $samount_after_comission = 50;
                        $enkpay_profit = 150;
                    } else {

                        $amount_after_comission = $tamount - $both_commmission;
                        $samount_after_comission = $scommmission;
                        $enkpay_profit = $ecommmission;
                    }






                    $updated_amount = $main_wallet + $amount_after_comission;

                    $status = PosLog::where('e_ref', $RRN)->first()->status ?? null;
                    if ($status == 2) {

                        return response()->json([
                            'status' => false,
                            'message' => 'Transaction already completed',
                        ], 500);
                    }




                    User::where('id', $user_id)->increment('main_wallet', $amount_after_comission);
                    User::where('id', $super_agent->id)->increment('main_wallet', (int)$samount_after_comission);
                    $balance = User::where('id', $super_agent->id)->first()->main_wallet;

                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $super_agent->id;
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $RRN;
                    $trasnaction->transaction_type = $transactionType;
                    $trasnaction->credit = round($samount_after_comission, 2);
                    $trasnaction->title = "Commission";
                    $trasnaction->note = "ENKPAY POS | Commission";
                    $trasnaction->amount = $tamount;
                    $trasnaction->balance = $balance;
                    $trasnaction->serial_no = $terminalID;
                    $trasnaction->sender_account_no = $pan;
                    $trasnaction->status = 1;
                    $trasnaction->save();

                    $user = User::where('id', $super_agent->id)->first();
                    $result = $user->first_name . " " . $user->last_name . "| got NGN " . $samount_after_comission;
                    send_notification($result);


                    PosLog::where('e_ref', $RRN)->update([

                        'status' => 2,
                        'note' => "Successful | $pan | $tamount"

                    ]);


                    //update Transactions
                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $user_id;
                    $trasnaction->ref_trans_id = $trans_id;
                    $trasnaction->e_ref = $RRN;
                    $trasnaction->transaction_type = $transactionType;
                    $trasnaction->credit = round($amount_after_comission, 2);
                    $trasnaction->e_charges = $enkpay_profit;
                    $trasnaction->title = "POS Transaction";
                    $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message";
                    $trasnaction->amount = $tamount;
                    $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
                    $trasnaction->e_charges = round($samount_after_comission, 2);
                    $trasnaction->balance = $updated_amount;
                    $trasnaction->sender_name = $pan;
                    $trasnaction->serial_no = $terminalID;
                    $trasnaction->sender_account_no = $pan;
                    $trasnaction->register_under_id = $register_under_id;
                    $trasnaction->status = 1;
                    $trasnaction->save();


                    $f_name = User::where('id', $user_id)->first()->first_name ?? null;
                    $l_name = User::where('id', $user_id)->first()->last_name ?? null;

                    $ip = $request->ip();
                    $amount4 = number_format($amount_after_comission, 2);
                    $message = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | using ENKPPAY POS" . "\n\nIP========> " . $ip;
                    $parametersJson = json_encode($request->all());
                    $result = "Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
                    send_notification($result);

                    return response()->json([
                        'status' => true,
                        'message' => 'Transaction Successful',
                    ], 200);
                }
            }


            if ($main_wallet == null && $user_id == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer not registered on Enkpay',
                ], 500);
            }

            //Both Commission
            $amount1 = $comission / 100;
            $amount2 = $amount1 * $tamount ?? $tamount;
            $both_commmission = number_format($amount2, 3);


            $business_commission_cap = Charge::where('title', 'business_cap')
                ->first()->amount;

            $agent_commission_cap = Charge::where('title', 'agent_cap')
                ->first()->amount;

            if ($both_commmission >= $agent_commission_cap && $type == 1) {

                $removed_comission = $tamount - $agent_commission_cap;

                $enkpay_profit = $agent_commission_cap - 75;
            } elseif ($both_commmission >= $business_commission_cap && $type == 3) {

                $removed_comission = $tamount - $business_commission_cap;

                $enkpay_profit = $business_commission_cap - 75;
            } else {

                $removed_comission = $tamount - $both_commmission;

                $enkpay_profit = $both_commmission;
            }




            if ($responseCode == 00) {

                $updated_amount = $main_wallet + $removed_comission;

                User::where('id', $user_id)->increment('main_wallet', $removed_comission);

                PosLog::where('e_ref', $RRN)->update([

                    'status' => 1,
                    'note' => "Successful | $pan | $tamount "

                ]);





                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $RRN;
                $trasnaction->transaction_type = $transactionType;
                $trasnaction->credit = round($removed_comission, 2);
                $trasnaction->e_charges = $enkpay_profit;
                $trasnaction->title = "POS Transasction";
                $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message";
                $trasnaction->amount = $tamount;
                $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
                $trasnaction->balance = $updated_amount;
                $trasnaction->sender_name = $pan;
                $trasnaction->serial_no = $terminalID;
                $trasnaction->sender_account_no = $pan;
                $trasnaction->status = 1;
                $trasnaction->save();



                $f_name = User::where('id', $user_id)->first()->first_name ?? null;
                $l_name = User::where('id', $user_id)->first()->last_name ?? null;

                $ip = $request->ip();
                $amount4 = number_format( $tamount, 2);
                $result = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | using ENKPPAY POS" . "\n\nIP========> " . $ip;
                send_notification($result);



                return response()->json([
                    'status' => true,
                    'message' => 'Transaction Successful',
                ], 200);
            } else {
                //update Transactions

                PosLog::where('e_ref', $RRN)->update([

                    'status' => 4,
                    'note' => "Failed | $message"

                ]);


                $under_id = User::where('id', $user_id)->first()->register_under_id ?? null;

                $trasnaction = new Transaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->register_under_id = $under_id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $RRN;
                $trasnaction->transaction_type = $transactionType;
                $trasnaction->credit = 0;
                $trasnaction->e_charges = $enkpay_profit;
                $trasnaction->title = "POS Transasction";
                $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message  ";
                $trasnaction->amount = $amount;
                $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
                $trasnaction->balance = 0;
                $trasnaction->sender_name = $pan;
                $trasnaction->serial_no = $terminalID;
                $trasnaction->sender_account_no = $pan;
                $trasnaction->status = 4;
                $trasnaction->save();

                $f_name = User::where('id', $user_id)->first()->first_name ?? null;
                $l_name = User::where('id', $user_id)->first()->last_name ?? null;

                $ip = $request->ip();
                $amount4 = number_format($removed_comission, 2);
                $message = $f_name . " " . $l_name . "| fund NGN " . $amount . " | Failed on ENKPAY POS" . "\n\nIP========> " . $ip;
                $parametersJson = json_encode($request->all());
                $result = "Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);


                return response()->json([
                    'status' => false,
                    'message' => 'Transaction Failed',
                ], 500);
            }



        }


        if ($key == null) {

            $result = "No Key Passed";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Empty Key',
            ], 500);
        }


        if ($key != $DataKey) {

            $result = "Invalid Key | $key";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Invalid Request',
            ], 500);
        }



        $userID = Terminal::where('serial_no', $serialNO)->first()->user_id ?? null;
        if ($userID == null) {

            $result = "No user found | for this serial $serialNO";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => "No user found with this serial | $serialNO",
            ], 500);
        }


        $trans_id = trx();
        $comission = Charge::where('title', 'both_commission')
            ->first()->amount;


        $user_id = $userID;

        $main_wallet = User::where('id', $user_id)
            ->first()->main_wallet ?? null;

        $type = User::where('id', $user_id)
            ->first()->type ?? null;

        $businessID = Terminal::where('serial_no', $serialNO)->first()->business_id ?? null;
        $super_agent = User::where('business_id', $businessID)->first() ?? null;



        if ($responseCode == 00 && $super_agent != null) {
            if ($super_agent != null) {

                if ($main_wallet == null && $user_id == null) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Customer not registered on Enkpay',
                    ], 500);
                }

                $super_agent_pos_charge = SuperAgent::where('user_id', $super_agent->id)->first()->pos_charge ?? null;
                $register_under_id = SuperAgent::where('user_id', $super_agent->id)->first()->register_under_id ?? null;
                $main_pos_charge = Charge::where('user_id', $super_agent->id)->where('title', 'pos_charge')->first()->amount ?? null;
                $both_commissions =  $super_agent_pos_charge + $main_pos_charge;
                $amount1 = $both_commissions / 100;
                $amount2 = $amount1 * $amount;
                $both_commmission = round($amount2, 2);


                $samount1 = $super_agent_pos_charge / 100;
                $samount2 = $samount1 * $amount;
                $scommmission = round($samount2, 2);


                $eamount1 = $main_pos_charge / 100;
                $eamount2 = $eamount1 * $amount;
                $ecommmission = round($eamount2, 2);


                $business_commission_cap = Charge::where('title', 'business_cap')
                    ->first()->amount;

                $agent_commission_cap = Charge::where('title', 'agent_cap')
                    ->first()->amount;



                if ($both_commmission >= 200) {
                    $amount_after_comission = $amount - 200;
                    $samount_after_comission = 50;
                    $enkpay_profit = 150;
                } else {

                    $amount_after_comission = $amount - $both_commmission;
                    $samount_after_comission = $scommmission;
                    $enkpay_profit = $ecommmission;
                }


                $updated_amount = $main_wallet + $amount_after_comission;



                $status = PosLog::where('e_ref', $RRN)->first()->status ?? null;

                if ($status == 2) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Transaction already completed',
                    ], 500);
                }



                User::where('id', $user_id)
                    ->update([
                        'main_wallet' => $updated_amount,
                    ]);

                User::where('id', $super_agent->id)->increment('main_wallet', (int)$samount_after_comission);
                $balance = User::where('id', $super_agent->id)->first()->main_wallet;

                $trasnaction = new Transaction();
                $trasnaction->user_id = $super_agent->id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $RRN;
                $trasnaction->transaction_type = $transactionType;
                $trasnaction->credit = round($samount_after_comission, 2);
                $trasnaction->title = "Commission";
                $trasnaction->note = "ENKPAY POS | Commission";
                $trasnaction->amount = $samount_after_comission;
                $trasnaction->balance = $balance;
                $trasnaction->serial_no = $terminalID;
                $trasnaction->sender_account_no = $pan;
                $trasnaction->status = 1;
                $trasnaction->save();

                $user = User::where('id', $super_agent->id)->first();


                $result = $user->first_name . " " . $user->last_name . "| got NGN " . $samount_after_comission;
                send_notification($result);


                PosLog::where('e_ref', $RRN)->update([

                    'status' => 2,
                    'note' => "Successful | $pan | $amount"

                ]);


                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->ref_trans_id = $trans_id;
                $trasnaction->e_ref = $RRN;
                $trasnaction->transaction_type = $transactionType;
                $trasnaction->credit = round($amount_after_comission, 2);
                $trasnaction->e_charges = $enkpay_profit;
                $trasnaction->title = "POS Transaction";
                $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message";
                $trasnaction->amount = $amount;
                $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
                $trasnaction->e_charges = round($samount_after_comission, 2);
                $trasnaction->balance = $updated_amount;
                $trasnaction->sender_name = $pan;
                $trasnaction->serial_no = $terminalID;
                $trasnaction->sender_account_no = $pan;
                $trasnaction->register_under_id = $register_under_id;
                $trasnaction->status = 1;
                $trasnaction->save();


                $f_name = User::where('id', $user_id)->first()->first_name ?? null;
                $l_name = User::where('id', $user_id)->first()->last_name ?? null;

                $ip = $request->ip();
                $amount4 = number_format($amount_after_comission, 2);
                $message = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | using ENKPPAY POS" . "\n\nIP========> " . $ip;
                $parametersJson = json_encode($request->all());
                $result = "Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
                send_notification($result);

                return response()->json([
                    'status' => true,
                    'message' => 'Transaction Successful',
                ], 200);
            }
        }


        if ($main_wallet == null && $user_id == null) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not registered on Enkpay',
            ], 500);
        }

        //Both Commission
        $amount1 = $comission / 100;
        $amount2 = $amount1 * $amount ?? $tamount;
        $both_commmission = number_format($amount2, 3);


        $business_commission_cap = Charge::where('title', 'business_cap')
            ->first()->amount;

        $agent_commission_cap = Charge::where('title', 'agent_cap')
            ->first()->amount;

        if ($both_commmission >= $agent_commission_cap && $type == 1) {

            $removed_comission = $amount ?? $tamount - $agent_commission_cap;

            $enkpay_profit = $agent_commission_cap - 75;
        } elseif ($both_commmission >= $business_commission_cap && $type == 3) {

            $removed_comission = $amount ?? $tamount - $business_commission_cap;

            $enkpay_profit = $business_commission_cap - 75;
        } else {

            $removed_comission = $amount ?? $tamount - $both_commmission;

            $enkpay_profit = $both_commmission;
        }




        if ($responseCode == 00) {

            $updated_amount = $main_wallet + $removed_comission;

            $main_wallet = User::where('id', $user_id)
                ->update([
                    'main_wallet' => $updated_amount,
                ]);


            $amttt = $amount ?? $tamount;

            PosLog::where('e_ref', $RRN)->update([

                'status' => 1,
                'note' => "Successful | $pan | $amttt "

            ]);





            //update Transactions
            $trasnaction = new Transaction();
            $trasnaction->user_id = $user_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->e_ref = $RRN;
            $trasnaction->transaction_type = $transactionType;
            $trasnaction->credit = round($removed_comission, 2);
            $trasnaction->e_charges = $enkpay_profit;
            $trasnaction->title = "POS Transasction";
            $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message";
            $trasnaction->amount = $amount ?? $tamount;
            $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
            $trasnaction->balance = $updated_amount;
            $trasnaction->sender_name = $pan;
            $trasnaction->serial_no = $terminalID;
            $trasnaction->sender_account_no = $pan;
            $trasnaction->status = 1;
            $trasnaction->save();



            $f_name = User::where('id', $user_id)->first()->first_name ?? null;
            $l_name = User::where('id', $user_id)->first()->last_name ?? null;

            $ip = $request->ip();
            $amount4 = number_format($removed_comission ?? $tamount, 2);
            $result = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | using ENKPPAY POS" . "\n\nIP========> " . $ip;
            send_notification($result);



            return response()->json([
                'status' => true,
                'message' => 'Transaction Successful',
            ], 200);
        } else {
            //update Transactions

            PosLog::where('e_ref', $RRN)->update([

                'status' => 4,
                'note' => "Failed | $message"

            ]);


            $under_id = User::where('id', $user_id)->first()->register_under_id ?? null;

            $trasnaction = new Transaction();
            $trasnaction->user_id = $user_id;
            $trasnaction->register_under_id = $under_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->e_ref = $RRN;
            $trasnaction->transaction_type = $transactionType;
            $trasnaction->credit = 0;
            $trasnaction->e_charges = $enkpay_profit;
            $trasnaction->title = "POS Transasction";
            $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message  ";
            $trasnaction->amount = $amount;
            $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
            $trasnaction->balance = 0;
            $trasnaction->sender_name = $pan;
            $trasnaction->serial_no = $terminalID;
            $trasnaction->sender_account_no = $pan;
            $trasnaction->status = 4;
            $trasnaction->save();

            $f_name = User::where('id', $user_id)->first()->first_name ?? null;
            $l_name = User::where('id', $user_id)->first()->last_name ?? null;

            $ip = $request->ip();
            $amount4 = number_format($removed_comission, 2);
            $message = $f_name . " " . $l_name . "| fund NGN " . $amount . " | Failed on ENKPAY POS" . "\n\nIP========> " . $ip;
            $parametersJson = json_encode($request->all());
            $result = "Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
            send_notification($result);


            return response()->json([
                'status' => false,
                'message' => 'Transaction Failed',
            ], 500);
        }
    }


    public function eod_transactions(request $request)
    {


        if ($request->date == null || $request->user_id == null) {


            return response()->json([
                'status' => false,
                'message' => "Date or User_id Can not be null"

            ], 500);
        }



        $today = $request->date;
        $transaction = Transaction::select('e_ref', 'amount', 'sender_name', 'created_at', 'status')->where('user_id', $request->user_id)->whereDate('created_at', $today)->get();
        $terminalNo = Terminal::where('user_id', $request->user_id)->first()->serial_no;
        $merchantName = Terminal::where('user_id', $request->user_id)->first()->merchantName;
        $merchantNo = Terminal::where('user_id', $request->user_id)->first()->merchantNo;
        $totalTransaction = Transaction::where('user_id', $request->user_id)->whereDate('created_at', $today)->count();
        $totalSuccess = Transaction::whereDate('created_at', $today)
            ->where([
                'user_id' => $request->user_id,
                'status' => 1
            ])->count();



        $totalFail = Transaction::whereDate('created_at', $today)
            ->where([
                'user_id' => $request->user_id,
                'status' => 4
            ])->count();

        $totalPurchaseAmount = Transaction::whereDate('created_at', $today)
            ->where([
                'user_id' => $request->user_id,
                'status' => 1
            ])->sum('amount');





        return response()->json([
            'status' => true,
            'reportDatetime' => date('Y-m-d h:i:s'),
            'terminalNo' => $terminalNo,
            'merchantName' => $merchantName,
            'merchantNo' => $merchantNo,
            'totalTransaction' => (int)$totalTransaction,
            'totalSuccess' => $totalSuccess,
            'totalFail' => $totalFail,
            'totalPurchaseAmount' => $totalPurchaseAmount,
            'transaction' => $transaction

        ], 200);
    }



    public function register_pos(request $request)
    {

        $ck_tid =  TidConfig::where('serial_no', $request->serial_no)->first() ?? null;
        if($ck_tid == null){

            $tid = new TidConfig();
            $tid->serial_no = $request->serial_no;
            $tid->terminal_id = $request->tid;
            $tid->user_id = 1;
            $tid->save();

            return response()->json([
                'status' => true,
                'message' => "Pos added successfully"
            ], 200);

        }else{
            return response()->json([
                'status' => false,
                'message' => "pos already exist"
            ], 200);

        }



    }


    public function get_details(request $request)
    {

        $SerialNo = $request->header('serialnumber');
        if($SerialNo == null){
            return response()->json([
                'status' => false,
                'message' => "Serial no can not be null"
            ], 422);

        }

        $get_d = TidConfig::where('serial_no', $SerialNo)->first() ?? null;
        if($get_d != null){
            $details = TidConfig::where('serial_no', $SerialNo)->first()->makeHidden(['created_at', 'updated_at']) ?? null;
            return response()->json([
                'status' => true,
                'terminal' => $details,
            ], 200);
        }else{

            return response()->json([
                'status' => false,
                'message' => "Terminal has not be set on config"

            ], 422);
        }


    }



}
