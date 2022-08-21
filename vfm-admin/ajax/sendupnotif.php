<?php
/**

 * Sends upload notification e-mail to selected users

 */
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once dirname(dirname(__FILE__)).'/class/class.utils.php';
require_once dirname(dirname(__FILE__)).'/class/class.setup.php';
require_once dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';
require_once dirname(dirname(__FILE__)).'/class/class.logger.php';

$lang = filter_input(INPUT_POST, 'thislang', FILTER_SANITIZE_SPECIAL_CHARS);
$senduser = filter_input(INPUT_POST, 'senduser', FILTER_VALIDATE_EMAIL, FILTER_REQUIRE_ARRAY);
$postpath = filter_input(INPUT_POST, 'path', FILTER_SANITIZE_SPECIAL_CHARS);
$postfilename = filter_input(INPUT_POST, 'filename', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
$uploader_message = filter_input(INPUT_POST, 'uploader_message', FILTER_SANITIZE_SPECIAL_CHARS);

// $lang = htmlspecialchars($_POST['thislang']);
// $senduser = filter_input(INPUT_POST, 'senduser', FILTER_VALIDATE_EMAIL, FILTER_REQUIRE_ARRAY);
// $postpath = htmlspecialchars($_POST['path']);
// $postfilename = filter_input(INPUT_POST, 'filename', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
// $uploader_message = htmlspecialchars($_POST['uploader_message']);

use PHPMailer\PHPMailer\PHPMailer;

$setUp = new SetUp();
$gateKeeper = new GateKeeper();

$appname = $setUp->getConfig('appname');
$time = $setUp->formatModTime(time());

// Send Log notification for multiple uploads
if (strlen($setUp->getConfig('upload_email')) > 5 && $setUp->getConfig('notify_upload')) {
    $title = $setUp->getString('new_upload');
    $path = urldecode($postpath);

    $message = $time."\n\n";
    $message .= "IP : ".Logger::getClientIP()."\n";
    $message .= $setUp->getString('user')." : ".$gateKeeper->getUserInfo('name')."\n";
    $message .= $setUp->getString('path')." : ".$path."\n";
    $message .= $setUp->getString('files')." : \n";

    foreach ($postfilename as $filename) {
        $message .= $path.$filename."\n";
    }
        
    $sendTo = $setUp->getConfig('upload_email');
    $from = "=?UTF-8?B?".base64_encode($appname)."?=";
    mail(
        $sendTo,
        "=?UTF-8?B?".base64_encode($title)."?=",
        $message,
        "Content-type: text/plain; charset=UTF-8\r\n".
        "From: ".$from." <noreply@{$_SERVER['SERVER_NAME']}>\r\n".
        "Reply-To: ".$from." <noreply@{$_SERVER['SERVER_NAME']}>"
    );
}

// Send notification to selected users
if ($senduser) {
    $setfrom = $setUp->getConfig('email_from');

    if ($setfrom == null) {
        echo $setUp->getString("setup_email_application")."<br>";
        exit;
    }

    $fullpath = urldecode($postpath);
    $path = str_replace($setUp->getConfig('starting_dir'), '', $fullpath);
    $basepath = str_replace('./', '', $setUp->getConfig('starting_dir'));

    $appurl = $setUp->getConfig('script_url');
    $title = $setUp->getString("new_upload")." - ".$appname;
    $name = $gateKeeper->getUserInfo('name');

    $altmessage = $time."\n\n";
    $altmessage .= $appurl."\n\n";
    $altmessage .= $setUp->getString('from')." : ".$name."\n\n";

    $upfiles = '<p>'.$time.'</p>';
    $upfiles = '<p>'.$setUp->getString("location").': <a href="'.$appurl.'?dir='.$basepath.$path.'"><strong>'.$path.'</strong></a></p>';
    $upfiles .= '<ul>';

    foreach ($postfilename as $filename) {
        $upfiles .= '<li>'.$filename.'</li>';
        $altmessage .= ' - '.$path.$filename.'\n';
    }
    $upfiles .= '</ul>';

    include_once dirname(dirname(__FILE__)).'/assets/mail/vendor/autoload.php';

    $mail = new PHPMailer();

    $mail->CharSet = 'UTF-8';
    $mail->setLanguage($lang);

    if ($setUp->getConfig('smtp_enable') == true) {
        $mail->isSMTP();
        
        $smtp_auth = $setUp->getConfig('smtp_auth');
        $mail->Host = $setUp->getConfig('smtp_server');
        $mail->Port = (int)$setUp->getConfig('port');
        if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                )
            );
        }
        if ($setUp->getConfig('secure_conn') !== "none") {
            $mail->SMTPSecure = $setUp->getConfig('secure_conn');
        }
        
        $mail->SMTPAuth = $smtp_auth;

        if ($smtp_auth == true) {
            $mail->Username = $setUp->getConfig('email_login');
            $mail->Password = $setUp->getConfig('email_pass');
        }
    }

    $mail->setFrom($setfrom, $appname);
    $mail->Subject = $title;

    $email_logo = $setUp->getConfig('email_logo', false) ? '../_content/uploads/'.$setUp->getConfig('email_logo') : '../images/px.png';
    $mail->AddEmbeddedImage($email_logo, 'logoimg');

    // Retrieve the email template required
    $message = file_get_contents('../_content/mail-template/template-uploaded-files.html');

    $message = str_replace('%app_url%', $appurl, $message);
    $message = str_replace('%app_name%', $appname, $message);
    $message = str_replace('%translate_from%', $setUp->getString('from'), $message);
    $message = str_replace('%username%', $name, $message);
    $message = str_replace('%upfiles%', $upfiles, $message);
    $message = str_replace('%uploader_message%', nl2br($uploader_message), $message);

    $mail->msgHTML($message);

    $mail->AltBody = $altmessage;

    // send notification mail to each selected user
    foreach ($senduser as $sender) {
        $mail->addAddress($sender);

        if (!$mail->send()) {
            echo "Error sending mail";
        }
        $mail->ClearAddresses();
    }
    // // send notification mail to the uploader user
    // $mail->addAddress($gateKeeper->getUserInfo('email'), '<'.$gateKeeper->getUserInfo('email').'>');
    // $mail->send();
    // $mail->ClearAddresses();
}
