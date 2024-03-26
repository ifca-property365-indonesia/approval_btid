<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendPLLymanMail;

class PlBudgetLymanController extends Controller
{
    public function processModule($data) 
    {
        $amount = number_format( $data["amount"] , 2 , '.' , ',' );

        $list_of_approve = explode('; ',  $data["approve_exist"]);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }

        $dataArray = array(
            'descs'         => $data["descs"],
            'entity_name'   => $data["entity_name"],
            'project_name'  => $data["project_name"],
            'amount'        => $amount,
            'doc_no'        => $data["doc_no"],
            'user_name'     => $data["user_name"],
            'sender'        => $data["sender"],
            'module'        => $data["module"],
            'approve_list'  => $approve_data,
            'clarify_user'  => $data['clarify_user'],
            'clarify_email' => $data['clarify_email'],
            'sender_addr'   => $data['sender_addr'],
            'body'          => "Please approve RAB Budget No. ".$data['doc_no']." project ".$data["project_name"]. " with Amount ".$amount,
            'subject'       => "Need Approval for RAB Budget No. ".$data['doc_no'],
        );

        $data2Encrypt = array(
            'entity_cd'     => $data["entity_cd"],
            'project_no'    => $data["project_no"],
            'email_address' => $data["email_addr"],
            'level_no'      => $data["level_no"],
            'doc_no'        => $data["doc_no"],
            'user_id'       => $data["user_id"],
            'type'          => 'B',
            'type_module'   => 'PL',
            'text'          => 'Budget Lyman'
        );  

        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddress = strtolower($data["email_addr"]);
            $doc_no = $data["doc_no"];
            // Check if email address is set, not empty, and a valid email address
            if (isset($emailAddress) && !empty($emailAddress) && filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                Mail::to($emailAddress)->send(new SendPLLymanMail($encryptedData, $dataArray));
                
                // Log the sent email address
                Log::channel('sendmail')->info('Email doc_no ' . $doc_no . ' berhasil dikirim ke: ' . $emailAddress);
                
                return "Email Doc No ".$doc_no." berhasil dikirim ke: " . $emailAddress;
            } else {
                // Log and return a warning if email address is invalid or not provided
                Log::channel('sendmail')->warning('Alamat email '.$emailAddress.' tidak valid atau tidak diberikan.');
                return "Alamat email ".$emailAddress." tidak valid atau tidak diberikan.";
            }
        } catch (\Exception $e) {
            // Log and return an error if an exception occurs
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email: " . $e->getMessage();
        }
    }

    public function update($status, $encrypt, $reason)
    {
        $data = Crypt::decrypt($encrypt);

        $descstatus = " ";
        $imagestatus = " ";
        $msg = " ";
        $msg1 = " ";
        $notif = " ";
        $st = " ";
        $image = " ";

        if ($status == "A") {
            $descstatus = "Approved";
            $imagestatus = "approved.png";
        } else if ($status == "R") {
            $descstatus = "Revised";
            $imagestatus = "revise.png";
        } else {
            $descstatus = "Cancelled";
            $imagestatus = "reject.png";
        }
        $pdo = DB::connection('BTID')->getPdo();
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.xrl_send_mail_approval_pl_budget_lyman ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $status);
        $sth->bindParam(5, $data["level_no"]);
        $sth->bindParam(6, $data["user_id"]);
        $sth->execute();
        if ($sth == true) {
            $msg = "You Have Successfully ".$descstatus." the RAB Budget No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the RAB Budget No.".$data["doc_no"];
            $notif = 'Fail to '.$descstatus.' !';
            $st = 'OK';
            $image = "reject.png";
        }
        $msg1 = array(
            "Pesan" => $msg,
            "St" => $st,
            "notif" => $notif,
            "image" => $image
        );
        return view("email.after", $msg1);
    }
}
