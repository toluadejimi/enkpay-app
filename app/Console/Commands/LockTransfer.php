<?php

namespace App\Console\Commands;

use App\Models\Feature;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class LockTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:lock-transfer';

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

        Feature::where('id', 1)->update(['pos_transfer' => 0]);

        $result = " Transfer out Blocked";
        send_notification($result);


    }
}
