<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;

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

        \Illuminate\Support\Facades\Log::info("Cron is working fine!");

        $message = "Working Fine";
        send_notification($message);

        //
    }
}



