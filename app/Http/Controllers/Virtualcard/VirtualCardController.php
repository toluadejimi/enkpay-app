<?php

namespace App\Http\Controllers\Virtualcard;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\Transactions;
use App\Models\User;
use App\Models\VCard;
use Auth;
use Illuminate\Http\Request;

class VirtualCardController extends Controller
{


    public function verify_identity(Request $request)
    {


        $key = env('BKEY');

        // if ($request->hasFile('image')) {
        //     $image = $request->file('image');
        //     $filename = 'image_'.time().'.'.$image->extension();
        //     $location = 'asset/images/' . $filename;
        //     Image::make($image)->save($location);
        //     $file_url = url('') . "/asset/images/$filename";
        // }


        if (Auth::user()->bvn == null) {

            return response()->json([
                'status' => true,
                'message' => 'please verify your account',
            ], 500);
        }

        if (Auth::user()->identification_image == null) {

            return response()->json([
                'status' => true,
                'message' => 'please verify your account',
            ], 500);
        }


        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path() . 'upload/selfie';
            $request->photo->move(public_path('upload/selfie'), $fileName);
            $file_url = url('') . "/public/upload/selfie/$fileName";
        }else{
            $fileName =  Auth::user()->identification_image;
            $file_url = url('') . "/public/upload/selfie/$fileName";
        }







        // User::where('id', Auth::user()->id)
        // ->update([
        //     'identification_type' => $request->identification_type,
        //     'identification_number' => $request->identification_number,
        //     'bvn' => $request->bvn,
        //     'identification_image' => $file_url,

        // ]);



        $databody = array(

            "first_name" => Auth::user()->first_name,
            "last_name" => Auth::user()->last_name,

            "address" => array(
                "address" => Auth::user()->address_line1,
                "city" =>   Auth::user()->city,
                "state" =>  Auth::user()->state,
                "country" => "Nigeria",
                "postal_code" => random_int(1000, 9999),
                "house_no" => random_int(10, 99),
            ),


            "phone" => Auth::user()->phone,
            "email_address" => Auth::user()->email,

            "identity" => array(
                "id_type" => "NIGERIAN_BVN_VERIFICATION",
                "selfie_image" => $file_url,
                "bvn" => Auth::user()->bvn,

            ),

            "meta_data" => array(
                "user_id" => Auth::id(),
            ),


        );



        $body = json_encode($databody);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cardholder/register_cardholder_synchronously',
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
                "token: Bearer $key"
            ),
        ));

        $var = curl_exec($curl);

        curl_close($curl);
        $var = json_decode($var);
        $error = $var->message ?? null;
        $status = $var->status ?? null;



        // $id = $var[0]->id;
        if ($status == "success") {

            User::where('id', Auth::user()->id)
                ->update([
                    'identification_image' => $file_url,
                    'card_holder_id' => $var->data->cardholder_id,
                    'is_kyc_verified' => 1,
                    'is_identification_verified' => 1,
                    'status' => 2,


                ]);

            $message = " Vcard verification |"  . Auth::user()->first_name . " " .  Auth::user()->last_name;
            send_notification($message);

            return response()->json([
                'status' => true,
                'message' => 'Account has been successfully verified',
            ], 200);
        }

        $alert = " Vcard verification Error |"  . Auth::user()->first_name . " " .  Auth::user()->last_name . "| $error";
        send_notification($alert);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }

    public function fund_card(Request $request)
    {
        $set = Settings::first();
        $user = User::find(Auth::user()->id);
        $key = env('BKEY');

        $amount_to_charge = $request->amount + $set->ngn_rate;


        if (Auth::user()->main_wallet < $amount_to_charge) {

            return response()->json([
                'status' => false,
                'message' => 'Account balance is insufficient, Fund your wallet',
            ], 500);
        }


        User::where('id', Auth::id())->decrement('main_wallet', $amount_to_charge);


        //fund card
        $get_card_id = VCard::select('*')->where('user_id', Auth::id())->first()->card_id;
        $amount_in_usd = $request->amount / $set->ngn_rate * 100;

        $curl = curl_init();
        $data = [

            "card_id" => $get_card_id,
            "amount" => $amount_in_usd,
            "transaction_reference" => random_int(1000000, 9999999),
            "currency" => "USD"

        ];
        $post_data = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cards/fund_card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "token: Bearer $key"
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $status = $var->status ?? null;
        $ref = $var->data->transaction_reference ?? null;
        $message = "Error from Virtual Card Fund" . "|" . $var->message ?? null;


        // $ref = "123456789";
        // $status = 'success';

        if ($status == 'success') {


            $balance = Auth::user()->main_wallet;

            Vcard::where('card_id', $get_card_id)->update([

                'amount' => $amount_in_usd / 100,
            ]);

            $trasnaction = new Transactions();
            $trasnaction->user_id = Auth::id();
            $trasnaction->e_ref = $ref;
            $trasnaction->transaction_type = "CardFunding";
            $trasnaction->amount = $amount_to_charge;
            $trasnaction->note = "USD CARD FUNDING | USD " . $amount_in_usd / 100;
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->balance = $balance;
            $trasnaction->status = 1;
            $trasnaction->save();

            return response()->json([
                'status' => true,
                'message' => 'Your card has been funded successfully | USD $' . number_format($amount_in_usd / 100, 2),

            ], 200);
        } else {

            User::where('id', Auth::id())->increment('main_wallet', $amount_to_charge);

            send_notification($message);

            return response()->json([
                'status' => false,
                'message' => 'Service not available at the moment, Please try again later',
            ], 500);
        }
    }



    public function block_card(request $request)
    {
        $set = Settings::first();
        $user = User::find(Auth::user()->id);
        $card = VCard::where('user_id', Auth::id())->first();

        $key = env('BKEY');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://issuecards.api.bridgecard.co/v1/issuing/cards/freeze_card?card_id=$card->card_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "token: Bearer " . env('BKEY')
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $status = $var->status ?? null;
        $message = "Error from V Card Fund" . "|" . $var->message ?? null;
        $error = $var->message ?? null;

        // $status ="success";

        if ($status == 'success') {

            VCard::where('user_id', Auth::id())->update([

                'status' => 2,

            ]);

            return response()->json([
                'status' => true,
                'message' => 'You card shas been successfully blocked',
            ], 200);
        }

        send_notification($message);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }


    public function unblock_card(request $request)
    {
        $set = Settings::first();
        $user = User::find(Auth::user()->id);
        $card = VCard::where('user_id', Auth::id())->first();

        $key = env('BKEY');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://issuecards.api.bridgecard.co/v1/issuing/cards/unfreeze_card?card_id=$card->card_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "token: Bearer " . env('BKEY')
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $status = $var->status ?? null;
        $message = "Error from V Card Fund" . "|" . $var->message ?? null;
        $error =  $var->message ?? null;



        // $status ="success";


        if ($status == 'success') {

            VCard::where('user_id', Auth::id())->update([

                'status' => 1,

            ]);

            return response()->json([
                'status' => true,
                'message' => 'You card has been successfully unblocked',
            ], 200);
        }

        send_notification($message);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }





    public function liquidate_card(Request $request)
    {

        $set = Settings::first();
        $key = env('BKEY');
        $card = VCard::where('user_id', Auth::id())->first();
        $amt_in_naira = $set->w_rate * $request->amount;



        $curl = curl_init();
        $data = [

            "card_id" => $card->card_id,
            "amount" => $request->amount * 100,
            "transaction_reference" => random_int(1000000, 9999999),
            "currency" => "USD"

        ];
        $post_data = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cards/unload_card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "token: Bearer $key"
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $error = $var->message ?? null;
        $status = $var->status ?? null;






        if ($status == 'success') {
            User::where('id', Auth::id())->increment('main_wallet', $amt_in_naira);

            $balance = User::where('id', Auth::id())->first()->main_wallet;

            //update Transaction
            $trasnaction = new Transactions();
            $trasnaction->user_id = Auth::id();
            $trasnaction->transaction_type = "CardWithdraw";
            $trasnaction->amount = $amt_in_naira;
            $trasnaction->note = "Card Liquidation | NGN " . number_format($amt_in_naira, 2);
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->balance = $balance;
            $trasnaction->status = 1;
            $trasnaction->save();

            return response()->json([
                'status' => true,
                'message' => 'Card has been successfully liquidated',
            ], 200);
        }



        $mymessage = "VCARD ERROR " . "|" . $error;
        send_notification($mymessage);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }



    public function create_details(Request $request)
    {


        $card = Vcard::where('user_id', Auth::id())->first() ?? null;

        if($card == null){

            return response()->json([
                'status' => false,
                'message' => "No card found",
            ], 500);

        }else{


            return response()->json([
                'status' => true,
                'card_number' => $card->MaskedPAN,
            ], 500);

        }








    }





    public function create_card(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $key = Settings::first();
        $bkey = env('BKEY');
        $card_fee_ngn =  $key->ngn_rate * $key->virtual_createcharge;

        if (Auth::user()->main_wallet < $card_fee_ngn) {
            return back()->with('alert', 'Account balance is insufficient, Fund your wallet');
        }

        $chk_card = VCard::where('user_id', $user->id)->first()->user_id ?? null;

        // if($chk_card == Auth::id()){
        //     return back()->with('alert', 'You already have a usd card');
        // }

        //create card


        $curl = curl_init();
        $data = array(
            "cardholder_id" => Auth::user()->card_holder_id,
            "card_type" => "virtual",
            "card_brand" => "Visa",
            "card_currency" => "USD",
            "meta_data" => array(
                "user_id" => Auth::id()
            ),
        );
        $post_data = json_encode($data);
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cards/create_card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => 0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "token: Bearer $bkey"
            ),
        ));
        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
        $status = $var->status ?? null;
        $message = "VCard Error | " .  $var->message ?? null;

        if ($status == "success") {

            //save card
            $vcard = new VCard();
            $vcard->user_id = Auth::id();
            $vcard->first_name = $request->first_name;
            $vcard->last_name = $request->last_name;
            $vcard->bg = $request->bg;
            $vcard->card_id = $var->data->card_id;
            $vcard->currency = $var->data->currency;
            $vcard->save();

            $balance = Auth::user()->main_wallet  - $card_fee_ngn;

            User::where('id', Auth::id())->decrement('main_wallet', $card_fee_ngn);


            $trasnaction = new Transactions();
            $trasnaction->user_id = Auth::id();
            $trasnaction->transaction_type = "CardCreation";
            $trasnaction->amount = $card_fee_ngn;
            $trasnaction->note = "USD Creation Fee | USD $key->virtual_createcharge ";
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->balance = $balance;
            $trasnaction->status = 1;
            $trasnaction->save();

            $message = "A card was created just now";
            send_notification($message);

            return response()->json([
                'status' => true,
                'message' => "Your card has successfully created",
            ], 200);
        } else {
            send_notification($message);
            return response()->json([
                'status' => false,
                'message' => "Card creation not available at the moment try again later",
            ], 500);
        }
    }


     public function card_details(Request $request)
    {


        $set = Settings::where('id', 1)->first();
        $card = Vcard::where('user_id', Auth::id())->first() ?? null;

        if($card == null){

                $card_details = "N0 Card Found";

        }else{

             $card_details = array([
            'card_number' => $card->masked_card,
            'cvv' => $card->cvv,
            'expiration' => $card->expiration,
            'card_type' => $card->card_type,
            'name_on_card' => $card->name_on_card,
            'amount' => $card->amount,
            'city' => $card->city,
            'state' => $card->state,
            'address' => $card->address,
            'zip_code' => $card->zip_code,
        ]);

        }




        $val = VCard::where('id', Auth::id())->first() ?? null;

        if($val != null) {


            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://issuecards.api.bridgecard.co/v1/issuing/cards/get_card_transactions?card_id=$val->card_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "token: Bearer " . env('BKEY')
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);
            $var = json_decode($var);
            $card_data = $var->data->transactions ?? null;


            if($card_data == null){
                $data =  "No Transaction found";

            }else{
                $data = $card_data;
            }



        }else{

            $data =  "No Transaction found";

        }







        return response()->json([
                'status' => true,
                'creation_charge' => $set->virtual_createcharge,
                'rate' => "$set->ngn_rate",
                'w_rate' => $set->w_rate,
                'card_transaction' => $data,
                'card_details' => $card_details,

            ], 200);









    }





}
