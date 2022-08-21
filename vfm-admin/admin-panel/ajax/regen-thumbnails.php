<?php

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once dirname(dirname(dirname(__FILE__))).'/class/class.setup.php';
require_once dirname(dirname(dirname(__FILE__))).'/class/class.utils.php';
require_once dirname(dirname(dirname(__FILE__))).'/class/class.gatekeeper.php';

$setUp = new SetUp();
$gateKeeper = new GateKeeper();

if (!$gateKeeper->isSuperAdmin()) {
    die('Permission Denied');
}

foreach (glob(dirname(dirname(dirname(__FILE__))).'/_content/thumbs/*.jpg', GLOB_NOSORT) as $deletable) {
    unlink($deletable);
}
echo 'success';
exit;
