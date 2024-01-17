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
            'status'        => array("A","R","C"),
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
            return view('email/passcheckwithremark', $data);
        }
    }

    public function getAccess(Request $request)
    {
        $status = $request->status;
        $encrypt= $request->encrypt;
        $email=$request->email;
        $module=$request->module;
        $reason=$request->reason;
        if (empty($request->reason)) {
            $reason = '0';
        }
        var_dump($module);
        // try {
        //     // Attempt to connect to the database
        //     $controller = 'App\\Http\\Controllers\\' . $module . 'Controller';
        //     $methodName = 'update';
        //     $arguments = [$status, $encrypt, $reason];
        //     $result = call_user_func_array([$controller, $methodName], $arguments);
        //     return $result;
        // } catch (\Exception $e) {
        //     $msg1 = array(
        //         "Pesan" => "Cannot find Module",
        //         "image" => "reject.png"
        //     );
        //     return view("email.after", $msg1);
        // }
    }
}
