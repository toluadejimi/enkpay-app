<?php

namespace App\Console\Commands;

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

        $fisteen = Carbon::now()->subMinutes(15);
        Webtransfer::where('created_at', '<=', $fisteen)
        ->where('status', 0)
        ->delete();

        $time5 = Carbon::now()->subMinutes(5);
        Webtransfer::where('created_at', '<=', $time5)
        ->where('status', 1)
        ->delete();

        $timefive = Carbon::now()->subMinutes(5);
        $data = VirtualAccount::where('updated_at', '<=', $timefive)
        ->update(['state' => 0]);

        $timefive = Carbon::now()->subMinutes(30);
        $data = Transaction::where('created_at', '<=', $timefive)
        ->where('status', 9)
        ->delete();


        $message = "Send:cron Successful";
        send_notification($message);


    }
}
