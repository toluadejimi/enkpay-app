<?php

namespace App\Console\Commands;

use App\Models\Feature;
use App\Models\Setting;
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
    protected $signature = 'app:close-manual';

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

        Setting::where('id', 1)->update([
            'opay_trx' => 0,
            'palmpay_trx' => 0,
        ]);

        $result = " Manual Account Locked";
        send_notification($result);


    }
}
