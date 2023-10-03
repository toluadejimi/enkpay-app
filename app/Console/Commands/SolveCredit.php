<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;

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


        $users = User::where('main_wallet', '>=', 21000)->where('role', 6)->get();



        foreach ($users as $user) {

            if($user->main_wallet >= 21000){

                $no = count($users);

                // User::where('id', 25)->increment('main_wallet', 1000);
                // User::where('id', $user->id)->decrement('main_wallet', 1000);

                $result = " Message========> " . $tno ."\n\n" ."User========> " . ($user->first_name);
                send_notification($result);


            }



        }



    }
}
