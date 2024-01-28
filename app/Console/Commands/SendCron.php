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
use App\Models\CompletedWebtransfer;

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

        // $fisteen = Carbon::now()->subHour();
        // Webtransfer::where('created_at', '>=', $fisteen)
        // ->where('status', 0)
        // ->delete();

       
        $dataToMove =  Webtransfer::where('status', 1)->get();
        foreach ($dataToMove as $item) {
          CompletedWebtransfer::updateOrCreate(['id' => $item->id], $item->toArray());
        }

         Webtransfer::where('status', 1)
        ->delete();

        $timefive = Carbon::now()->subMinutes(5);
        VirtualAccount::where('updated_at', '>=', $timefive)
        ->update(['state' => 0]);



        // $data =  $dataToMove =  Webtransfer::where('status', 1)->count();

        // $message = $data;
        // send_notification($message);


    }
}
