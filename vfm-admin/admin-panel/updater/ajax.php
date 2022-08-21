<?php
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once dirname(dirname(dirname(__FILE__))).'/class/class.setup.php';
require_once dirname(dirname(dirname(__FILE__))).'/class/class.gatekeeper.php';

$setUp = new SetUp();
$gateKeeper = new GateKeeper();

if (!$gateKeeper->canSuperAdmin('superadmin_can_preferences')) {
    $response = array(
        'error' => '403 Forbidden'
    );
    echo json_encode($response);
    exit();
}

require_once dirname(__FILE__).'/class.vfmupdater.php';
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

$response = false;
switch ($action){
case 'download':
        $response = VFM_updater()->startUpdate();
    break;
case 'expand':
        $response = VFM_updater()->expandPackage();
    break;
case 'prepare':
        $response = VFM_updater()->preparePackage();
    break;
case 'replace':
        $response = VFM_updater()->replaceFiles();
    break;
case 'end':
        $response = VFM_updater()->endProcess();
    break;
case 'getkey':
        $response = $setUp->getConfig("license_key");;
    break;
default:
    $response = false;
    break;
}
echo json_encode($response);
exit();
