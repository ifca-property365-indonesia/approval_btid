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

    public function processData($status='', $encrypt='')
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
                "encrypt"   => $encrypt,
                "name"      => $name,
                "bgcolor"   => $bgcolor,
                "valuebt"   => $valuebt
            );
            return view('email/pos/passcheckwithremark', $data);
        }
    }

    public function getaccess(Request $request)
    {
        $data = Crypt::decrypt($request->encrypt);
        // var_dump($request->all());
    }
}
