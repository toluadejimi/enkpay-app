<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;

class TestController extends Controller
{
    function testevent(){

        Notification::send($user, new NewMessage($data));

    }
}
