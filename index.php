<?php

define('VFM_APP', true);
require_once dirname(__FILE__).'/vfm-admin/include/head.php';
require_once dirname(__FILE__).'/vfm-admin/include/activate.php';
?>
<!doctype html>
<html lang="<?php echo $setUp->lang; ?>"<?php echo $rtl_att; ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $setUp->getConfig("appname"); ?></title>
    <?php echo $setUp->printIcon("vfm-admin/_content/uploads/"); ?>

    <meta name="description" content="File Manager">

    <?php require 'vfm-admin/include/load-css.php'; ?>
    <script type="text/javascript" src="vfm-admin/assets/jquery/jquery-3.3.1.min.js"></script>

</head>
    <body id="uparea" class="<?php echo $bodyclass; ?>"<?php echo $bodydata; ?>>
        <div id="error"><?php echo $setUp->printAlert(); ?></div>
        <div class="overdrag"></div>
            <?php
            /**
             * ******************** HEADER ********************
             */
            if ($setUp->getConfig('header_position') == 'above') {
                include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('header');
                include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('navbar');
            } else {
                include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('navbar');
                include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('header');
            }
            ?>
        <div class="container mb-auto pt-3">
            <div class="main-content row">
            <?php
            if ($getdownloadlist) :
                /**
                 * ********* SARED FILES DOWNLOADER *********
                 */
                include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('downloader');
            elseif ($getrp) :
                /**
                 * **************** PASSWORD RESET ****************
                 */
                include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('reset');
            else :
                /**
                 * **************** FILEMANAGER **************
                 */
                if (!$getreg || $setUp->getConfig('registration_enable') == false) {
                    include dirname(__FILE__).'/vfm-admin/include/user-redirect.php';
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('remote-uploader');
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('notify-users');
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('uploadarea');
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('breadcrumbs');
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('list-folders');
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('list-files');
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('disk-space');
                }
                if ($getreg && $setUp->getConfig('registration_enable') == true) {
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('register');
                } else {
                    include dirname(__FILE__).'/vfm-admin'.$template->includeTpl('login');
                }
            endif; ?>
            </div> <!-- .main-content -->
        </div> <!-- .container -->
        <?php
        /**
         * ******************** FOOTER ********************
         */
        require dirname(__FILE__).'/vfm-admin'.$template->includeTpl('footer');
        require dirname(__FILE__).'/vfm-admin'.$template->includeTpl('modals');
        require dirname(__FILE__).'/vfm-admin/include/load-js.php';
        ?>
    </body>
</html>