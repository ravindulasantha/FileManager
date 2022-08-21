<?php

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once dirname(dirname(__FILE__)).'/class/class.setup.php';
require_once dirname(dirname(__FILE__)).'/class/class.utils.php';
require_once dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';

$setUp = new SetUp();
$gateKeeper = new GateKeeper();

if (!$gateKeeper->isAccessAllowed()) {
    die();
}
// update list view
$listview = filter_input(INPUT_POST, "listview", FILTER_SANITIZE_SPECIAL_CHARS);
// $listview = htmlspecialchars($_POST['listview']);
if ($listview) {
    $listdefault = $setUp->getConfig('list_view') ? $setUp->getConfig('list_view') : 'list';
    $listtype = $listview ? $listview : $listdefault;
    $_SESSION['listview'] = $listtype;
}

// update table paging lenght
$ilenght = filter_input(INPUT_POST, "iDisplayLength", FILTER_VALIDATE_INT);
if ($ilenght) {
    $_SESSION['ilenght'] = $ilenght;
}

$sort_col = filter_input(INPUT_POST, "sort_col", FILTER_VALIDATE_INT);
$sort_order = filter_input(INPUT_POST, "sort_order", FILTER_SANITIZE_SPECIAL_CHARS);
// $sort_order = htmlspecialchars($_POST['sort_order']);
if ($sort_col && $sort_order) {
    $_SESSION['sort_col'] = $sort_col;
    $_SESSION['sort_order'] = $sort_order;
}
$dirlenght = filter_input(INPUT_POST, "dirlenght", FILTER_VALIDATE_INT);
if ($dirlenght) {
    $_SESSION['dirlenght'] = $dirlenght;
}
$sort_dir_col = filter_input(INPUT_POST, "sort_dir_col", FILTER_VALIDATE_INT);
$sort_dir_order = filter_input(INPUT_POST, "sort_dir_order", FILTER_SANITIZE_SPECIAL_CHARS);
// $sort_dir_order = htmlspecialchars($_POST['sort_dir_order']);
if ($sort_dir_col && $sort_dir_order) {
    $_SESSION['sort_dir_col'] = $sort_dir_col;
    $_SESSION['sort_dir_order'] = $sort_dir_order;
}
exit;
