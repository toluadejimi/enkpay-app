<?php

namespace App\Http\Controllers\Transaction;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Laravel\Passport\Passport;
use Laravel\Passport\HasApiTokens;
use Mail;



class TransactionController extends Controller
{

    public $success = true;
    public $failed = false;



public function cash_out(Request $request){

try {
    $erran_api_key = errand_api_key();


    $beneficiaryName = $request->beneficiaryName;
    $bankName = $request->bankName;
    $bankCode = $request->bankCode;
    $beneficiaryAccount = $request->beneficiaryAccount;
    $amount = $request->amount;
    $institutionCode = $request->institutionCode;
    $referenceCode = "ENK-".random_int(1000000, 999999999);




        $curl = curl_init();
        $data = array(

                "beneficiaryName" => $beneficiaryName,
                "bankName" => $bankName ,
                "bankCode" =>  $bankCode,
                "beneficiaryAccount" => $beneficiaryAccount,
                "amount" => $beneficiaryAccount,
                "referenceCode" => $referenceCode,
                "institutionCode" =>  $institutionCode


        );

        $post_data = json_encode($data);

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/PayBeneficiary',
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
            'Content-Type: application/json'
        ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);

            $response1 = $var->data ?? null;
            $respose2 = 'ERA 001 Please try again later';

            if($var->code == 200){

                return response()->json([
                    'status' => $this->success,
                    'data' => $response1
                ], 200);

            }

            return response()->json([
                'status' => $this->failed,
                'data' => $response2
            ], 500);







    $main_account = main_account();

    if($main_account < $amount){
        return response()->json([
            'status' => $this->failed,
                'message' => 'Insufficent Funds'
            ], 500);
    }









} catch (\Exception $th) {
    return $th->getMessage();
}

}


public function get_banks(){

try {

    $erran_api_key = errand_api_key();

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/GetAllBanks',
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





    $code = $var->code ?? null;

        $response1 = $var->data ?? null;
        $respose2 = 'ERA 001 Please try again later';


        if($code == null){

            return response()->json([
                'status' => $this->failed,
                'data' => $erran_api_key
            ], 500);


        }elseif($var->code == 200){

            return response()->json([
                'status' => $this->success,
                'data' => $response1
            ], 200);

        }else{
            return response()->json([
                'status' => $this->failed,
                'data' => $response2
            ], 500);
        }





} catch (\Exception $th) {
    return $th->getMessage();
}

}





public function cash_out_webhook(Request $request){


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
                $Fee = $request->Fee;
                $PostingType = $request->PostingType;
                $TerminalID = $request->AdditionalDetails['TerminalID'];

                $key = env('ERIP');
                $trans_id = "ENK-".random_int(100000, 999999);
                $verify1 = hash('sha512', $key);



                if($verify1 == $header){



                    if($StatusCode == 00){

                        $main_wallet = User::where('serial_no', $SerialNumber)
                        ->first()->main_wallet;

                        $user_id = User::where('serial_no', $SerialNumber)
                        ->first()->id;



                        //credit
                        $updated_amount = $main_wallet + $Amount;
                        $main_wallet = User::where('serial_no', $SerialNumber)
                        ->update([
                            'main_wallet' => $updated_amount,
                        ]);

                        if($TransactionType == 'CashOut'){


                        //update Transactions
                        $trasnaction = new Transaction();
                        $trasnaction->user_id = $user_id;
                        $trasnaction->ref_trans_id = $trans_id;
                        $trasnaction->transaction_type = $TransactionType;
                        $trasnaction->debit = $Amount;
                        $trasnaction->fee = $Fee;
                        $trasnaction->balance = $updated_amount;
                        $trasnaction->terminal_id = $TerminalID;
                        $trasnaction->serial_no = $SerialNumber;
                        $trasnaction->status = 1;
                        $trasnaction->save();

                        }

                        return response()->json([
                            'status' => true,
                            'message' => 'Tranasaction Successsfull'
                        ], 200);




                    }

                }

                    return response()->json([
                        'status'  => false,
                        'message' => 'Key not Authorized'
                    ], 500);







                } catch (\Exception $th) {
                    return $th->getMessage();
            }




}


public function balance_webhook(Request $request){


            try {



                $IP = $_SERVER['SERVER_ADDR'];

                $serial_number = $request->serial_number;
                $amount = $request->amount;
                $pin = $request->pin;
                $transaction_type = $request->transaction_type;
                $serviceCode = $request->serviceCode;
                $reference = $request->reference;

                $oip = env('ERIP');

                $trans_id = "ENK-".random_int(100000, 999999);


                $user_id = User::where('serial_no', $serial_number)
                ->first()->id ?? null;

                if($user_id == null){

                    return response()->json([
                        'status'  => false,
                        'message' => 'Serial_no not found on our system'
                    ], 500);

                }


                if($serviceCode == 'CO1'){


                    $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;


                    $balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;


                    $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;


                    if($status == 1){
                        $agent_status = "Active";
                    }else{
                        $agent_status = "InActive";

                    }



                    if (Hash::check($pin, $get_pin)) {
                        $is_pin_valid = true;
                    }else{
                        $is_pin_valid = false;
                    }


                    return response()->json([

                        'is_pin_valid' => $is_pin_valid,
                        'balance' => number_format($balance, 2),
                        'agent_status' => $agent_status

                    ]);


                }


                if($serviceCode == 'FT1'){


                    $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;


                    $balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;


                    $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;


                    if($status == 1){
                        $agent_status = "Active";
                    }else{
                        $agent_status = "InActive";

                    }



                    if (Hash::check($pin, $get_pin)) {
                        $is_pin_valid = true;
                    }else{
                        $is_pin_valid = false;
                    }


                    if($is_pin_valid == true){


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
                        'agent_status' => $agent_status

                    ]);


                }

                if($serviceCode == 'BLE1'){


                    $status = User::where('serial_no', $serial_number)
                    ->first()->is_active;


                    $balance = User::where('serial_no', $serial_number)
                    ->first()->main_wallet;


                    $get_pin = User::where('serial_no', $serial_number)
                    ->first()->pin;


                    if($status == 1){
                        $agent_status = "Active";
                    }else{
                        $agent_status = "InActive";

                    }



                    if (Hash::check($pin, $get_pin)) {
                        $is_pin_valid = true;
                    }else{
                        $is_pin_valid = false;
                    }


                    return response()->json([

                        'is_pin_valid' => $is_pin_valid,
                        'balance' => number_format($balance, 2),
                        'agent_status' => $agent_status

                    ]);


                }







                } catch (\Exception $th) {
                    return $th->getMessage();
            }




}



public function transactiion_status(Request $request){

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





    } catch (\Exception $th) {
        return $th->getMessage();
    }

    }

















public function fund_transfer_webhook(Request $request){

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






        } catch (\Exception $th) {
            return $th->getMessage();
        }

    }




    public function wallet_check(Request $request){




        try{


            $serial_number = $request->serial_number;
            $pin = $request->pin;
            $transaction_type = "inward";



            $status = User::where('serial_no', $serial_number)
            ->first()->is_active;


            $balance = User::where('serial_no', $serial_number)
            ->first()->main_wallet;


            $get_pin = User::where('serial_no', $serial_number)
            ->first()->pin;


            if($status == 1){
                $agent_status = "Active";
            }else{
                $agent_status = "InActive";

            }



            if (Hash::check($pin, $get_pin)) {
                $is_pin_valid = true;
            }else{
                $is_pin_valid = false;
            }


            return response()->json([

                'status' => true,
                'is_pin_valid' => $is_pin_valid,
                'balance' => number_format($balance, 2),
                'agent_status' => $agent_status

            ]);








        } catch (\Exception $th) {
            return $th->getMessage();
        }





    }




}






