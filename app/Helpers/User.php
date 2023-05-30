<?php

use App\Models\AccountInfo;
use App\Models\ErrandKey;
use App\Models\ProvidusBank;
use App\Models\Setting;
use App\Models\User;
use App\Models\VfdBank;
use Illuminate\Support\Facades\Auth;

if (!function_exists('main_account')) {

    function main_account()
    {
        $user = Auth::user();
        return $user->main_wallet;
    }
}

if (!function_exists('user_status')) {

    function user_status()
    {
        $user = Auth::user();
        return $user->status;
    }
}

if (!function_exists('bonus_account')) {

    function bonus_account()
    {
        $user = Auth::user();
        return $user->bonus_wallet;
    }
}

if (!function_exists('user_email')) {

    function user_email()
    {
        $user = Auth::user();
        return $user->email;
    }
}

if (!function_exists('user_phone')) {

    function user_phone()
    {
        $user = Auth::user();
        return $user->phone;
    }
}

if (!function_exists('user_bvn')) {

    function user_bvn()
    {
        $user = Auth::user();
        return $user->bvn;
    }
}

if (!function_exists('first_name')) {

    function first_name()
    {
        $user = Auth::user();
        return $user->first_name;
    }
}

if (!function_exists('last_name')) {

    function last_name()
    {
        $user = Auth::user();
        return $user->last_name;
    }
}

if (!function_exists('user_status')) {

    function user_status()
    {
        $user = Auth::user();
        return $user->status;
    }
}

if (!function_exists('select_account')) {

    function select_account()
    {



        $account = User::where('id', Auth::id())->first();

        //dd($account);

        $account_array = array();
        $account_array[0] = [
            "title" => "Main Account",
            "amount" => $account->main_wallet,
            "key" => "main_account",

        ];
        $account_array[1] = [
            "title" => "Bonus Account",
            "amount" => $account->bonus_wallet,
            "key" => "bonus_account",
        ];

        return $account_array;
    }
}

if (!function_exists('send_error')) {

    function send_error($message)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.telegram.org/bot6140179825:AAGfAmHK6JQTLegsdpnaklnhBZ4qA1m2c64/sendMessage?chat_id=1316552414',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'chat_id' => "1316552414",
                'text' => $message,

            ),
            CURLOPT_HTTPHEADER => array(),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
    }
}


if (!function_exists('send_notification')) {

    function send_notification($message)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.telegram.org/bot6140179825:AAGfAmHK6JQTLegsdpnaklnhBZ4qA1m2c64/sendMessage?chat_id=1316552414',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'chat_id' => "1316552414",
                'text' => $message,

            ),
            CURLOPT_HTTPHEADER => array(),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
    }




    if (!function_exists('store_vfd_banks')) {
        function store_vfd_banks()
        {

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

            $result = $var->data ?? null;

            $status = $var->code ?? null;

            $chk_bank = VfdBank::select('*')->first()->bank_code ?? null;
            if ($chk_bank == null || empty($chk_bank)) {
                $history = [];
                foreach ($var->data as $key => $value) {
                    $history[] = array(
                        "bank_name" => $value->bankName,
                        "code" => $value->code,
                    );
                }

                DB::table('vfd_banks')->insert($history);
            }
        }
    }


    if (!function_exists('store_providus_banks')) {
        function store_providus_banks()
        {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://154.113.16.142:8882/postingrest/GetNIPBanks',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',

            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);


            $result = $var->banks ?? null;

            $status = $var->code ?? null;

            $chk_bank = ProvidusBank::select('*')->first()->bank_code ?? null;
            if ($chk_bank == null || empty($chk_bank)) {
                $history = [];
                foreach ($var->banks as $key => $value) {
                    $history[] = array(
                        "bank_name" => $value->bankName,
                        "code" => $value->bankCode,
                    );
                }

                $rr =  DB::table('providus_banks')->insert($history);

                return  $rr;
            }
        }
    }


    if (!function_exists('get_banks')) {
        function get_banks()
        {



            $set = Setting::where('id', 1)->first();

            if ($set->bank == 'vfd') {
                $get_banks = VfdBank::select('bankName', 'code')->get();


                return $get_banks;
            }


            // if($set->bank == 'manuel'){
            //     $get_banks = ProvidusBank::select('bankName', 'code')->get();
            //     return $get_banks;
            // }



            if ($set->bank == 'manuel') {
                $get_banks = VfdBank::select('bankName', 'code')->get();

                return $get_banks;
            }





            if ($set->bank == 'pbank') {
                $get_banks = ProvidusBank::select('bankName', 'code')->get();



                return $get_banks;
            }
        }
    }



    if (!function_exists('resolve_bank')) {
        function resolve_bank($bank_code, $account_number)
        {



            $set = Setting::where('id', 1)->first();

            if ($set->bank == 'manuel') {

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

                $status = $var->code ?? null;

                if ($status == 200) {

                    return $customer_name;
                }

                return $error;
            }

            if ($set->bank == 'pbank') {

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

                $status = $var->code ?? null;

                if ($status == 200) {

                    return $customer_name;
                }

                return $error;
            }


            if ($set->bank == 'vfd') {


                $customer_name = AccountInfo::where('account_no', $account_number)
                    ->where('code', $bank_code)->first()->customer_name ?? null;

                    if($customer_name != null){
                        return $customer_name;
                    }




                if (!empty($customer_name) || $customer_name == null) {

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

                    $status = $var->code ?? null;

                    $bankName = VfdBank::where('code', $bank_code)->first()->bankName;

                    if ($status == 200) {

                       $sv = new AccountInfo();
                       $sv->account_no = $account_number;
                       $sv->code = $bank_code;
                       $sv->bankName = $bankName;
                       $sv->customer_name = $customer_name;
                       $sv->save();

                       return $customer_name;
                    }

                    return $error;
                }
            }
        }
    }
}
