<?php

$adminurl = $setUp->getConfig('script_url')."vfm-admin";
if (!$activesec || $activesec == 'home') {
    $open = ' active';
} else {
    $open = '';
} ?>
<div id="sidebar-nav" class="navbar flex-column align-items-stretch text-white bg-dark-lighter pt-0 pb-5 fixed-top" style="height: 100%; overflow: scroll; z-index: 10;">
    <div class="pt-5">    
        <nav class="nav nav-pills flex-column pt-3">
            <div class="nav-item text-uppercase small py-3 ps-3"><?php echo $setUp->getString("administration"); ?></div>
    <?php
    if ($gateKeeper->canSuperAdmin('superadmin_can_preferences')) {
        if (!$activesec || $activesec == 'home') { ?>
            <a class="d-flex nav-link<?php echo $open; ?>" href="#view-preferences">
                <span><i class="bi bi-sliders"></i> <?php echo $setUp->getString("preferences"); ?></span> 
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <nav class="nav nav-pills flex-column small bg-dark">
                <a class="nav-link ps-4" href="#view-general"><i class="bi bi-gear-wide-connected"></i> 
                    <span><?php echo $setUp->getString("general_settings"); ?></span></a>
                
    
                <a class="nav-link ps-4" href="#view-permissions"><i class="bi bi-stoplights"></i> 
                    <span><?php echo $setUp->getString('permissions'); ?></span></a>
       
                <a class="nav-link ps-4" href="#view-share"><i class="bi bi-send"></i> 
                    <span><?php echo $setUp->getString('share_files'); ?></span></a>
              
            </nav>
            <?php
        } else { ?>
        <a href="index.php" class="d-flex nav-link">
            <span><i class="bi bi-sliders"></i> <?php echo $setUp->getString("preferences"); ?></span> 
            <i class="bi bi-chevron-left ms-auto"></i>
        </a>
            <?php
        }
    }

    if ($gateKeeper->canSuperAdmin('superadmin_can_users')) {
        $activeitem = $activesec == 'users' ? ' active' : '';
        ?>
        <a href="?section=users" class="nav-link<?php echo $activeitem; ?>"><i class="bi bi-people"></i> 
            <span><?php echo $setUp->getString("users"); ?></span>
        </a>
        <?php
    }

    if ($gateKeeper->canSuperAdmin('superadmin_can_appearance')) {
        $activeitem = $activesec == 'appearance' ? ' active' : '';
        ?>
        
        <?php
    }

    if ($gateKeeper->canSuperAdmin('superadmin_can_translations')) {
        $activeitem = $activesec == 'lang' ? ' active' : '';
        ?>
        
        <?php
    }
    if ($setUp->getConfig('log_file') == true && $gateKeeper->canSuperAdmin('superadmin_can_statistics')) {
        $activeitem = $activesec == 'log' ? ' active' : '';
        ?>
       
        <?php
    }
/*
    if ($gateKeeper->canSuperAdmin('superadmin_can_preferences')) {
        $activeitem = $activesec == 'updates' ? ' active' : '';
        ?>
        <a href="?section=updates" class="nav-link<?php echo $activeitem; ?>"><i class="bi bi-arrow-repeat"></i> 
            <span><?php echo $setUp->getString("updates"); ?></span>
        </a>
        <?php
    }
*/
    ?>
        </nav>
    </div>
</div>