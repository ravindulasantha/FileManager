<?php

if (file_exists(dirname(dirname(dirname(__FILE__))).'/.maintenance')) {
    exit('<h2>Briefly unavailable for scheduled maintenance. Check back in a minute.</h2>');
}
if (!defined('VFM_APP')) {
    return false;
}
if (version_compare(phpversion(), '5.5', '<')) {
    // PHP version too low.
    header('Content-type: text/html; charset=utf-8');
    exit('<h2>Veno File Manager 3 requires PHP >= 5.5</h2><p>Current: PHP '.phpversion().', please update your server settings.</p>');
}
if (!file_exists('vfm-admin/config.php')) {
    if (!copy('vfm-admin/config-master.php', 'vfm-admin/config.php')) {
        exit("failed to create the main config.php file, check CHMOD on /vfm-admin/");
    }
}

if (!file_exists('vfm-admin/_content/users/users.php')) {
    if (!copy('vfm-admin/_content/users/users-master.php', 'vfm-admin/_content/users/users.php')) {
        exit("failed to create the main users.php file, check CHMOD on /vfm-admin/_content/users/");
    }
}

require_once 'vfm-admin/class.php';

$setUp = new SetUp();

if ($setUp->getConfig('debug_mode') === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL ^ E_NOTICE);
}

// Redirect blacklisted IPs.
Utils::checkIP();
global $translations_index;
$translations_index = json_decode(file_get_contents('vfm-admin/translations/index.json'), true);

$gateKeeper = new GateKeeper();
$_USERS = $gateKeeper->getUsers();

if ($setUp->getConfig("firstrun") === true || strlen($_USERS[0]['pass']) < 1) {
    header('Location:vfm-admin/setup.php');
    exit;
}

$updater = new Updater();
$location = new Location();
$downloader = new Downloader();
$imageServer = new ImageServer();
$resetter = new Resetter();

$gateKeeper->init();
$updater->init();
$resetter->init();

$updater->updateUploadsDir();

if ($gateKeeper->isAccessAllowed()) {
    new Actions($location);
};

$template = new Template();

$getdownloadlist = filter_input(INPUT_GET, "dl", FILTER_SANITIZE_SPECIAL_CHARS);
$getrp = filter_input(INPUT_GET, "rp", FILTER_SANITIZE_SPECIAL_CHARS);
$getreg = filter_input(INPUT_GET, "reg", FILTER_SANITIZE_SPECIAL_CHARS);

$rtl_ext = '';
$rtl_att = '';
$rtl_class = '';
if ($setUp->getConfig("txt_direction") == "RTL") {
    $rtl_att = ' dir="rtl"';
    $rtl_ext = '.rtl';
    $rtl_class = ' rtl';
}
$bodyclass = 'vfm-body d-flex flex-column justify-content-between min-vh-100';
$bodyclass .= ($setUp->getConfig('inline_thumbs') == true) ? ' inlinethumbs' : '';
$bodyclass .= (!$gateKeeper->isAccessAllowed()) ? ' unlogged' : '';
$bodyclass .= ($setUp->getConfig('header_position') == 'below') ? ' pt-5' : '';
$bodyclass .= ' header-'.$setUp->getConfig('header_position');
$bodyclass .= ' role-'.$gateKeeper->getUserInfo('role');
$bodyclass .= $rtl_class;
$bodydata = $setUp->getConfig('audio_notification') ? ' data-ping="'.$setUp->getConfig('audio_notification').'"' : '';
