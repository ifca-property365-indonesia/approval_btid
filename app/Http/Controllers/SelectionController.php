<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendMail;
use PDO;
use DateTime;

class SelectionController extends Controller
{
    public function processModule($data) 
    {
        return ($data);
    }
}
