<?php

namespace App\Http\Controllers;

class MarketingController extends Controller
{
    public function home()
    {
        return view('marketing.home');
    }

    public function pos()
    {
        return view('marketing.pos');
    }
}
