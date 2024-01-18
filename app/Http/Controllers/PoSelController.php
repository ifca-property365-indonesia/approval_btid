<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class PoSelController extends Controller
{
    public function inpuytdata(Request $request)
    {
        return "Email berhasil dikirim";
    }
}
