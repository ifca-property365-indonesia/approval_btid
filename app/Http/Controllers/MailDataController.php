<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MailDataController extends Controller
{
    public function receive(Request $request)
    {
        $dataFromExternal = $request->all();
        $module = $request->module;
        $controller = 'App\\Http\\Controllers\\' . $module . 'Controller';
        $methodName = 'processModule';
        $arguments = [$dataFromExternal];
        $result = call_user_func_array([$controller, $methodName], $arguments);
        return $result;
    }

    public function processData($module='', $status='', $encrypt='')
    {
        if ($status == 'A') {
            $name   = 'Approval';
            $bgcolor = '#40de1d';
            $valuebt  = 'Approve';
        } else if ($status == 'R') {
            $name   = 'Revision';
            $bgcolor = '#f4bd0e';
            $valuebt  = 'Revise';
        } else {
            $name   = 'Cancellation';
            $bgcolor = '#e85347';
            $valuebt  = 'Cancel';
        }
        $dataArray = Crypt::decrypt($encrypt);
        $data = array(
            "status"    => $status,
            "doc_no"    => $dataArray["doc_no"],
            "email"     => $dataArray["email_address"],
            "module"    => $module,
            "encrypt"   => $encrypt,
            "name"      => $name,
            "bgcolor"   => $bgcolor,
            "valuebt"   => $valuebt
        );
        if ($status == "A"){
            return view('email/passcheck', $data);
        } else {
            return view('email/passcheckwithremark', $data);
        }
    }

    public function getAccess(Request $request)
    {
        $status = $request->status;
        $encrypt= $request->encrypt;
        $password=$request->password;
        $email=$request->email;
        $module=$request->module;
        $reason=$request->reason;
        if (empty($request->reason)) {
            $reason = '0';
        }

        $where = array('email' => $email);
        $data = DB::connection('BTID')
                    ->table('mgr.security_users')
                    ->where($where)
                    ->select('name')
                    ->first();

        $servername = getenv('DB_HOST3') ?: 'uat.ifca.co.id';
        $port = getenv('DB_PORT3') ?: '1436';
        $dbname = getenv('DB_DATABASE3') ?: 'BTID_LIVE';
        $username = $data->name;
        $password = $password;

        try {
            $connection = new \PDO("sqlsrv:Server=$servername,$port;Database=$dbname", $username, $password);
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $controller = 'App\\Http\\Controllers\\' . $module . 'Controller';
            $methodName = 'update';
            $arguments = [$status, $encrypt, $reason];
            $result = call_user_func_array([$controller, $methodName], $arguments);
            return $result;
        } catch (\Exception $e) {
            if ($e->getCode() == 2002 || $e->getCode() == 50000) {
                $msg = "Server not found or connection failed.";
            } elseif ($e->getCode() == 28000) {
                $msg = "Authentication failed: Invalid username or password.";
            } else {
                $msg = "Connection failed: " . $e->getMessage();
            }
            $msg1 = array(
                "Pesan" => $msg,
                "image" => "reject.png"
            );
            return view("email.after", $msg1);
        }
    }
}
