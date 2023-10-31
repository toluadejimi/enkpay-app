<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class CustomizedTransactionPDFMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $transaction;

    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    public function build()
    {
        return $this->subject('Bank Transaction Report')
            ->view('emails.customized_transaction_report') // Blade view for email content
            ->attach(
                Storage::path('app/pdf_reports/' . $this->transaction->user_id . '.pdf'),
                ['as' => 'transaction_report.pdf', 'mime' => 'application/pdf']
            );
    }
}
