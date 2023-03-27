<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

if (!function_exists('main_account')) {

    function main_account()
    {
        $user = Auth::user();
        return $user->main_wallet;
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
        return $user->identification_number;
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

        // dd($account->main_wallet);
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

}
