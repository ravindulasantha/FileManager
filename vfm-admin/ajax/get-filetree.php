<?php
/**

 * Display folder tree
 *

 */
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once dirname(dirname(__FILE__)).'/class/class.utils.php';
require_once dirname(dirname(__FILE__)).'/class/class.setup.php';
require_once dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';
require_once dirname(dirname(__FILE__)).'/class/class.actions.php';

$setUp = new SetUp();
$gateKeeper = new GateKeeper();

$currentdir = base64_decode(filter_input(INPUT_POST, 'currentdir', FILTER_SANITIZE_SPECIAL_CHARS));
$__root = filter_input(INPUT_POST, '__root', FILTER_SANITIZE_SPECIAL_CHARS);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

$movedir = $setUp->getConfig('starting_dir');

$href = ($action == 'breadcrumbs') ? true : false;
$movelink = ($action == 'breadcrumbs') ? '' : 'movelink';

// check if any folder is assigned to the current user
if ($gateKeeper->getUserInfo('dir') !== null) {
    $userpatharray = array();
    $userpatharray = json_decode($gateKeeper->getUserInfo('dir'), true);

    $output = '<div class="wrap-foldertree"><span class="toggle-all-tree"><i class="bi bi-dash-square-fill tree-toggler"></i></span>';

    // Natural sort order
    natcasesort($userpatharray);
    // show all available directories trees
    foreach ($userpatharray as $userdir) {
        $path = $setUp->getConfig('starting_dir').$userdir.'/';

        $output .= '<ul class="foldertree">';
        $output .= '<li class="folderoot">';

        if ($path === $currentdir) {
            $output .= '<i class="bi bi-folder2-open"></i> <span class="search-highlight">'.$userdir.'</span>';
        } else {
            $output .= '<a href="?dir='.ltrim($path, './').'" data-dest="'.urlencode($path).'" class="'.$movelink.'">';
            $output .= '<i class="bi bi-folder"></i> '.$userdir;
            $output .= '</a>';
        }
        $output .= Actions::walkDir($path, $currentdir, $href, '../.');
        $output .= '</li></ul>';
    }
    $output .= '</div>';

    echo json_encode($output);

} else {
    // no directory assigned, access to all folders
    $movedir = $setUp->getConfig('starting_dir');
    $cleandir = substr($setUp->getConfig('starting_dir'), 2);
    $cleandir = substr_replace($cleandir, '', -1);
    $cleandir = strlen($cleandir) > 0 ? $cleandir : $__root;

    $output = '<div class="wrap-foldertree"><span class="toggle-all-tree"><i class="bi bi-dash-square-fill tree-toggler"></i></span>';

    $output .= '<ul class="foldertree">';
    $output .= '<li class="folderoot">';

    if ($movedir === $currentdir) {
        $output .= '<i class="bi bi-folder2-open"></i> <span class="search-highlight">'.$cleandir.'</span>';
    } else {
        $output .= '<a href="?dir='.ltrim($movedir, './').'" data-dest="'.urlencode($movedir).'" class="'.$movelink.'">';
        $output .= '<i class="bi bi-folder"></i> '.$cleandir;
        $output .= '</a>';
    }

    $output .= Actions::walkDir($movedir, $currentdir, $href, '../.');
    $output .= '</li></ul></div>';
    
    echo json_encode($output);
}
exit;
