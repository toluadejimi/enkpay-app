<?php

namespace App\Console\Commands;

use App\Models\EmailSend;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EmailPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-push';

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


        $mail = EmailSend::where('status', 0)->first() ?? null;

        if($mail != null) {

            $user_email = User::where('email', $mail->receiver_email)->first()->email ?? null;
            if ($user_email !== null) {

                $data = array(
                    'fromsender' => 'noreply@enkpay.com', 'EnkPay',
                    'subject' => "Bank Transfer",
                    'toreceiver' => $mail->receiver_email,
                    'amount' => $mail->amount,
                    'first_name' => $mail->first_name,
                );

                Mail::send('emails.transaction.banktransfer', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });


                EmailSend::where('receiver_email', $mail->receiver_email)->delete();

            }
        }
    }
}
