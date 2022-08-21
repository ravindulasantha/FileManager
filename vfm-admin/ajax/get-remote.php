<?php
/**

 * Download files from remote URL

 */
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once dirname(dirname(__FILE__)).'/class/class.utils.php';
require_once dirname(dirname(__FILE__)).'/class/class.setup.php';
require_once dirname(dirname(__FILE__)).'/class/class.location.php';
require_once dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';

$location_dir = urldecode(filter_input(INPUT_POST, 'get_location', FILTER_SANITIZE_SPECIAL_CHARS));
$location = new Location('../../'.$location_dir);
$setUp = new SetUp();
$gateKeeper = new GateKeeper();

if ($location->editAllowed('../../')
    && $gateKeeper->isAllowed('upload_enable')
    && $setUp->getConfig('remote_uploader') === true
) {
    include_once dirname(dirname(__FILE__)).'/class/class.actions.php';
    include_once dirname(dirname(__FILE__)).'/class/class.uploader.php';
    include_once dirname(dirname(__FILE__)).'/class/class.logger.php';

    $uploader = new Uploader();

    if (!function_exists('curl_version')) {
        Utils::setError('<span><i class="bi bi-exclamation-triangle"></i> Enable server Curl');
        exit;
    }

    $getfile = filter_input(INPUT_POST, 'get_upload_url', FILTER_VALIDATE_URL);

    if (!$getfile) {
        Utils::setError('<span><i class="bi bi-exclamation-triangle"></i> '.$setUp->getLangString('invalid_url'));
        exit;
    }

    $getfile = Uploader::removeQueryString($getfile);

    // Get size of remote file.
    $filesize = $uploader->getRemoteSize($getfile);

    if (!$filesize) {
        exit;
    }

    $getfileinfo = Utils::mbPathinfo($getfile);
    $extension = Utils::getFileExtension($getfile);
    $filename = Utils::normalizeStr(Utils::checkMagicQuotes($getfileinfo['filename']));
    // append date to filename
    $filename = $setUp->getConfig('overwrite_files') === 'date' ? $filename.'_'.date('Y-m-d_G-i-s') : $filename;

    $setfile = '../../'.$location_dir.Uploader::safeExtension($filename.'.'.$extension, $extension);

    // Check if file can be uploaded.
    if (!$uploader->veryFile($setfile, $filesize, true)) {
        exit;
    }

    $ch = curl_init($getfile);
    curl_setopt_array(
        $ch, 
        array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => false,
        )
    );

    $getcleanfile = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_error($ch)) {
        Utils::setError(curl_error($ch));
        exit;
    }

    if (false === $getcleanfile) {
        $message = ' <span><i class="bi bi-exclamation-triangle"></i> '.$setUp->getLangString('error_downloading_remote_file').'</span>';
        Utils::setError(' <span><i class="bi bi-exclamation-triangle"></i> '.$message.'</span>');
    }

    if (false === file_put_contents($setfile, $getcleanfile)) {
        $message = '<span><i class="bi bi-exclamation-triangle"></i> '.$setUp->getLangString('failed_upload').'</span> ';
        Utils::setError($message);

    } else {
        Actions::updateUserSpace(false, false, $filesize);
        $message = array(
            'user' => $gateKeeper->getUserInfo('name'),
            'action' => 'ADD',
            'type' => 'file',
            'item' => $location_dir.$filename.'.'.$extension,
        );
        Logger::log($message, '../');
        if ($setUp->getConfig('notify_upload')) {
            Logger::emailNotification('./'.$location_dir.$filename.'.'.$extension, 'upload');
        }
        Utils::setSuccess(' <span><i class="bi bi-check-circle"></i> <strong>'.$filename.'.'.$extension.'</strong></span> ');
    }
    curl_close($ch);
} else {
    $message = '<span><i class="bi bi-exclamation-triangle"></i> '.$setUp->getLangString('upload_not_allowed').'</span> ';
    Utils::setError($message);
}
exit;
