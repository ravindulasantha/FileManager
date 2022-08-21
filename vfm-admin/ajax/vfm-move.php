<?php

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once dirname(dirname(__FILE__)).'/class/class.actions.php';
require_once dirname(dirname(__FILE__)).'/class/class.downloader.php';
require_once dirname(dirname(__FILE__)).'/class/class.setup.php';
require_once dirname(dirname(__FILE__)).'/class/class.utils.php';
require_once dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';

$setUp = new SetUp();
$gateKeeper = new GateKeeper();
$downloader = new Downloader();
$actions = new Actions();
$copy = filter_input(INPUT_POST, 'copy', FILTER_VALIDATE_BOOLEAN);
$setmove = filter_input(INPUT_POST, 'setmove', FILTER_SANITIZE_SPECIAL_CHARS);
$dest = filter_input(INPUT_POST, 'dest', FILTER_SANITIZE_SPECIAL_CHARS);
$hash = filter_input(INPUT_POST, 'h', FILTER_SANITIZE_SPECIAL_CHARS);
$time = filter_input(INPUT_POST, 't', FILTER_SANITIZE_SPECIAL_CHARS);
// $setmove = htmlspecialchars($_POST['setmove']);
// $dest = htmlspecialchars($_POST['dest']);
// $hash = htmlspecialchars($_POST['h']);
// $time = htmlspecialchars($_POST['t']);

$salt = $setUp->getConfig('salt');
$starting_dir = $setUp->getConfig('starting_dir');

if ($hash && $time && $dest
    && $gateKeeper->isUserLoggedIn() 
    && ($gateKeeper->isAllowed('move_enable') || $gateKeeper->isAllowed('copy_enable'))
) { 
    if (md5($salt.$time) === $hash
        && $downloader->checkTime($time) == true
        && $setmove
    ) {
        $setmove = explode(',', $setmove);
        $destcoded = urldecode($dest);
        $dest = '../.'.$destcoded;
        if (strlen($dest) > strlen('../.'.$starting_dir)) {
            $cleandest = str_replace('../.'.$starting_dir, '', $dest);
        } else {
            $cleandest = $destcoded;
        }
        $counter = 0;
        $total = count($setmove);

        foreach ($setmove as $pezzo) {
            if ($downloader->checkFile($pezzo, '../') == true) {
                $filename = urldecode(base64_decode($pezzo));
                $myfile = '../../'.$filename;
                $filepathinfo = Utils::mbPathinfo($filename);
                $basename = $filepathinfo['basename'];
                
                if ($copy) {
                    $filesize = filesize($myfile);

                    if ($actions->checkUserSpace($myfile, $filesize) == false) {
                        Utils::setError('<i class="bi bi-x-circle"></i> '.$setUp->getString('available_space_exhausted').': <strong>'.$basename.'</strong> ('.$setUp->formatSize($filesize).') ');
                        echo "ok";
                        exit;
                    }
                }
                if ($actions->renameFile($myfile, $dest.'/'.$basename, $basename, true, $copy)) {
                    $counter++;
                }
            }
        }

        if ($counter > 0) {
            if ($total == 1) {
                $counter = $basename;
            }
            if ($copy) {
                Utils::setSuccess('<strong>'.$counter.'</strong> '.$setUp->getString('files_copied_to').': <strong>'.$cleandest.'</strong>');
            } else {
                Utils::setSuccess('<strong>'.$counter.'</strong> '.$setUp->getString('files_moved_to').': <strong>'.$cleandest.'</strong>');
            }
        }
        echo "ok";
        exit;
    } else {
        echo $setUp->getString('not_allowed');
    }
} else {
    echo "Not enough data";
}
exit;
