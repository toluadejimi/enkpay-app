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

    dd($var);



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


    $IP = $request->ip();
    dd($IP);
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

    $oip = env('ERIP');




if($IP == $oip){



    if($StatusCode == 00){

        $main_wallet = User::where('terminal_id', $TerminalID)
        ->first()->main_wallet;

        $user_id = User::where('terminal_id', $TerminalID)
        ->first()->id;



        //credit
        $updated_amount = $main_wallet + $Amount;
        $main_wallet = User::where('terminal_id', $TerminalID)
        ->update([
            'main_wallet' => $updated_amount,
        ]);

        if($TransactionType == 'CashOut'){


        //update Transactions
        $trasnaction = new Transaction();
        $trasnaction->user_id = $user_id;
        $trasnaction->ref_trans_id = $SerialNumber;
        $trasnaction->transaction_type = $TransactionType;
        $trasnaction->debit = $Amount;
        $trasnaction->fee = $Fee;
        $trasnaction->balance = $updated_amount;
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
        'message' => 'Do not do that'
    ], 500);







} catch (\Exception $th) {
    return $th->getMessage();
}











}







}
