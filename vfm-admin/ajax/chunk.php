<?php
/**

 * Resumable uploads

 */
require_once dirname(dirname(__FILE__)).'/class/class.setup.php';
require_once dirname(dirname(__FILE__)).'/class/class.gatekeeper.php';
require_once dirname(dirname(__FILE__)).'/class/class.actions.php';
require_once dirname(dirname(__FILE__)).'/class/class.location.php';
require_once dirname(dirname(__FILE__)).'/class/class.utils.php';
require_once dirname(dirname(__FILE__)).'/class/class.logger.php';
require_once dirname(dirname(__FILE__)).'/class/class.uploader.php';

$uploader = new Uploader();
$setUp = new SetUp();
$getloc = filter_input(INPUT_GET, 'loc', FILTER_SANITIZE_SPECIAL_CHARS);

$starttrim = ltrim($setUp->getConfig('starting_dir'), './');
$getloc = $getloc ? ltrim(base64_decode($getloc), './') : false;

if ($getloc && mb_substr($getloc, 0, mb_strlen($starttrim)) == $starttrim) {
    $getloc = mb_substr($getloc, mb_strlen($starttrim));
} else {
    $getloc = '';
}

$getlocfull = str_replace('\\', '/', dirname(dirname(dirname(realpath(__FILE__))))).'/'.$starttrim.$getloc;

if (!is_dir($getlocfull)) {
    $message = '<span><i class="bi bi-exclamation-triangle"></i> '.$setUp->getLangString('upload_not_allowed').'</span> ';
    Utils::setError($message);
    exit;
}
$logloc = './'.$starttrim.$getloc;
$location = new Location('../../'.$starttrim.$getloc);
$gateKeeper = new GateKeeper();

if ($gateKeeper->isAccessAllowed() && ($gateKeeper->isAllowed('upload_enable')) && $location->editAllowed('../../')) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $resumabledata = $uploader->setupFilename($_GET['resumableFilename']);
        $resumableFilename = $resumabledata['filename'];
        $extension = $resumabledata['extension'];
        $basename = $resumabledata['basename'];

        $fullfilepath = $getlocfull.$resumableFilename;

        // Skip invalid file
        if (!$uploader->veryFile($fullfilepath, $_GET['resumableTotalSize'])) {
            header("HTTP/1.0 200 Ok");
            exit;
        }
        $temp_dir = '../tmp/'.$_GET['resumableIdentifier'];
        $uploader_file = $temp_dir.'/'.$resumableFilename.'.part'.$_GET['resumableChunkNumber'];

        if (!file_exists($uploader_file)) {
            header("HTTP/1.0 204 No Content");
            exit;
        }
        header("HTTP/1.0 200 Ok");
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)) {
        @set_time_limit(0);

        $resumableIdentifier = filter_input(INPUT_POST, 'resumableIdentifier', FILTER_SANITIZE_SPECIAL_CHARS);
        $resumableChunkNumber = filter_input(INPUT_POST, 'resumableChunkNumber', FILTER_VALIDATE_INT);
        $resumableTotalSize = filter_input(INPUT_POST, 'resumableTotalSize', FILTER_VALIDATE_INT);
        $resumableTotalChunks = filter_input(INPUT_POST, 'resumableTotalChunks', FILTER_VALIDATE_INT);
        $resumableChunkSize = filter_input(INPUT_POST, 'resumableChunkSize', FILTER_VALIDATE_INT);

        $resumabledata = $uploader->setupFilename($_POST['resumableFilename']);
        $resumableFilename = $resumabledata['filename'];
        $finalFilename = $resumabledata['finalname'];

        foreach ($_FILES as $file) {
            // init the destination file (format <filename.ext>.part<#chunk>
            // the file is stored in a temporary directory
            $temp_dir = '../tmp/'.$resumableIdentifier;

            $dest_file = $temp_dir.'/'.$resumableFilename.'.part'.$resumableChunkNumber;

            // create the temporary directory
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0775, true);
            }

            // move the temporary file
            if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
                Utils::setError(
                    ' <span><i class="bi bi-exclamation-triangle"></i> Error saving chunk '
                    .$resumableChunkNumber.' for '.$resumableFilename.'</span> '
                );
            } else {
                // Check if all the parts present
                if ($uploader->checkChunks($temp_dir, $resumableTotalSize, $resumableChunkSize, $resumableTotalChunks)) {
                    // Create the final destination file
                    $uploader->createFileFromChunks(
                        $getlocfull,
                        $temp_dir,
                        $resumableFilename,
                        $resumableTotalSize,
                        $resumableTotalChunks,
                        $logloc,
                        $finalFilename,
                    );
                }
                exit;
            }
        }
    }
    exit;
}
$message = '<span><i class="bi bi-exclamation-triangle"></i> '.$setUp->getLangString('upload_not_allowed').'</span> ';
Utils::setError($message);
