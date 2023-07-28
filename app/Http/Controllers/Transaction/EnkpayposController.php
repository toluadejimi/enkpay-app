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


        // // Configuration settings for the key
        // $config = array(
        //     "digest_alg" => "sha512",
        //     "private_key_bits" => 4096,
        //     "private_key_type" => OPENSSL_KEYTYPE_RSA,
        // );

        // // Create the private and public key
        // $res = openssl_pkey_new($config);

        // // Extract the private key into $private_key
        // openssl_pkey_export($res, $private_key);

        // Extract the public key into $public_key
        // $public_key = openssl_pkey_get_details($res);
        $public_key = '/Users/mac/Desktop/web project/ENKPAY APP/enkpay-app/public_key.pem';
        $private_key = '-----BEGIN PRIVATE KEY-----
        MIIJQwIBADANBgkqhkiG9w0BAQEFAASCCS0wggkpAgEAAoICAQCpeuxticMOoTZT
        N98q/OFGSus6joTDBfKEAFeDMzzcxY5pY3wQUpu8xMhNaBFyYe0ICNUYFAFh9J5H
        hGj9Y1ktzO5piH9nm7rj3a3NCKFGeAjHCDM9m+WpdSEjQ/NSkmiSUXKErmDZIgnO
        crFrvQ7SLIZkTqkGCU3ZAgWZ5GNPvCErrYlkDoQKfFqtdBdSmNGmd3HWNZji/Cgr
        82y/r9Ch3r1DnxDa6UNbReWp7uqxB6NZP1o6T2Dl5M6Cy12rogQuIEGsAx4eKs88
        6a+qXaq9hu00laJ6dMjLRu7k46FIf7pX+UllNSP9/g5b1EaR4o34ln3h66EErBqc
        fxshSI6cl7ro1GIIots+0x7tn4+WW0VynoMYDMnMIMdkeHCTJqwPc/RZyLp/K2ui
        FITlhS/pt0YnUEgGYtdMi1LgDNEqIE4glfQxv61b/TB9ZJs30FRcZ8odr0UcoRAT
        xbLG3d4Pm86uenYnhOc6PVxz9m5n6YjAdXAOrzt9Sd4jylU0mvvifxismWsm79iE
        JjmtohYi5pWygRnfO8RhR42bOrelYPKWN77sCv1zjPs20Tto/BZUe3ofSYqGFXIS
        cJr1j/tWCQ0uQRKTiB7l82D7cSTYbaK06rnJxjwwYBqDLBsDmGcu7tBsTbfKWC9H
        r3UP8u3fFzdPM3OCm5vxlsOGTnpEcQIDAQABAoICAAU+p18RT/3hIbzqNZt2ZmAS
        na4Mx7FxyR3U3G6piAlqwz2T5yAw2rPVjyMTb0XlO+dhMUKPYiaucEKcUkWpl0t3
        qYGOyF/j4xLTQwzdZroMjK1uyE8pcVkCl3sodUcbcB6Q1gS/H2Yjdk0dzKDlBTa6
        vOZmboxnKYd1yR0IZqkKsNLDiiiUaRXhip/x+MOvtIxR6xGZL66VrKLM48wYlRkL
        gletfGFEkZp+tkBsH5KCPwChRAuoWWz5MntKYPK1HStWCG6vTSAUUC6Z1S2Cn+Gz
        KnPTlS5mEM2TZgujgfBWwOT2gWhmR1/cuIPsGFjCSb46bK4wWmzzJS10otm9m9tD
        9x7Ev+z2hxTa6YZBW+LIjPtIGjHJW1iUP5jmBkAYwI9qlktqt67y9oh4C5zalf3v
        AZ8lyqiNRM8c/cp81aA6PTPfKpya4E2a034HRrJOkjl8FIYdaryUQFV+LxoMkYNd
        6/CsP/TPeKpMCMvZOPo8XqMUOXd3PWonEWnMKQB0Gt9YGx5nNdxiq42a9U4leS3S
        UDr8bxHfbXyE6BGJrHzBhz1GKg1V0eaUYNaVlRTpNcwKzegbUcyfmO+CK+05UmZo
        e8wMJNBGD1e/xkxS3uMCc/KOXtiQeAkHq8nHb+YPXi2XNF85Gx6XALiO3rs1Eymo
        WjtALMzamk2SPjIsHET1AoIBAQDpfDxq4wJX2ycr6tS/Bomhf3KxmD9Fw4wGJdyr
        XHc0RLkvyv9TltN3n/ZSxqLSB8Fn4gzUTr7zvqbymCE/49DtVHuedJeUZ8bsgLqT
        Sowic+6XF7bl1gnJlDj0eboXshSRzsp9xTo0HDsrvwVaegoQVTZObSQnn57OdCO1
        HOyjsBka8dnA5R4iYXufNXxwBTRNMGM9Wvk5Wc+2K+ij/T0IRW8Gq+knr7WK1enB
        8YEn3ixs3VtbIIWGbWMZ+qBW2dP0Nvpg3Pkk4jf8y1UNaC3JRn8IQb24qW1eki5N
        MOc5o8ZAWS251YEDz/CUfIYR69/PuibB5P0kar2DDoemUlnPAoIBAQC50qv6zoNT
        VvUjYDaG3oAqzaLxSgj7co9sz03I802fmcrBDKS7oX1k3yOrH9wfj+8EkTPm081+
        t30ozqus6+yQU/dNuEOvUMw5PCDiljCE5f1oYI3zzvMavr6hr9dPXUl39alPZt3u
        pDRDBxHfdxtTYDyx/L37InXdNbCO8N89vrjCm7qG3TzBL86jLQ/mX6x6lw+XTNX1
        Qeql039KGxzKj4sDcAE0/4QT3N27R0puPNngHJK+q6WVe8Iz35BvDHn9VTP0Eosz
        Tjwpr6BEcs/AanHfNNjZiN3/0P6sEnfoBl60FJ9DLrEEAvE4/Xc1POTuYzEIH6nS
        6RetaMk9Gk2/AoIBAB1ObL7103t3lIkcpqt7hJbyylCkTXzSOKck5XfU2VmELsJi
        9n1ldxGS0HY/XHyEYLBLXVTgtXMewRG+Kp48WCMR8ZpIBCaqh/tzhPer6b8BUjdA
        0MI0hXH52tRE2yjSP2a10BDNSqrzyDGLfn6GxvCZ4YEijeEzVdmQFlNLWCCLn/sT
        1EMz/v5NwhiPvBjLbm8p3ar72kBql3nENSUwShLZtD/gIBnNIjpU80xWpeF9L5JS
        jn73rArdfAzsumdsXABRTkgONG9+vYxLcVeHdgfHg3Zj2g5tpb6zFoC13LANcHrv
        NqAWvmTeGtJoduOU+bi3Ito/hc6mXzcuAbTMrV0CggEBALm6t+qXPuutnpcdM2La
        QYCGyLYjKZcpifFXwod3p74+GUmbOYvQfWS1IxGHZpylYGFQQHAWgu6Et7Rx3WWY
        6XnYcvZblktEltseHkBbnWM3/XC2ESv/TT0OKbux5aKNu0ELaY7TYj+EIDheeJ7C
        ja1oI0JDPCAm8WeIolA8sOUmG8VnoafquS7eecre32WDewGMuyiew7u/hqj4G+IV
        91D+6BMngA1Y1sqDl5v4RYyphZta89Ff/sDKUfFHIzXbYpKy9pqdbkR5jeicgbPo
        jX3k5qwSRiwngLyQR/v0+aQPudiERCsCICvCRRiRPvUXSDp9KiTUgQktsVzBv2Iz
        SEECggEBAIbx4yxHa3cnlfdZE/g0BAy0srJoMIb9Ah9rwgz2Mb9LacHaikmZHkd4
        AnIwlGpr0SKJ/pz+HYDOiHEwG740rcEySq+bYPq/IPTvDeJChZDdICllpmWgzA1L
        Xx85LTnRKwP7jaXfkUHICSlZ+KbjLWgS8rmftxMOG5/TUXUMWIhvGJTcyl+TiE6o
        6CtkDPvTIekBUWDT3+A/XehK4sOG8+LkO7vqMrJb6fxu+jF+vjrsIxUlgSx/uEk9
        ttCUZSCPsWNUYWDeHJIA/bXxve78vf/eqrHvesbw502BtL5JYqmpMnOleeiUCuET
        D9rjrdf0WW4ZKU7ilqZm4r3uPcClO2E=
        -----END PRIVATE KEY-----
        ';






        //     // Something to encrypt
        // $text = '{This is the text to encrypt';

        // echo "This is the original text: $text\n\n";

        // // Encrypt using the public key
        // openssl_public_encrypt($text, $encrypted, $public_key);

        // $encrypted_hex = bin2hex($encrypted);
        // echo "This is the encrypted text: $encrypted_hex\n\n";



        $encrypted = 'l0UzzhQv3yaNrsIPhWc75UhYSaIiMXMG/Hpkg9WQfz0AHSF3/fAxeHkBSwodd74mek7n/cZwhej7nJHGcM6UNuxsMlatb8rq72vKrTrrawj7TIUZR2cE5oQeyuCfmM/7a4dxSnVl0zOI+my3cLnx6fMXEoHqx+Xyh6LVWosSRqFP5PDkpHbnNqDRCoY2HSETHmJc4J7qItj//PL0Kkg+hnZQ/dsJGGx9JZkON/fOwzNhmb11oVpoEJl/2snp73fS+ef864fWmQ3lujrxV3BpnuU1CWkdCptZze/zNKXQ0GA6k+B1RnkyJobs8pRA0EMJIittfd4rLEFBEi6SkecMn6Cjg9WuT01aVbhdjFnWSzFNSpyWS+Kpwv1SN1rmaho791AtX0EPoUcFke1wm/1wpr/S1GQhQfsahGdnqIyO8oCoBfqAo2yWFypiT7rGVU6ZXaOTa2BaDkjmcNeu7tn59yXWIikv8n0fhP8LQVnGEnX/huQgyptuODCPKwbnA659O6+5KdYtXEjGS+DhrnHy1yw5UrFc9MhA/bwiUksqp7o2CHjWek5LidblVtXkmaZHCB+MZKMRr9k3NbleWtNGWhgj96gm20kWEFMkt0bePmRfEguES/a1Qn4ABm8BHm4egIb8+subIUZQ6Rv9GmpgL3ZRJh5i6cQlR9/n0ZIMSCs=';

        // Decrypt the data using the private key
        openssl_private_decrypt($encrypted, $decrypted, $private_key);


        dd($public_key, $private_key, $encrypted, $decrypted);



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
