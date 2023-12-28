<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use App\Mail\StaffActionMail;
use Carbon\Carbon;

class StaffActionController extends Controller
{
    public function processModule($data)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        if ($data["status"] == 'R') {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$data["descs"].' No. '.$data["doc_no"].' with the reason';

        } else if ($data["status"] == 'C'){
            
            $action = 'Cancellation';
            $bodyEMail = $data["descs"].' No. '.$data["doc_no"].' has been cancelled with the reason';

        }

        $EmailBack = array(
            'doc_no'            => $data["doc_no"],
            'action'            => $action,
            'reason'            => $data["reason"],
            'descs'             => $data["descs"],
            'subject'		    => $data["subject"],
            'bodyEMail'		    => $bodyEMail,
            'user_name'         => $data["user_name"],
            'staff_act_send'    => $data["staff_act_send"],
            'entity_name'       => $data["entity_name"],
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );

        try {
            $emailAddresses = $data["email_addr"];
            if (!empty($emailAddresses)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                
                $newPath = storage_path('logs/'.$data["module"].'/sendmail.log');
                
                Config::set('logging.channels.sendmail.path', $newPath);
                
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
}
