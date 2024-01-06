<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletedWebtransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'trans_id',
        'user_id',
        'status',
        'amount',
        'v_account_no',
        'v_account_name',
        'bank_name',
        'web_charges',
        'payable_amount',
        'wc_order',
        'sender',
        'account_no',
        'note',
        'client_id',
        'amount',
        'total_received',
        'url',
        'email',
        'webhook',
        'key',
        'data',
        'adviceReference',
        'ref',

    ];




}
