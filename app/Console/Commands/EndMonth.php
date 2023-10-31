<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TransactionPDFMail;
use PDF;


class EndMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:endmonth';

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


        $currentMonth = now()->startOfMonth();
        $transactions = Transaction::whereMonth('created_at', $currentMonth->month)
                                    ->get();

        foreach ($transactions as $transaction) {
            $pdf = PDF::loadView('pdf.transaction', ['transaction' => $transaction]);
            $pdf->save(storage_path('app/transaction_reports/' . $transaction->id . '.pdf'));

            // Send the PDF report via email
            Mail::to($transaction->user->email)
                ->send(new TransactionPDFMail($transaction));
        }



    }
}
