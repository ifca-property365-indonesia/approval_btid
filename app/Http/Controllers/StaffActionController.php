<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
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
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason';

        } else if ($request->status == 'C'){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason';

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
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                foreach ($emails as $email) {
                    Mail::to($email)->send(new StaffActionMail($EmailBack));
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

        // $urlcheck = 'http://35.219.16.171/file/'.$folder_name.'/'.$file_name;
        $urlcheck = 'http://uat.ifca.co.id:8080/FTP/BTID/'.$folder_name.'/'.$file_name;

        $response2 = Http::get($urlcheck);
        if( $response2->successful() ) {
            echo "Ada File";
        } else {
            echo "Tidak Ada File";
        }
    }
}
