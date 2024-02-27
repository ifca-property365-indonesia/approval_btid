<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use App\Mail\FeedbackMail;
use App\Mail\StaffActionMail;
use Carbon\Carbon;

class StaffActionController extends Controller
{
    public function staffaction(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        if ($request->status == 'R') {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if ($request->status == 'C'){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if  ($request->status == 'A') {
            $action = 'Approval';
            $bodyEMail = 'Your Request has been Approved';
        }

        $EmailBack = array(
            'doc_no'            => $request->doc_no,
            'action'            => $action,
            'reason'            => $request->reason,
            'descs'             => $request->descs,
            'subject'		    => $request->subject,
            'bodyEMail'		    => $bodyEMail,
            'user_name'         => $request->user_name,
            'staff_act_send'    => $request->staff_act_send,
            'entity_name'       => $request->entity_name,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );

        try {
            $emailAddresses = $request->email_addr;
            $email_cc = $request->email_cc;
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                // foreach ($emails as $email) {
                //     Mail::to($email)->send(new StaffActionMail($EmailBack));
                // }

                foreach ($emails as $email) {
                    $mail = new StaffActionMail($EmailBack);
                    if (!empty($email_cc)) {
                        $mail->cc($email_cc);
                    }
                    Mail::to($email)->send($mail);
                }
                
                $sentTo = is_array($emailAddresses) ? implode(', ', $emailAddresses) : $emailAddresses;
                Log::channel('sendmail')->info('Email berhasil dikirim ke: ' . $sentTo);
                return "Email berhasil dikirim";
            } else {
                Log::channel('sendmail')->warning('Tidak ada alamat email yang diberikan.');
                return "Tidak ada alamat email yang diberikan.";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email. Cek log untuk detailnya.";
        }
    }

    public function fileexist(Request $request)
    {
        $file_name = $request->file_name;
        $folder_name = $request->folder_name;

        // Connect to FTP server
        $ftp_server = "34.101.201.127";
        $ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");

        // Log in to FTP server
        $ftp_user_name = "ifca_dev";
        $ftp_user_pass = "@Serangan1212";
        $login = ftp_login($ftp_conn, $ftp_user_name, $ftp_user_pass);

        $file = "ifca-att/".$folder_name."/".$file_name;

        if (ftp_size($ftp_conn, $file) >= 0) {
            echo "Ada File";
        } else {
            echo "Tidak Ada File";
        }

        ftp_close($ftp_conn);
    }
}
