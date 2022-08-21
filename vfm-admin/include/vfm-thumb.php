<?php

require_once dirname(dirname(__FILE__)).'/class/class.setup.php';
$setUp = new SetUp();

if ($setUp->getConfig('debug_mode') === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
require_once dirname(dirname(__FILE__)).'/class/class.imageserver.php';
require_once dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';
require_once dirname(dirname(__FILE__)).'/class/class.utils.php';
$gateKeeper = new GateKeeper();

if (!$gateKeeper->isAccessAllowed() && $setUp->getConfig('share_thumbnails') !== true) {
    die('access denied');
}
$imageServer = new ImageServer();
$imageServer->showImage();

exit;