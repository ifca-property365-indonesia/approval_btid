<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendPoMail;

class PoOrderController extends Controller
{
    public function processModule($data) 
    {

        $list_of_urls = explode('; ', $data["url_file"]);
        $list_of_files = explode('; ', $data["file_name"]);
        $list_of_doc = explode('; ', $data["document_link"]);

        $url_data = [];
        $file_data = [];
        $doc_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        foreach ($list_of_doc as $doc) {
            $doc_data[] = $doc;
        }

        $list_of_supplier = explode('; ', $data["supplier_name"]);

        $supplier_data = [];

        foreach ($list_of_supplier as $supplier) {
            $supplier_data[] = $supplier;
        }

        $list_of_order_no = explode('; ', $data["order_no"]);

        $order_no_data = [];

        foreach ($list_of_order_no as $order_no) {
            $order_no_data[] = $order_no;
        }
        
        $list_of_order_remarks = explode('; ', $data["order_remarks"]);

        $order_remarks_data = [];

        foreach ($list_of_order_remarks as $order_remarks) {
            $order_remarks_data[] = $order_remarks;
        }

        $list_of_approve = explode('; ',  $data["approve_exist"]);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }

        $list_of_remark = explode('; ', $data["remark"]);

        $remark_data = [];

        foreach ($list_of_remark as $remark) {
            $remark_data[] = $remark;
        }

        $po_amt = number_format($data["po_amt"], 2, '.', ',');
        
        $dataArray = array(
            'module'        => "PoOrder",
            'sender'        => $data["sender"],
            'sender_addr'   => $data["sender_addr"],
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'entity_name'   => $data["entity_name"],
            'email_address' => $data["email_addr"],
            'descs'         => $data["descs"],
            'user_name'     => $data["user_name"],
            'approve_list'  => $approve_data,
            'clarify_user'  => $data["clarify_user"],
            'clarify_email' => $data["clarify_email"],
            'supplier_name' => $supplier_data,
            'po_amt'        => $po_amt,
            'order_no'      => $order_no_data,
            'order_remarks' => $order_remarks_data,
            'remark'        => $remark_data,
            'doc_link'      => $doc_data,
            'body'          => "Please approve Purchase Order No. ".$data['doc_no'],
            'subject'       => "Need Approval for Purchase Order No.  ".$data['doc_no'],
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
            'type'          => 'A',
            'type_module'   => 'PO',
            'text'          => 'Purchase Order'
        );

        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddress = strtolower($data["email_addr"]);
            $doc_no = $data["doc_no"];
            // Check if email address is set, not empty, and a valid email address
            if (isset($emailAddress) && !empty($emailAddress) && filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                Mail::to($emailAddress)->send(new SendPoMail($encryptedData, $dataArray));
                
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
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_order ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
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
            $msg = "You Have Successfully ".$descstatus." the Purchase Order No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Purchase Order No.".$data["doc_no"];
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
