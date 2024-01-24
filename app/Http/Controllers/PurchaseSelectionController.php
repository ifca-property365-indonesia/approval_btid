<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendPoSMail;
use PDO;
use DateTime;

class PurchaseSelectionController extends Controller
{
    public function Mail(Request $request)
    {
        if (strpos($request->po_descs, "\n") !== false) {
            $po_descs = str_replace("\n", ' (', $request->po_descs) . ')';
        } else {
            $po_descs = $request->po_descs;
        }
        
        $list_of_urls = explode('; ', $request->url_file);
        $list_of_files = explode('; ', $request->file_name);

        $url_data = [];
        $file_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        $list_of_approve = explode('; ',  $request->approve_exist);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }
        
        $dataArray = array(
            'ref_no'        => $request->ref_no,
            'po_doc_no'     => $request->po_doc_no,
            'po_descs'      => $po_descs,
            'supplier_name' => $request->supplier_name,
            'sender'        => $request->sender,
            'sender_addr'   => $request->sender_addr,
            'entity_name'   => $request->entity_name,
            'descs'         => $request->descs,
            'user_name'     => $request->user_name,
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'approve_list'  => $approve_data,
            'clarify_user'  => $request->clarify_user,
            'clarify_email' => $request->clarify_email,
            'body'          => "Please approve Quotation No. ".$request->po_doc_no." for ".$po_descs,
            'subject'       => "Need Approval for Quotation No.  ".$request->po_doc_no,
        );

        $data2Encrypt = array(
            'entity_cd'     => $request->entity_cd,
            'project_no'    => $request->project_no,
            'doc_no'        => $request->doc_no,
            'request_no'    => $request->request_no,
            'trx_date'      => $request->trx_date,
            'level_no'      => $request->level_no,
            'usergroup'     => $request->usergroup,
            'user_id'       => $request->user_id,
            'supervisor'    => $request->supervisor,
            'type'          => 'S',
            'type_module'   => 'PO',
            'text'          => 'Purchase Selection'
        );

        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddresses = $request->email_addr;
        
            // Check if email addresses are provided and not empty
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                foreach ($emails as $email) {
                    Mail::to($email)->send(new SendPoSMail($encryptedData, $dataArray));
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

    public function processData($status = '', $encrypt = '')
    {
        $data = Crypt::decrypt($encrypt);
        $trx_date = $data["trx_date"];
        // $dateTime = DateTime::createFromFormat('d-m-Y', $trx_date);
        // $formattedDate = $dateTime->format('d-m-Y');
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
        $sth = $pdo->prepare("EXEC mgr.x_send_mail_approval_po_selection ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $data["request_no"]);
        $sth->bindParam(5, $data["trx_date"]);
        $sth->bindParam(6, $status);
        $sth->bindParam(7, $data["level_no"]);
        $sth->bindParam(8, $data["usergroup"]);
        $sth->bindParam(9, $data["user_id"]);
        $sth->bindParam(10, $data["supervisor"]);
        $sth->bindParam(11, $reason);
        $sth->execute();
        if ($sth == true) {
            $msg = "You Have Successfully ".$descstatus." the Purchase Selection No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Purchase Selection No.".$data["doc_no"];
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
