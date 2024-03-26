<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendCbPpuMail;

class CbPpuVvipController extends Controller
{
    public function processModule($data)
    {
        if (strpos($data["ppu_descs"], "\n") !== false) {
            $ppu_descs = str_replace("\n", ' (', $data["ppu_descs"]) . ')';
        } else {
            $ppu_descs = $data["ppu_descs"];
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

        $ppu_amt = number_format($data["ppu_amt"], 2, '.', ',');

        $dataArray = array(
            'module'        => 'CbPpuVvip',
            'ppu_no'        => $data['ppu_no'],
            'ppu_descs'     => $data['ppu_descs'],
            'sender'        => $data['sender'],
            'sender_addr'   => $data['sender_addr'],
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'entity_name'   => $data['entity_name'],
            'descs'         => $data['descs'],
            'user_name'     => $data['user_name'],
            'reason'        => $data['reason'],
            'pay_to'        => $data['pay_to'],
            'forex'         => $data['forex'],
            'ppu_amt'       => $ppu_amt,
            'approve_list'  => $approve_data,
            'clarify_user'  => $data['clarify_user'],
            'clarify_email' => $data['clarify_email'],
            'body'          => "Please approve Payment Request No. ".$data['ppu_no']." for ".$ppu_descs,
            'subject'       => "Need Approval for Payment Request No.  ".$data['ppu_no'],
        );

        $data2Encrypt = array(
            'entity_cd'     => $data["entity_cd"],
            'project_no'    => $data["project_no"],
            'doc_no'        => $data["doc_no"],
            'trx_type'      => $data["trx_type"],
            'level_no'      => $data["level_no"],
            'usergroup'     => $data["usergroup"],
            'user_id'       => $data["user_id"],
            'supervisor'    => $data["supervisor"],
            'email_address' => $data["email_addr"],
            'type'          => 'V',
            'type_module'   => 'CB',
            'text'          => 'Payment Request'
        );

        

        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddress = strtolower($data["email_addr"]);
            $doc_no = $data["doc_no"];
            // Check if email address is set, not empty, and a valid email address
            if (isset($emailAddress) && !empty($emailAddress) && filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                Mail::to($emailAddress)->send(new SendCbPpuMail($encryptedData, $dataArray));
                
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
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_cb_ppu_vvip ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
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
            $msg = "You have successfully ".$descstatus." the Payment Request No. ".$data["doc_no"];
            $notif = $descstatus."!";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You failed to ".$descstatus." the Payment Request No.".$data["doc_no"];
            $notif = 'Fail to '.$descstatus.'!';
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
