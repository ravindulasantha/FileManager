<?php

define('VFM_APP', true);

require_once 'config.php';

if ($_CONFIG['debug_mode'] === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL ^ E_NOTICE);
}

require_once 'class/class.setup.php';
require_once 'class/class.gatekeeper.php';

$setUp = new setUp();
$gateKeeper = new GateKeeper();

$_USERS = $gateKeeper->getUsers();
$firstrun = $setUp->getConfig('firstrun');
$script_url = $setUp->getConfig('script_url');

if ($firstrun !== true && strlen($_USERS[0]['pass']) > 0 && $script_url) {
    header('Location:'.$script_url);
    exit;
}

require_once 'class/class.utils.php';
require_once 'class/class.admin.php';
require_once 'class/class.updater.php';
$updater = new Updater();

$resetconfig = false;
$resetusr = false;

/**
 * Get the base url of the app
 */
if ($firstrun || !$script_url) {
    $actual_link = Admin::getAppUrl();
    $_CONFIG['script_url'] = $actual_link;
    $_CONFIG['firstrun'] = false;
    $resetconfig = true;
    $updater->updateUploadsDir();
}
/**
 * Create session name
 */
if (strlen($_CONFIG['session_name']) < 5) {
    $session = "vfm_".strval(mt_rand());
    $_CONFIG['session_name'] = $session;
    $resetconfig = true;
}
/**
 * Create unique app key
 */
if (strlen($_CONFIG['salt']) < 5) {
    $_CONFIG['salt'] = md5(mt_rand());
    $resetusr = true;
}

/**
 * Reset first SuperAdmin
 */
if (strlen($_USERS[0]['pass']) < 1 || $resetusr === true) {
    $reset = crypt($_CONFIG['salt'].urlencode('password'), Utils::randomString());
    $_USERS[0]['pass'] = $reset;
    $usr = '$_USERS = ';
    if (false == (file_put_contents('_content/users/users.php', "<?php\n\n $usr".var_export($_USERS, true).";\n"))) {
        Utils::setError("Error writing on <strong>_content/users/users.php</strong>, check CHMOD settings");
    }
}

/**
 * Update config.php file
 */
if ($resetusr === true || $resetconfig === true) {
    $con = '$_CONFIG = ';
    if (false == (file_put_contents('config.php', "<?php\n\n $con".var_export($_CONFIG, true).";\n"))) {
        Utils::setError("Error writing on <strong>/config.php</strong>, check CHMOD settings");
    }
}

$updater->updateHtaccess('./');

header('Location:'.$script_url);
exit;
