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

class SelectionController extends Controller
{
    public function processModule($data) 
    {
        if (strpos($dataEncrypt["po_descs"], "\n") !== false) {
            $po_descs = str_replace("\n", ' (', $dataEncrypt["po_descs"]) . ')';
        } else {
            $po_descs = $dataEncrypt["po_descs"];
        }
        
        $list_of_urls = explode(',', $dataEncrypt["url_file"]);
        $list_of_files = explode(',', $dataEncrypt["file_name"]);

        $url_data = [];
        $file_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }
        
        $dataArray = array(
            'sender'        => $dataEncrypt["sender"],
            'entity_name'   => $dataEncrypt["entity_name"],
            'descs'         => $dataEncrypt["descs"],
            'user_name'     => $dataEncrypt["user_name"],
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'module'        => $dataEncrypt["module"],
            'body'          => "Please approve Quotation No. ".$dataEncrypt['po_doc_no']." for ".$po_descs,
            'subject'       => "Need Approval for Quotation No.  ".$dataEncrypt['po_doc_no'],
        );

        $data2Encrypt = array(
            'entity_cd'     => $dataEncrypt["entity_cd"],
            'project_no'    => $dataEncrypt["project_no"],
            'email_address' => $dataEncrypt["email_addr"],
            'level_no'      => $dataEncrypt["level_no"],
            'trx_date'      => $dataEncrypt["trx_date"],
            'doc_no'        => $dataEncrypt["doc_no"],
            'ref_no'        => $dataEncrypt["ref_no"],
            'usergroup'     => $dataEncrypt["usergroup"],
            'user_id'       => $dataEncrypt["user_id"],
            'supervisor'    => $dataEncrypt["supervisor"],
            'type'          => 'S',
            'type_module'   => 'PO',
            'text'          => 'Purchase Selection'
        );

        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddresses = $dataEncrypt["email_addr"];
        
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

    public function processData($module = '', $status='', $encrypt='')
    {
        $dataEncrypt = Crypt::decrypt($encrypt);

        $where = array(
            'doc_no'        => $dataEncrypt["doc_no"],
            'status'        => array("A","R","C"),
            'entity_cd'     => $dataEncrypt["entity_cd"],
            'level_no'      => $dataEncrypt["level_no"],
            'type'          => $dataEncrypt["type"],
            'module'        => $dataEncrypt["type_module"],
        );

        $query = DB::connection('BTID')
        ->table('mgr.cb_cash_request_appr')
        ->where($where)
        ->get();

        $where2 = array(
            'doc_no'        => $dataEncrypt["doc_no"],
            'status'        => 'P',
            'entity_cd'     => $dataEncrypt["entity_cd"],
            'level_no'      => $dataEncrypt["level_no"],
            'type'          => $dataEncrypt["type"],
            'module'        => $dataEncrypt["type_module"],
        );

        $query2 = DB::connection('BTID')
        ->table('mgr.cb_cash_request_appr')
        ->where($where2)
        ->get();

        if (count($query)>0) {
            $msg = 'You Have Already Made a Request to '.$dataEncrypt["text"].' No. '.$dataEncrypt["doc_no"] ;
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
            $msg = 'There is no '.$dataEncrypt["text"].' with No. '.$dataEncrypt["doc_no"] ;
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
            return view('email/pos/passcheckwithremark', $data);
        }
    }

    public function getAccess(Request $request)
    {
        var_dump($request->all());
    }
}
