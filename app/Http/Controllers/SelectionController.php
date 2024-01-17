<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Mail\SendMail;
use PDO;
use DateTime;

class SelectionController extends Controller
{
    public function update($status, $encrypt, $reason)
    {
        $data = Crypt::decrypt($encrypt);
        $trx_date = $data["trx_date"];
        $dateTime = DateTime::createFromFormat('d-m-Y', $trx_date);
        $formattedDate = $dateTime->format('d-m-Y');

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
        $sth->bindParam(4, $data["ref_no"]);
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
