<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TransferData extends Command
{
    protected $signature = 'data:transfer';  // Command name
    protected $description = 'Transfer data from source to destination database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Fetch data from the source database
        $sourceData = DB::connection('mysql_source')->table('users')->get();

        if ($sourceData->isEmpty()) {
            $this->info('No data to transfer');
            return;
        }

        // Transfer data to the destination database
        foreach ($sourceData as $data) {
            DB::connection('mysql_dest')->table('users')->insert([
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                // Add other columns as needed
            ]);
        }

        $this->info('Data transfer completed successfully');
    }
}
