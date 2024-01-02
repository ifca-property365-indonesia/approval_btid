<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
        $data = Crypt::decrypt($encrypt);

        $where = array(
            'doc_no'        => $data["doc_no"],
            'status'        => array("A",'R', 'C'),
            'entity_cd'     => $data["entity_cd"],
            'level_no'      => $data["level_no"],
            'type'          => $data["type"],
            'module'        => $data["type_module"],
        );

        $query = DB::connection('BTID')
        ->table('mgr.cb_cash_request_appr')
        ->where($where)
        ->get();

        $where2 = array(
            'doc_no'        => $data["doc_no"],
            'status'        => 'P',
            'entity_cd'     => $data["entity_cd"],
            'level_no'      => $data["level_no"],
            'type'          => $data["type"],
            'module'        => $data["type_module"],
        );

        $query2 = DB::connection('BTID')
        ->table('mgr.cb_cash_request_appr')
        ->where($where2)
        ->get();

        if (count($query)>0) {
            $msg = 'You Have Already Made a Request to '.$data["text"].' No. '.$data["doc_no"] ;
            $notif = 'Restricted !';
            $st  = 'OK';
            $image = "double_approve.png";
            $msg1 = array(
                "Pesan" => $msg,
                "St" => $st,
                "notif" => $notif,
                "image" => $image
            );
            return view("email.after", $msg1);
        } else if (count($query2) == 0){
            $msg = 'There is no '.$data["text"].' with No. '.$data["doc_no"] ;
            $notif = 'Restricted !';
            $st  = 'OK';
            $image = "double_approve.png";
            $msg1 = array(
                "Pesan" => $msg,
                "St" => $st,
                "notif" => $notif,
                "image" => $image
            );
            return view("email.after", $msg1);
        } else {
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
            // Attempt to connect to the database
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
