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

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
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
            $emailAddresses = strtolower($request->email_addr);
            $doc_no = $request->doc_no;
            // Check if email address is set, not empty, and a valid email address
            if (isset($emailAddress) && !empty($emailAddress) && filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                Mail::to($emailAddress)->send(new StaffActionMail($EmailBack));
                
                // Log the sent email address
                Log::channel('sendmail')->info('Email Feedback doc_no ' . $doc_no . ' berhasil dikirim ke: ' . $emailAddress);
                
                return "Email berhasil dikirim ke: " . $emailAddress;
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

    public function staffaction_por(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
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
            $emailAddress = strtolower($request->email_addr);
            $emailCc = strtolower($request->email_cc); // Add this line to get CC email address
            $doc_no = $request->doc_no;
            
            // Check if email address is set, not empty, and a valid email address
            if (isset($emailAddress) && !empty($emailAddress) && filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $mail = Mail::to($emailAddress);
                
                // Process CC addresses
                $ccAddresses = [];
                if (isset($emailCc) && !empty($emailCc)) {
                    $ccAddresses = explode(';', $emailCc);
                    $ccAddresses = array_map('trim', $ccAddresses); // Trim whitespaces
                    $ccAddresses = array_filter($ccAddresses, 'filter_var', FILTER_VALIDATE_EMAIL); // Filter valid email addresses
                    
                    // Filter out invalid email addresses
                    $ccAddresses = array_filter($ccAddresses, function($ccAddress) {
                        return !empty($ccAddress);
                    });
                    
                    // Add CC if provided
                    foreach ($ccAddresses as $ccAddress) {
                        $mail->cc($ccAddress);
                    }
                }
                
                $mail->send(new StaffActionPoRMail($EmailBack));
                
                // Log the sent email address
                $logMessage = "Email Feedback doc_no " . $doc_no . " berhasil dikirim ke: " . $emailAddress;
                if (!empty($ccAddresses)) {
                    $logMessage .= " & CC-nya ke: " . implode(', ', $ccAddress);
                }
                Log::channel('sendmail')->info($logMessage);
                return $logMessage;
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

    public function staffaction_pos(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
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
            $emailAddresses = strtolower($request->email_addr);
            $doc_no = $request->doc_no;
            // Check if email address is set, not empty, and a valid email address
            if (isset($emailAddress) && !empty($emailAddress) && filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                Mail::to($emailAddress)->send(new StaffActionPoSMail($EmailBack));
                
                // Log the sent email address
                Log::channel('sendmail')->info('Email Feedback doc_no ' . $doc_no . ' berhasil dikirim ke: ' . $emailAddress);
                
                return "Email berhasil dikirim ke: " . $emailAddress;
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