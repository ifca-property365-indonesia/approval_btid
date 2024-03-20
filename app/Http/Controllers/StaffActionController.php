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
use App\Mail\StaffActionPoRMail;
use App\Mail\StaffActionPoSMail;
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
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
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
            $doc_no = $request->doc_no;
            // Check if email addresses are provided and not empty
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                foreach ($emails as $email) {
                    Mail::to($email)->send(new StaffActionMail($EmailBack));
                }
                
                $sentTo = is_array($emailAddresses) ? implode(', ', $emailAddresses) : $emailAddresses;
                Log::channel('sendmail')->info('Email Feedback doc_no '.$doc_no.' berhasil dikirim ke: ' . $sentTo);
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

    public function staffaction_por(Request $request)
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
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
        }

        $list_of_urls = explode('; ', $request->url_file);
        $list_of_files = explode('; ', $request->file_name);
        $list_of_doc = explode('; ', $request->doc_link);

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
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'doc_link'          => $doc_data,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );

        try {
            $emailAddresses = $request->email_addr;
            $email_cc = $request->email_cc;

            $entity_cd = $request->entity_cd;
            $doc_no = $request->doc_no;
            $request_type = $request->request_type;
            $type = $request->type;
            $module= $request->moduledb;
            $email_status = 'Y';
            $audit_user = 'MGR';

            $currentTime = Carbon::now();
            // Format the date and time
            $formattedDateTime = $currentTime->format('d-m-Y H:i:s');
        
            // Explode the email addresses strings into arrays
            $emails = !empty($emailAddresses) ? (is_array($emailAddresses) ? $emailAddresses : [$emailAddresses]) : [];
            $cc_emails = !empty($email_cc) ? explode(';', $email_cc) : [];
        
            // Remove duplicates from CC emails
            $cc_emails = array_unique($cc_emails);
        
            // Remove email addresses from CC list if they exist in the email addresses list
            $cc_emails = array_diff($cc_emails, $emails);
        
            if (!empty($emails)) {
                foreach ($emails as $email) {
                    $mail = new StaffActionPoRMail($EmailBack);
        
                    // Set CC emails
                    foreach ($cc_emails as $cc_email) {
                        $mail->cc(trim($cc_email));
                    }
        
                    Mail::to($email)->send($mail);
                }
        
                $sentTo = implode(', ', $emails);
                $ccList = implode(', ', $cc_emails);
                Log::channel('sendmail')->info("Email Feedback doc_no ".$doc_no." berhasil dikirim ke: " . $sentTo . " & CC ke : " . $ccList);
                var_dump($entity_cd);
                var_dump($doc_no);
                var_dump($request_type);
                var_dump($type);
                var_dump($module);
                var_dump($sentTo);
                var_dump($ccList);
                var_dump($email_status);
                var_dump($formattedDateTime);
                return "Email berhasil dikirim ke: " . $sentTo . " & CC ke : " . $ccList;
            } else {
                Log::channel('sendmail')->warning('Tidak ada alamat email yang diberikan.');
                return "Tidak ada alamat email yang diberikan.";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email. Cek log untuk detailnya.";
        }        
    }

    public function staffaction_pos(Request $request)
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
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
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
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );

        try {
            $emailAddresses = $request->email_addr;
            $doc_no = $request->doc_no;
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                foreach ($emails as $email) {
                    Mail::to($email)->send(new StaffActionPoSMail($EmailBack));
                }
                
                $sentTo = is_array($emailAddresses) ? implode(', ', $emailAddresses) : $emailAddresses;
                Log::channel('sendmail')->info('Email Feedback doc_no '.$doc_no.' berhasil dikirim ke: ' . $sentTo);
                return 'Email berhasil dikirim ke: ' . $sentTo;
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