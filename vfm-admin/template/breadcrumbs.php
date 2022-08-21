<?php

if (!defined('VFM_APP')) {
    return;
}
/**
* BreadCrumbs
*/
if ($gateKeeper->isAccessAllowed()
) { ?>
    <nav aria-label="breadcrumb">
    <ol class="breadcrumb small px-3">
    <?php
    if ($setUp->getConfig("show_path") !== true) {
        $cleandir = "?dir=".urlencode(substr($setUp->getConfig('starting_dir').$gateKeeper->getUserInfo('dir'), 2));
        $stolink = '?dir='.urlencode($location->getDir(false, false, false, 1));
        $stodeeplink = '?dir='.urlencode($location->getDir(false, false, false, 0));

        if (strlen($stolink) > strlen($cleandir)) {
            $parentlink = $stolink;
        } else {
            $parentlink = "?dir=";
        }
        if (strlen($stodeeplink) > strlen($cleandir)
        ) { ?>
        <li class="breadcrumb-item">
            <a href="<?php echo $parentlink; ?>">
                <i class="bi bi-chevron-left"></i> <i class="bi bi-folder-fill"></i>
            </a>
        </li>
            <?php
        }
    }

    if ($setUp->getConfig("show_foldertree") == true && $gateKeeper->isAllowed('viewdirs_enable')) { ?>
        <li class="breadcrumb-item">
            <a href="#" data-bs-toggle="modal" data-bs-target="#archive-map" data-action="breadcrumbs">
                <i class="bi bi-diagram-3-fill"></i> 
            </a>
        </li>
        <?php
    }
    
    if ($setUp->getConfig("show_path") == true) {
        if (strlen($setUp->getConfig('starting_dir')) < 3) {
            ?>
        <li class="breadcrumb-item">
            <a href="?dir=">
                <i class="bi bi-folder-fill"></i> <?php echo $setUp->getString("root"); ?>
            </a>
        </li>
            <?php
        }
        $totdirs = count($location->path);
        foreach ($location->path as $key => $dir) {
            $stolink = '?dir='.urlencode($location->getDir(false, false, false, $totdirs -1 - $key));
            ?>
        <li class="breadcrumb-item"><a href="<?php echo $stolink; ?>">
            <i class="bi bi-folder2-open"></i> 
            <?php echo $location->getPathLink($key, false); ?>
        </a></li>
            <?php
        }
    } ?>
    </ol>
    </nav>
    <?php
}
