<?php
/**

 * Generate short sharing link
 *

 */
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once  dirname(dirname(__FILE__)).'/class/class.utils.php';
require_once  dirname(dirname(__FILE__)).'/class/class.downloader.php';
require_once  dirname(dirname(__FILE__)).'/class/class.setup.php';

$utils = new Utils;
$downloader = new Downloader();
$setUp = new SetUp();

$attachments = filter_input(INPUT_POST, "atts", FILTER_SANITIZE_SPECIAL_CHARS);
$time = filter_input(INPUT_POST, "time", FILTER_SANITIZE_SPECIAL_CHARS);
$hash = filter_input(INPUT_POST, "hash", FILTER_SANITIZE_SPECIAL_CHARS);
$pass = filter_input(INPUT_POST, "pass", FILTER_SANITIZE_SPECIAL_CHARS);

if (strlen($pass) > 0) {
    $hpass = md5($pass);
} else {
    $hpass = false;
}

$saveData = array();

$saveData['pass'] = $hpass;
$saveData['time'] = $time;
$saveData['hash'] = $hash;
$saveData['attachments'] = $attachments;
$json_name = md5($time.$attachments.$pass);
/** 
 * Use this second function $attacash
 * to shorten the name to 12 chars instead of default 32
 */
// $json_name = substr(md5($time.$attachments.$pass), 0, 12);

// create the temporary directory
if (!is_dir(dirname(dirname(__FILE__)).'/_content/share')) {
    mkdir(dirname(dirname(__FILE__)).'/_content/share', 0755, true);
}
// save dowloadable link if it does not already exists
if (!file_exists(dirname(dirname(__FILE__)).'/_content/share/'.$json_name.'.json') || $pass!==false) {
    $fp = fopen(dirname(dirname(__FILE__)).'/_content/share/'.$json_name.'.json', 'w');
    fwrite($fp, json_encode($saveData));
    fclose($fp);
}
// remove old files
$shortens = glob(dirname(dirname(__FILE__))."/_content/share/*.json");

foreach ($shortens as $shorten) {
    if (is_file($shorten)) {
        $filetime = filemtime($shorten);

        if ($downloader->checkTime($filetime) == false) {
            unlink($shorten);
        }
    }
}
echo $json_name;
