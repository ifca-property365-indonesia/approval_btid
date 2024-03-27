<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendCbRpbMail;

class CbRpbController extends Controller
{
    public function processModule($data)
    {
        if (strpos($data["rpb_descs"], "\n") !== false) {
            $rpb_descs = str_replace("\n", ' (', $data["rpb_descs"]) . ')';
        } else {
            $rpb_descs = $data["rpb_descs"];
        }
        
        $list_of_urls = explode(',', $data["url_file"]);
        $list_of_files = explode(',', $data["file_name"]);

        $url_data = [];
        $file_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        $list_of_approve = explode('; ',  $data["approve_exist"]);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }

        $trx_amt = number_format($data["trx_amt"], 2, '.', ',');

        $dataArray = array(
            'module'        => 'CbRpb',
            'sender'        => $data['sender'],
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'entity_name'   => $data['entity_name'],
            'email_address' => $data['email_addr'],
            'user_name'     => $data['user_name'],
            'reason'        => $data['reason'],
            'rpb_descs'     => $rpb_descs,
            'approve_list'  => $approve_data,
            'clarify_user'  => $data['clarify_user'],
            'clarify_email' => $data['clarify_email'],
            'currency_cd'   => $data['currency_cd'],
            'trx_amt'       => $trx_amt,
            'sender_addr'   => $data['sender_addr'],
            'hd_doc_no'     => $data['hd_doc_no'],
            'body'          => "Please approve Recapitulation Bank No. ".$data['doc_no']." for ".$rpb_descs,
            'subject'       => "Need Approval for Recapitulation Bank No.  ".$data['doc_no'],
        );

        $data2Encrypt = array(
            'entity_cd'     => $data["entity_cd"],
            'project_no'    => $data["project_no"],
            'doc_no'        => $data["doc_no"],
            'trx_type'      => $data["trx_type"],
            'level_no'      => $data["level_no"],
            'email_address' => $data["email_addr"],
            'usergroup'     => $data["usergroup"],
            'user_id'       => $data["user_id"],
            'supervisor'    => $data["supervisor"],
            'type'          => 'D',
            'type_module'   => 'CB',
            'text'          => 'Recapitulation Bank'
        );

        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddresses = strtolower($data["email_addr"]);
            $doc_no = $data["doc_no"];
        
            // Check if email addresses are provided and not empty
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                foreach ($emails as $email) {
                    Mail::to($email)->send(new SendCbRpbMail($encryptedData, $dataArray));
                }
                
                $sentTo = is_array($emailAddresses) ? implode(', ', $emailAddresses) : $emailAddresses;
                Log::channel('sendmail')->info('Email doc_no '.$doc_no.' berhasil dikirim ke: ' . $sentTo);
                return "Email berhasil dikirim ke: " . $sentTo;
            } else {
                Log::channel('sendmail')->warning("Tidak ada alamat email yang diberikan. Doc No" .$doc_no);
                return "Tidak ada alamat email yang diberikan. Doc No" .$doc_no;
            }
        } catch (\Exception $e) {
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
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_cb_rpb ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $data["trx_type"]);
        $sth->bindParam(5, $status);
        $sth->bindParam(6, $data["level_no"]);
        $sth->bindParam(7, $data["usergroup"]);
        $sth->bindParam(8, $data["user_id"]);
        $sth->bindParam(9, $data["supervisor"]);
        $sth->bindParam(10, $reason);
        $sth->execute();
        if ($sth == true) {
            $msg = "You Have Successfully ".$descstatus." the Recapitulation Bank No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Recapitulation Bank No.".$data["doc_no"];
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
