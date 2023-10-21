<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terminal extends Model
{
    use HasFactory;

    protected $casts = [

        'amount' => 'integer',
        'serial_no' => 'string',
        'transfer_status' => 'integer'

    ];
}
