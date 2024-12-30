<?php

namespace App\Console\Commands;

use App\Models\Transactioncheck;
use App\Models\Transfertransaction;
use App\Models\Webkey;
use Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Charge;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\Transaction;
use App\Models\Webtransfer;
use App\Models\VirtualAccount;
use Illuminate\Console\Command;
use App\Models\PendingTransaction;
use App\Models\CompletedWebtransfer;
use Pusher\Webhook;

class SendCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {




       $trx_webhoook =  Webhook::where('status', 0)->first() ?? null;
       if($trx_webhoook != null){

           try {

               $acc_no = $trx_webhoook->account_no;
               $status = Transfertransaction::where('account_no', $acc_no)->first()->status ?? null;
               $trx = Transfertransaction::where('account_no', $acc_no)->first() ?? null;
               $amount = Transfertransaction::where('account_no', $acc_no)->first()->amount ?? null;
               $pstatus = Transfertransaction::where('account_no', $acc_no)->first()->status ?? null;


               if ($pstatus == 4) {
                   return [
                       'code' => 4
                   ];
               }


               if ($status == 4) {

                   $ref=$trx->ref_trans_id;
                   $url = url('')."/success?trans_id=$ref&amount=$amount";
                   return [
                       'url' => $url,
                       'code' => 4
                   ];

               }



               $trx = Transfertransaction::where('account_no', $acc_no)
                   ->where([
                       'status' => 0
                   ])->first() ?? null;


               if ($trx == null) {

                   return response()->json([
                       'status' => false,
                       'message' => "Account Not found in our database",
                   ]);

               }



               $paid_amt = Transfertransaction::where('account_no', $acc_no)->update(['amount_paid' => $amount]) ?? null;
               Transfertransaction::where('account_no', $acc_no)->increment('amount_paid', $amount);
               $trx = Transfertransaction::where('account_no', $acc_no)->first() ?? null;

               $main_amount = $trx_webhoook->amount;
               if ($trx != null) {

                   $set = Setting::where('id', 1)->first();
                   if ($amount > 15000) {
                       $p_amount = $main_amount - $set->psb_cap;
                   } else {
                       $p_amount = $main_amount - $set->psb_charge;
                   }



                   if ($trx->status == 0) {
                       //fund Vendor
                       $trx = Transfertransaction::where('account_no', $acc_no)->first();
                       User::where('id', $trx->user_id)->increment('main_wallet', $p_amount);
                       $balance = User::where('id', $trx->user_id)->first()->main_wallet;
                       $user = User::where('id', $trx->user_id)->first();
                       $session_id = Transfertransaction::where('account_no', $acc_no)->first()->session_id ?? null;



                       $url = Webkey::where('key', $trx->key)->first()->url_fund ?? null;
                       $user_email = $trx->email ?? null;
                       //$amount = $trx->amount ?? null;
                       $order_id = $trx->ref_trans_id ?? null;
                       $site_name = Webkey::where('key', $trx->key)->first()->site_name ?? null;

                       $trasnaction = new Transaction();
                       $trasnaction->user_id = $trx->user_id;
                       $trasnaction->e_ref = $request->sessionid ?? $acc_no;
                       $trasnaction->ref_trans_id = $order_id;
                       $trasnaction->type = "webpay";
                       $trasnaction->transaction_type = "VirtualFundWallet";
                       $trasnaction->title = "Wallet Funding";
                       $trasnaction->main_type = "CHARM";
                       $trasnaction->credit = $p_amount;
                       $trasnaction->note = "Transaction Successful | Web Pay | for $user_email";
                       $trasnaction->fee = $fee ?? 0;
                       $trasnaction->amount = $trx->amount;
                       $trasnaction->e_charges = 0;
                       $trasnaction->charge = $payable ?? 0;
                       $trasnaction->enkPay_Cashout_profit = 0;
                       $trasnaction->balance = $balance;
                       $trasnaction->status = 1;
                       $trasnaction->save();

                       $message = "Business funded | $acc_no | Charm | $p_amount | $user->first_name " . " " . $user->last_name;
                       send_notification($message);

                       Webtransfer::where('trans_id', $trx->trans_id)->update(['status' => 4]);
                       Transfertransaction::where('account_no', $acc_no)->update(['status' => 4, 'resolve' => 1]);
                       Webhook::where('account_no', $acc_no)->delete() ?? null;

                       $trck = new Transactioncheck();
                       $trck->session_id = $trx_webhoook->sessionId;
                       $trck->amount = $trx->amount;
                       $trck->status = 2;
                       $trck->email = $user_email;
                       $trck->save();

                       $type = "epayment";
                       $fund = credit_user_wallet($url , $user_email, $amount, $order_id, $type, $session_id);

                       $message = "Repush ====>  Funding of | $amount | successful | $user_email | on | $site_name" ;
                       send_notification($message);

                   }





               }
           } catch (\Exception $th) {
               $message = "Error funding user >>>>> ". $th->getMessage();
               send_notification($message);
               return $th->getMessage();
           }


       }






        $dataToMove =  Webtransfer::where('status', 1)->get();
        foreach ($dataToMove as $item) {
          CompletedWebtransfer::updateOrCreate(['id' => $item->id], $item->toArray());
        }

         Webtransfer::where('status', 1)
        ->delete();

        $timefive = Carbon::now()->subMinutes(5);
        VirtualAccount::where('state', 1)
        ->update(['state' => 0]);



        // $data =  $dataToMove =  Webtransfer::where('status', 1)->count();

//         $message = "hello";
//         send_notification($message);


    }
}
