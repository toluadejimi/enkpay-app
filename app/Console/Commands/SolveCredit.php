<?php

namespace App\Console\Commands;

use App\Models\Feature;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SolveCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:solve-credit';

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

        $user1 = User::select('main_wallet')->where('id','203')->first()->main_wallet;
        //$user2 = User::select('main_wallet')->where('id','293395')->first()->main_wallet;
        $user3 = User::select('main_wallet')->where('id','214')->first()->main_wallet;
        $user4 = User::select('main_wallet')->where('id','293494')->first()->main_wallet;
        $user5 = User::select('main_wallet')->where('id','293554')->first()->main_wallet;
        $user6 = User::select('main_wallet')->where('id','293578')->first()->main_wallet;
        $user7 = User::select('main_wallet')->where('id','293599')->first()->main_wallet;
        $user8 = User::select('main_wallet')->where('id','293619')->first()->main_wallet;
        $user10 = User::select('main_wallet')->where('id','293526')->first()->main_wallet;
        $user11 = User::select('main_wallet')->where('id','293623')->first()->main_wallet;




        $count1 = Transaction::where('user_id','203')->whereDate('created_at', Carbon::today())->count();
        //$count2 = Transaction::where('user_id','293395')->whereDate('created_at', Carbon::today())->count();
        $count3 = Transaction::where('user_id','214')->whereDate('created_at', Carbon::today())->count();
        $count4 = Transaction::where('user_id','293369')->whereDate('created_at', Carbon::today())->count();
        $count5 = Transaction::where('user_id','293554')->whereDate('created_at', Carbon::today())->count();



        if($user11 > 500000){
            $deuc = 30000;
            User::where('id','293623')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " CHI Count1========> " . $deuc;
            send_notification($result);



        }elseif($user11 > 200000){

            $deuc = 15000;
            User::where('id','293623')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " CHI Count1========> " . $deuc;
            send_notification($result);

        }elseif($user10 > 100000){

            $deuc = 10000;
            User::where('id','293623')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " CHI Count1========> " . $deuc;
            send_notification($result);


        }else{

            $result = " CHI Count1========> ";
            send_notification($result);

        }



        if($user10 > 500000){
            $deuc = 30000;
            User::where('id','293526')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " NON Count1========> " . $deuc;
            send_notification($result);



        }elseif($user10 > 200000){

            $deuc = 15000;
            User::where('id','293526')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " AMK Count1========> " . $deuc;
            send_notification($result);

        }elseif($user10 > 100000){

            $deuc = 6000;
            User::where('id','293526')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " AMK Count1========> " . $deuc;
            send_notification($result);


        }else{

            $result = " AMK Count1========> ";
            send_notification($result);

        }





        if($user8 > 500000){
            $deuc = 100000;
            User::where('id','293619')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " FAD Count1========> " . $deuc;
            send_notification($result);



        }elseif($user8 > 200000){

            $deuc = 30000;
            User::where('id','293619')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " FAD Count1========> " . $deuc;
            send_notification($result);

        }elseif($user8 > 10000){

            $deuc = 6000;
            User::where('id','293619')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " FAD Count1========> " . $deuc;
            send_notification($result);


        }else{

            $deuc = 2000;
            User::where('id','293619')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " FAD Count1========> " . $deuc;
            send_notification($result);

        }






        if($user7 > 500000){
            $deuc = 50000;
            User::where('id','293599')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " ACE2 Count1========> " . $deuc;
            send_notification($result);



        }elseif($user7 > 200000){

            $deuc = 15000;
            User::where('id','293599')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " ACE2 Count1========> " . $deuc;
            send_notification($result);

        }elseif($user7 > 10000){

            $deuc = 6000;
            User::where('id','293599')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " ACE2 Count1========> " . $deuc;
            send_notification($result);


        }else{

            $deuc = 2000;
            User::where('id','293599')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " ACE2 Count1========> " . $deuc;
            send_notification($result);

        }


        if($user6 > 500000){
            $deuc = 30000;
            User::where('id','293578')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek2 Count1========> " . $deuc;
            send_notification($result);



        }elseif($user6 > 200000){

            $deuc = 15000;
            User::where('id','293578')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek2 Count1========> " . $deuc;
            send_notification($result);

        }elseif($user6 > 10000){

            $deuc = 6000;
            User::where('id','293578')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek2 Count1========> " . $deuc;
            send_notification($result);


        }else{

            $deuc = 2000;
            User::where('id','293578')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek2 Count1========> " . $deuc;
            send_notification($result);

        }



        if($user6 > 500000){
            $deuc = 30000;
            User::where('id','203')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek Count1========> " . $deuc;
            send_notification($result);



        }elseif($user6 > 200000){

            $deuc = 15000;
            User::where('id','203')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek Count1========> " . $deuc;
            send_notification($result);

        }elseif($user1 > 10000){

            $deuc = 6000;
            User::where('id','203')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek Count1========> " . $deuc;
            send_notification($result);


        }else{

            $deuc = 2000;
            User::where('id','203')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Yek Count1========> " . $deuc;
            send_notification($result);

        }



//        if($user2 > 500000){
//            $deuc = 30000;
//            User::where('id','293395')->first()->decrement('main_wallet', $deuc);
//            User::where('id','293561')->first()->increment('main_wallet', $deuc);
//
//            $result = " Pla Count1========> " . $deuc;
//            send_notification($result);
//
//
//
//        }elseif($user2 > 200000){
//
//            $deuc = 15000;
//            User::where('id','293395')->first()->decrement('main_wallet', $deuc);
//            User::where('id','293561')->first()->increment('main_wallet', $deuc);
//
//            $result = " Pla Count1========> " . $deuc;
//            send_notification($result);
//
//        }elseif($user2 > 10000){
//
//            $deuc = 6000;
//            User::where('id','293395')->first()->decrement('main_wallet', $deuc);
//            User::where('id','293561')->first()->increment('main_wallet', $deuc);
//
//            $result = " Pla Count1========> " . $deuc;
//            send_notification($result);
//
//
//        }else{
//
//            $deuc = 2000;
//            User::where('id','293395')->first()->decrement('main_wallet', $deuc);
//            User::where('id','293561')->first()->increment('main_wallet', $deuc);
//
//            $result = " Pla Count1========> " . $deuc;
//            send_notification($result);
//
//        }
//




        if($user4 > 500000){
            $deuc = 30000;
            User::where('id','293494')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Ace Count1========> " . $deuc;
            send_notification($result);



        }elseif($user4 > 200000){

            $deuc = 15000;
            User::where('id','293494')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Ace Count1========> " . $deuc;
            send_notification($result);

        }elseif($user4 > 10000){

            $deuc = 6000;
            User::where('id','293494')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Ace Count1========> " . $deuc;
            send_notification($result);


        }else{

            $deuc = 2000;
            User::where('id','293494')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Ace Count1========> " . $deuc;
            send_notification($result);

        }




        if($user4 > 200000){
            $deuc = 10000;
            User::where('id','293494')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " ace Count3========> " . $deuc;
            send_notification($result);

        }elseif($user4 > 10000){

            $deuc = 6000;
            User::where('id','293494')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);
            $result = " ace Count3========> " . $deuc;
            send_notification($result);

        }else{

            $deuc = 2000;
            User::where('id','293494')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " ace Count3========> " . $deuc;
            send_notification($result);

        }


        if($user5 > 150000){
            $deuc = 10000;
            User::where('id','293554')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " LUG Count1========> " . $deuc;
            send_notification($result);

        }elseif($user5 > 10000){

            $deuc = 6000;
            User::where('id','293554')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " LUG Count1========> " . $deuc;
            send_notification($result);


        }else{

            $deuc = 2000;
            User::where('id','293554')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " LUG Count1========> " . $deuc;
            send_notification($result);

        }






        if($count3 > 2 && $user3 > 5000){
            $deuc = 100;
            User::where('id','214')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " San Count2========> " . $deuc;
            send_notification($result);


        }



        if($count4 > 5 && $user4 > 10000){
            $deuc = 1000;
            User::where('id','293369')->first()->decrement('main_wallet', $deuc);
            User::where('id','293561')->first()->increment('main_wallet', $deuc);

            $result = " Hik Count4========> " . $deuc;
            send_notification($result);


        }

        Feature::where('id', 1)->update(['pos_transfer' => 1]);


        $result = " result========> No Show | Transfer open";
        send_notification($result);


    }
}
