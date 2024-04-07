<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;

    protected $casts = [

        'name' => 'string',
        'acct_no' => 'string',
        'bank_code' => 'string',
        'user_id' => 'string',

    ];


}
