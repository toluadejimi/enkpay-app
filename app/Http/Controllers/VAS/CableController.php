<?php

namespace App\Http\Controllers\VAS;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

use Mail;

class CableController extends Controller
{

    public $success = true;
    public $failed = false;

    public function get_cable_plan()
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=dstv');
            $response = $request->getBody();
            $result = json_decode($response);
            $dstv = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=gotv');
            $response = $request->getBody();
            $result = json_decode($response);
            $gotv = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=startimes');
            $response = $request->getBody();
            $result = json_decode($response);
            $startimes = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=showmax');
            $response = $request->getBody();
            $result = json_decode($response);
            $showmax = $result->content->variations;



            return response()->json([
                'status' => $this->success,
                'dstv' => $dstv,
                'gotv' => $gotv,
                'startimes' => $startimes,
                'showmax' => $showmax,
                'account' => $account,
            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }
}
