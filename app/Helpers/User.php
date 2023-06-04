<?php

use App\Models\User;
use App\Models\VirtualAccount;
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
            CURLOPT_HTTPHEADER => array(
            ),
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
            CURLOPT_HTTPHEADER => array(
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

    }



    if (!function_exists('create_p_account')) {

        function create_p_account($name, $bvn)
        {

            $curl = curl_init();
            $data = array(
                "account_name" => $name,
                "bvn" => $bvn,
            );

            $databody = json_encode($data);
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://vps.providusbank.com/vps/api/appdevapi/api/PiPCreateReservedAccountNumber',
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
                    'X-Auth-Signature: b900d355dd66f3507c775ba52bcd3ba6b6f3f4093448ea24f3aa6500bbbce5c1e63c12214acd08d8057b7bec36d37a8f66a504a1b7a8df54af00ba6ba825a9c4',
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            dd($var);

            $status = $var->responseCode ?? null;
            $p_acct_no = $var->account_number ?? null;
            $p_acct_name = $var->account_name ?? null;

            $pbank = "PROVIDUS BANK";

            if ($status == 00) {

                $create = new VirtualAccount();
                $create->v_account_no = $p_acct_no;
                $create->v_account_name = $p_acct_name;
                $create->v_bank_name = $pbank;
                $create->save();

                $user = User::find(Auth::id());
                $user->p_account_no = $p_acct_no;
                $user->p_account_name = $p_acct_name;
                $user->save();


                return response()->json(['account_no' => $p_acct_no,  'account_name' => $p_acct_name]);

                $message = "Account Created on Providus";
                send_notification($message);

            }


            $message = "Error from Providus Account Creation | Account Created on Providus";
            send_notification($message);


        }








    }





    if (!function_exists('create_dynamic_p_account')) {

        function create_dynamic_p_account($name)
        {
            $client_id = env('CLIENTID');
            $hashkey = env('HASHKEY');


            $curl = curl_init();
            $data = array(
                "account_name" => $name,
            );

            $databody = json_encode($data);
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://vps.providusbank.com/vps/api/PiPCreateDynamicAccountNumber',
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
                    "Client-Id: $client_id",
                    "X-Auth-Signature: $hashkey",
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            dd($var);

            $status = $var->responseCode ?? null;
            $p_acct_no = $var->account_number ?? null;
            $p_acct_name = $var->account_name ?? null;

            $pbank = "PROVIDUS BANK";

            if ($status == 00) {

                $create = new VirtualAccount();
                $create->v_account_no = $p_acct_no;
                $create->v_account_name = $p_acct_name;
                $create->v_bank_name = $pbank;
                $create->save();

                $user = User::find(Auth::id());
                $user->p_account_no = $p_acct_no;
                $user->p_account_name = $p_acct_name;
                $user->save();


                return response()->json(['account_no' => $p_acct_no,  'account_name' => $p_acct_name]);

                $message = "Account Created on Providus";
                send_notification($message);

            }


            $message = "Error from Providus Account Creation | Account Created on Providus";
            send_notification($message);


        }








    }






}


