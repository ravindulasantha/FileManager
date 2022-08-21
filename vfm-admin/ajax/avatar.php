<?php
/**

 * Save a new avatar image
 *

 */
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
/**
 * Create a thumb from the uploaded image canvas

 */
function base64ToJpg($base64_string, $output_file) 
{
    $ifp = fopen($output_file, "wb"); 
    $data = explode(',', $base64_string);
    fwrite($ifp, base64_decode($data[1])); 
    fclose($ifp);
}

$relativepath = dirname(dirname(__FILE__)). '/_content/avatars';
if (!is_dir($relativepath)) {
    mkdir($relativepath, 0755);         
}

$postimg = filter_input(INPUT_POST, 'imgData', FILTER_SANITIZE_SPECIAL_CHARS);
$imgname = filter_input(INPUT_POST, 'imgName', FILTER_SANITIZE_SPECIAL_CHARS);

$relative = $relativepath.'/'.$imgname.'.png';

if ($postimg) {
	$finalavatar = 'vfm-admin/_content/avatars/'.$imgname.'.png';
	base64ToJpg($postimg, $relative);
} else {
	if (file_exists($relative)) {
		unlink($relative);
	}
	$finalavatar = false;
}
echo $finalavatar;
