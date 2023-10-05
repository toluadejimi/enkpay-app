<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'debit',
        'serial_no'
    ];



    protected $casts = [
        'user_id'=> 'integer',
        'debit' => 'double',
        'credit' => 'double',
        'balance' => 'double',
        'amount' => 'double',
        'fee' => 'double',
        'from_user_id' => 'integer',
        'main_wallet' => 'integer',
        'terminal_id' => 'integer',
        'status' => 'integer',
        'e_charges' => 'integer',
        'charge' => 'double',
        'enkPay_Cashout_profit' => 'double',
        'enkPay_Cashout_profit' => 'double',
        'resolve' => 'integer',


        


    ];


}
