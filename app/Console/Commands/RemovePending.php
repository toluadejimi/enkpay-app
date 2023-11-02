<?php

namespace App\Console\Commands;

use App\Models\Charge;
use App\Models\PendingTransaction;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Webtransfer;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;

class RemovePending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pending';

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

        WebTransfer::where('status', 0)->where('created_at','>', Carbon::now()->subMinutes(5))->delete();

        $message = "Transaction Deleted";

        send_notification($message);

    }

}
