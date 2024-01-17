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

class PoSelectionController extends Controller
{
    public function processModule($data) 
    {

        if (strpos($data["po_descs"], "\n") !== false) {
            $po_descs = str_replace("\n", ' (', $data["po_descs"]) . ')';
        } else {
            $po_descs = $data["po_descs"];
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
        
        $dataArray = array(
            'sender'        => $data["sender"],
            'entity_name'   => $data["entity_name"],
            'descs'         => $data["descs"],
            'user_name'     => $data["user_name"],
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'module'        => "PoSelection",
            'body'          => "Please approve Quotation No. ".$data['po_doc_no']." for ".$po_descs,
            'subject'       => "Need Approval for Quotation No.  ".$data['po_doc_no'],
        );

        $data2Encrypt = array(
            'entity_cd'     => $data["entity_cd"],
            'project_no'    => $data["project_no"],
            'email_address' => $data["email_addr"],
            'level_no'      => $data["level_no"],
            'trx_date'      => $data["trx_date"],
            'doc_no'        => $data["doc_no"],
            'ref_no'        => $data["ref_no"],
            'usergroup'     => $data["usergroup"],
            'user_id'       => $data["user_id"],
            'supervisor'    => $data["supervisor"],
            'type'          => 'S',
            'type_module'   => 'PO',
            'text'          => 'Purchase Selection'
        );

        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddresses = $data["email_addr"];
        
            // Check if email addresses are provided and not empty
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                foreach ($emails as $email) {
                    Mail::to($email)->send(new SendMail($encryptedData, $dataArray));
                }
                
                $sentTo = is_array($emailAddresses) ? implode(', ', $emailAddresses) : $emailAddresses;
                Log::channel('sendmail')->info('Email berhasil dikirim ke: ' . $sentTo);
                return "Email berhasil dikirim ke: " . $sentTo;
            } else {
                Log::channel('sendmail')->warning('Tidak ada alamat email yang diberikan.');
                return "Tidak ada alamat email yang diberikan.";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email: " . $e->getMessage();
        }
    }

    public function update($status, $encrypt, $reason)
    {
        $data = Crypt::decrypt($encrypt);
        $trx_date = $data["trx_date"];
        $dateTime = DateTime::createFromFormat('d-m-Y', $trx_date);
        $formattedDate = $dateTime->format('d-m-Y');

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
        var_dump($data);
        // var_dump($data["entity_cd"]);
        // var_dump($data["project_no"]);
        // var_dump($data["doc_no"]);
        // var_dump($data["ref_no"]);
        // var_dump($data["trx_date"]);
        // var_dump($status);
        // var_dump($data["level_no"]);
        // var_dump($data["usergroup"]);
        // var_dump($data["user_id"]);
        // var_dump($data["supervisor"]);
        // var_dump($data["reason"]);
        $pdo = DB::connection('BTID')->getPdo();
        $sth = $pdo->prepare("EXEC mgr.x_send_mail_approval_po_selection ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $data["ref_no"]);
        $sth->bindParam(5, $data["trx_date"]);
        $sth->bindParam(6, $status);
        $sth->bindParam(7, $data["level_no"]);
        $sth->bindParam(8, $data["usergroup"]);
        $sth->bindParam(9, $data["user_id"]);
        $sth->bindParam(10, $data["supervisor"]);
        $sth->bindParam(11, $reason);
        $sth->execute();
        var_dump($sth);
        // if ($sth == true) {
        //     $msg = "You Have Successfully ".$descstatus." the Purchase Selection No. ".$data["doc_no"];
        //     $notif = $descstatus." !";
        //     $st = 'OK';
        //     $image = $imagestatus;
        // } else {
        //     $msg = "You Failed to ".$descstatus." the Purchase Selection No.".$data["doc_no"];
        //     $notif = 'Fail to '.$descstatus.' !';
        //     $st = 'OK';
        //     $image = "reject.png";
        // }
        // $msg1 = array(
        //     "Pesan" => $msg,
        //     "St" => $st,
        //     "notif" => $notif,
        //     "image" => $image
        // );
        // return view("email.after", $msg1);
    }
}