<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendMail;

class CbPpuController extends Controller
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

        $dataArray = array(
            'sender'        => $data['sender'],
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'entity_name'   => $data['entity_name'],
            'email_address' => $data['email_addr'],
            'descs'         => $data['descs'],
            'user_name'     => $data['user_name'],
            'reason'        => $data['reason'],
            'module'        => 'CbPpu',
            'body'          => "Please approve Payment Request No. ".$data['ppu_no']." for ".$ppu_descs,
            'subject'       => "Need Approval for Payment Request No.  ".$data['ppu_no'],
        );

        $data2Encrypt = array(
            'entity_cd'     => $data["entity_cd"],
            'project_no'    => $data["project_no"],
            'email_address' => $data["email_addr"],
            'level_no'      => $data["level_no"],
            'doc_no'        => $data["doc_no"],
            'trx_type'      => $data["trx_type"],
            'usergroup'     => $data["usergroup"],
            'user_id'       => $data["user_id"],
            'supervisor'    => $data["supervisor"],
            'type'          => 'U',
            'type_module'   => 'CB',
            'text'          => 'Payment Request'
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

        // Execute the stored procedure using Laravel's database query builder
        try {
            $result = DB::connection('sqlsrv')->select("EXEC mgr.x_send_mail_approval_cb_ppu ?, ?, ?, ?, ?, ?, ?, ?, ?, ?", [
                $data["entity_cd"],
                $data["project_no"],
                $data["doc_no"],
                $data["trx_type"],
                $status,
                $data["level_no"],
                $data["usergroup"],
                $data["user_id"],
                $data["supervisor"],
                $reason,
            ]);

            // Check the result and set messages accordingly
            if ($result) {
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

            // Prepare data for the view
            $msg1 = [
                "Pesan" => $msg,
                "St" => $st,
                "notif" => $notif,
                "image" => $image
            ];

            return view("email.after", $msg1);
        } catch (\Exception $e) {
            // Handle exceptions (if any)
            return "Error: " . $e->getMessage();
        }
    }
}
