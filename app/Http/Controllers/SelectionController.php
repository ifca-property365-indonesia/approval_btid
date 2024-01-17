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
            'module'        => $data["module"],
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
        var_dump($module);
    }
}
