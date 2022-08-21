<?php
/**

 * Generate zip archive

 */
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
@set_time_limit(0);

require_once  dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';
require_once  dirname(dirname(__FILE__)).'/class/class.zipper.php';
require_once  dirname(dirname(__FILE__)).'/class/class.setup.php';
require_once  dirname(dirname(__FILE__)).'/class/class.utils.php';

$setUp = new SetUp();
$zipper = new Zipper();

$getfiles = is_array($_POST['files']) ? filter_var_array($_POST['files'], FILTER_SANITIZE_SPECIAL_CHARS) : false;
$getfolder = filter_input(INPUT_POST, 'folder', FILTER_SANITIZE_SPECIAL_CHARS);
$time = filter_input(INPUT_POST, "time", FILTER_SANITIZE_SPECIAL_CHARS);
$hash = filter_input(INPUT_POST, 'dash', FILTER_SANITIZE_SPECIAL_CHARS);
$onetime = filter_input(INPUT_POST, 'onetime', FILTER_SANITIZE_SPECIAL_CHARS);
// $getfiles = is_array($_POST['files']) ? filter_var_array($_POST['files']) : false;
// $getfolder = htmlspecialchars($_POST['folder']);
// $time = htmlspecialchars($_POST['time']);
// $hash = htmlspecialchars($_POST['dash']);
// $onetime = htmlspecialchars($_POST['onetime']);

$alt = $setUp->getConfig('salt');
$altone = $setUp->getConfig('session_name');

$dozip = false;
$folder = false;
$files = false;

if (!$hash) {
    echo json_encode(array('error'=>$setUp->getString('access_denied')));
    exit;
}

if ($getfolder && $hash === md5($alt.$getfolder.$altone)) {
    $folder = base64_decode($getfolder);
    $filename = $folder;
    $dozip = true;
}

if ($getfiles && $hash === md5($alt.$time)) {
    $files = $getfiles;
    $dozip = true;
}

if ($dozip === true) {
    $zippedfile = $zipper->prepareZip($files, $folder);
    if ($onetime && $onetime !== '0') {
        $sharefile = dirname(dirname(__FILE__)). '/_content/share/'.$onetime.'.json';
        if (file_exists($sharefile)) {
            unlink($sharefile);
        }
    }
    echo json_encode($zippedfile);
    exit;
}
echo json_encode(array('error'=>$setUp->getString('nothing_found')));
exit;
