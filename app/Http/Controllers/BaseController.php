<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Requests;

class BaseController extends Controller
{
    //
    use Helpers;

    /**
     *  BaseController constructor
     */
    public function __construct()
    {
        //
    }

    public function Test(Request $request) {
        return [1];
    }
}
