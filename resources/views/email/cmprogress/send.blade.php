<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="application/pdf">
    <meta name="x-apple-disable-message-reformatting">
    <title></title>
    
    <style>
        body {
            font-family: Arial;
            margin: 0;
            padding: 0 !important;
            mso-line-height-rule: exactly;
            background-color: #ffffff;
            font-family: Arial;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .custom-table {
                background-color:#e0e0e0;"
            }

        td {
            padding: 8px;
        }

        @media only screen and (max-width: 620px) {
            table {
                width: 100% !important;
            }

            td {
                display: block;
                width: 100% !important;
                box-sizing: border-box;
            }
            .custom-table {
                background-color:#ffffff;"
            }
            
        }
    </style>
</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #ffffff;font-family: Arial;">
	<div style="width: 100%; background-color: #ffffff; text-align: center;">
        <table width="80%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="margin-left: auto;margin-right: auto;" >
            <tr>
               <td style="padding: 40px 0;">
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding-bottom:25px">
                                    <img width = "120" src="{{ url('public/images/KuraKuraBali-logo.jpg') }}" alt="logo">
                                        <p style="font-size: 16px; color: #026735; padding-top: 0px;">{{ $dataArray['entity_name'] }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;background-color:#e0e0e0;">
                        <tbody>
                            <tr>
                                <td style="padding: 30px 30px">
                                    <h5 style="text-align:left;margin-bottom: 24px; color: #000000; font-size: 20px; font-weight: 400; line-height: 28px;">Dear {{ $dataArray['user_name'] }}, </h5>
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 16px;">{{ $dataArray['body'] }}.</p><br>
                                    <table style="width:100%;max-width:620px;margin:0 auto;background-color:#e0e0e0;">
                                        <tr>
                                            <td style="text-align: left; padding-right: 10px;">Progress No</td>
                                            <td>:</td>
                                            <td>{{ $dataArray['progress_no'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; padding-right: 10px;">Current Progress Percentage</td>
                                            <td>:</td>
                                            <td>{{ $dataArray['curr_progress'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; padding-right: 10px;">Amount Current Progress</td>
                                            <td>:</td>
                                            <td>{{ $dataArray['amount'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; padding-right: 10px;">Previous Progress Percentage</td>
                                            <td>:</td>
                                            <td>{{ $dataArray['prev_progress'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; padding-right: 10px;">Amount Previous Progress</td>
                                            <td>:</td>
                                            <td>{{ $dataArray['prev_progress_amt'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; padding-right: 10px;">Contract No</td>
                                            <td>:</td>
                                            <td>{{ $dataArray['contract_no'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; padding-right: 10px;">In Entity</td>
                                            <td>:</td>
                                            <td>{{ $dataArray['entity_name'] }}</td>
                                        </tr>
                                    </table>                                    
                                    <a href="{{ url('api') }}/processdata/{{ $dataArray['module'] }}/A/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #1ee0ac; border-radius: 4px; color: #ffffff;">Approve</a>
                                    <a href="{{ url('api') }}/processdata/{{ $dataArray['module'] }}/R/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #f4bd0e; border-radius: 4px; color: #ffffff;">Revise</a>
                                    <a href="{{ url('api') }}/processdata/{{ $dataArray['module'] }}/C/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #e85347; border-radius: 4px; color: #ffffff;">Cancel</a>
                                    <br><p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 16px;">
                                        <b>Thank you,</b><br>
                                        {{ $dataArray['sender'] }}
                                    </p><br>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding:25px 20px 0;">
                                    <p style="font-size: 13px;">Copyright © 2023 IFCA Software. All rights reserved.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
               </td>
            </tr>
        </table>
        </div>
</body>
</html>