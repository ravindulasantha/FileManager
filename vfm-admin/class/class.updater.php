<?php
/**
 * Controls single user update panel

 */
if (!class_exists('Updater', false)) {
   
    class Updater
    {
       
        public function init()
        {
            global $updater;

            $posteditname = filter_input(INPUT_POST, 'user_new_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $postoldname = filter_input(INPUT_POST, 'user_old_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $posteditpass = filter_input(INPUT_POST, 'user_new_pass', FILTER_SANITIZE_SPECIAL_CHARS);
            $posteditpasscheck = filter_input(INPUT_POST, 'user_new_pass_confirm', FILTER_SANITIZE_SPECIAL_CHARS);
            $postoldpass = filter_input(INPUT_POST, 'user_old_pass', FILTER_SANITIZE_SPECIAL_CHARS);
            $posteditmail = filter_input(INPUT_POST, 'user_new_email', FILTER_VALIDATE_EMAIL);
            $postoldmail = filter_input(INPUT_POST, 'user_old_email', FILTER_VALIDATE_EMAIL);

            if ($postoldpass && $posteditname) {
                $updater->updateUser(
                    $posteditname,
                    $postoldname,
                    $posteditpass,
                    $posteditpasscheck,
                    $postoldpass,
                    $posteditmail,
                    $postoldmail
                );
            }
        }

        /**
         * Update user profile
         *
         * @param string $posteditname      new username
         * @param string $postoldname       current username
         * @param string $posteditpass      new password
         * @param string $posteditpasscheck check password
         * @param string $postoldpass       old password
         * @param string $posteditmail      new email
         * @param string $postoldmail       old email
         *
         * @return global $users updated
         */
        public function updateUser(
            $posteditname,
            $postoldname,
            $posteditpass,
            $posteditpasscheck,
            $postoldpass,
            $posteditmail,
            $postoldmail
        ) {
            global $setUp;
            global $updater;
            global $gateKeeper;
            // global $_USERS;
            // global $users;
            // $users = $_USERS;
            $passa = true;
            $new_users = false;

            if ($gateKeeper->isUser($postoldname, $postoldpass)) {
                // Update Username.
                if ($posteditname != $postoldname) {
                    if ($updater->findUser($posteditname)) {
                            Utils::setError('<span><strong>'.$posteditname.'</strong> '.$setUp->getString('file_exists').'</span>');
                            $passa = false;
                            return;
                    }
                    GateKeeper::removeCookie($postoldname);
                    Updater::updateAvatar($postoldname, $posteditname);
                    $new_users = $updater->updateUserData($postoldname, 'name', $posteditname, $new_users);
                    $postoldname = $posteditname;
                }
                // Update e-mail.
                if ($posteditmail != $postoldmail) {
                    if ($updater->findUser($posteditmail, true)) {
                            Utils::setError('<span><strong>'.$posteditmail.'</strong> '.$setUp->getString('file_exists').'</span>');
                            $passa = false;
                            return;
                    }
                    $new_users = $updater->updateUserData($postoldname, 'email', $posteditmail, $new_users);
                }
                // Update password.
                if ($posteditpass) {
                    if ($posteditpass === $posteditpasscheck) {
                        $new_users = $updater->updateUserPwd($postoldname, $posteditpass, $new_users);
                    } else {
                        Utils::setError($setUp->getString('wrong_pass'));
                        $passa = false;
                        return;
                    }
                }

                // Update custom fields
                $jcustomfields = isset($_POST['user-customfields']) ? $_POST['user-customfields'] : false;
                if ($jcustomfields) {
                    $customfields = json_decode($jcustomfields, true);
                    foreach ($customfields as $customkey => $customfield) {
                        $cleanfield = false;
                        if ($customfield['type'] == 'email') {
                            $cleanfield = filter_input(INPUT_POST, $customkey, FILTER_VALIDATE_EMAIL);
                        } else {
                            $cleanfield = filter_input(INPUT_POST, $customkey, FILTER_SANITIZE_SPECIAL_CHARS);
                        }
                        if ($cleanfield) {
                            $new_users = $updater->updateUserData($postoldname, $customkey, $cleanfield, $new_users);
                        }
                    }
                }

                if ($passa == true) {
                    $updater->updateUserFile('', $posteditname, $new_users);
                }
            } else {
                Utils::setError($setUp->getString('wrong_pass'));
            }
        }

        /**
         * Update user password
         *
         * @param string $checkname  username
         * @param string $changepass new pass
         * @param array  $users      users list to handle
         *
         * @return global $users updated
         */
        public function updateUserPwd($checkname, $changepass, $users = false)
        {
            global $setUp;
            global $gateKeeper;
            $users = is_array($users) ? $users : $gateKeeper->getUsers();

            foreach ($users as $key => $value) {
                if (strtolower($value['name']) === strtolower($checkname)) {
                    $salt = $setUp->getConfig('salt');
                    $users[$key]['pass'] = crypt($salt.urlencode($changepass), Utils::randomString());
                    break;
                }
            }
            return $users;
        }

        /**
         * Update user data
         *
         * @param string $checkname username to find
         * @param string $type      info to change
         * @param string $changeval new value
         * @param array  $users     users list to handle
         *
         * @return global $users updated
         */
        public function updateUserData($checkname, $type, $changeval, $users = false)
        {
            global $gateKeeper;
            $users = is_array($users) ? $users : $gateKeeper->getUsers();

            foreach ($users as $key => $value) {
                if (strtolower($value['name']) === strtolower($checkname)) {
                    if ($changeval) {
                        $users[$key][$type] = $changeval;
                    } else {
                        unset($users[$key][$type]);
                    }
                    break;
                }
            }
            return $users;
        }

        /**
         * Update user Avatar if user changes name or delete it
         *
         * @param string $checkname username to find
         * @param string $newname   new username to assign
         * @param string $dir       relative path to /_content/avatars/
         *
         * @return avatar updated
         */
        public static function updateAvatar($checkname = false, $newname = false, $dir = 'vfm-admin/')
        {
            $avatars = glob($dir.'_content/avatars/*.png');
            $filename = md5($checkname);
            foreach ($avatars as $avatar) {
                $fileinfo = Utils::mbPathinfo($avatar);
                $avaname = $fileinfo['filename'];

                if ($avaname === $filename) {
                    if ($newname) {
                        $newname = md5($newname);
                        rename($dir.'_content/avatars/'.$avaname.'.png', $dir.'_content/avatars/'.$newname.'.png');
                    } else {
                        unlink($dir.'_content/avatars/'.$avaname.'.png');
                    }
                    break;
                }
            }
        }

        /**
         * Delete user
         *
         * @param string $checkname username to find
         *
         * @return global $users updated
         */
        public function deleteUser($checkname)
        {
            // global $_USERS;
            // global $users;
            global $gateKeeper;
            $_USERS = $gateKeeper->getUsers();
            $users = $_USERS;

            foreach ($users as $key => $value) {
                if (strtolower($value['name']) === strtolower($checkname)) {
                    unset($users[$key]);
                    GateKeeper::removeCookie($checkname, '');
                    Updater::updateAvatar($checkname, false, '');
                    break;
                }
            }
            return $users;
        }

        /**
         * Look if user exists
         *
         * @param string $userdata username or email to look for
         * @param bool   $email    false or true to search email
         *
         * @return true/false
         */
        public function findUser($userdata, $email = false)
        {
            global $gateKeeper;
            $_USERS = $gateKeeper->getUsers();
            $attr = $email ? 'email' : 'name';
            if (is_array($_USERS)) {
                foreach ($_USERS as $value) {
                    if (isset($value[$attr])) {
                        if (strtolower($value[$attr]) === strtolower($userdata)) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }

        /**
         * Look if pre-registered user exists
         *
         * @param string $userdata username or email to look for
         * @param bool   $email    false or true to search email
         *
         * @return true/false
         */
        public function findUserPre($userdata, $email = false)
        {
            global $gateKeeper;
            $newusers = $gateKeeper->getUsersNew();
            $attr = $email ? 'email' : 'name';
            if (is_array($newusers)) {
                foreach ($newusers as $preuser) {
                    if (isset($preuser[$attr])) {
                        if (strtolower($preuser[$attr]) === strtolower($userdata)) {
                            return $preuser;
                        }
                    }
                }
            }
            return false;
        }

        /**
         * Get user by activation key from users-new.php
         * prepare it for users.php
         * and create his custom dir if requested
         *
         * @param string $userdata username to look for
         *
         * @return $thisuser array or false
         */
        public function findUserKey($userdata)
        {
            global $setUp;
            global $gateKeeper;

            $newusers = $gateKeeper->getUsersNew();
            $defaultfolders = $setUp->getConfig('registration_user_folders');

            if (!empty($newusers)) {
                foreach ($newusers as $newuser) {
                    if ($newuser['key'] === $userdata) {
                        $thisuser = array();
                        foreach ($newuser as $attrkey => $userattr) {
                            $thisuser[$attrkey] = $userattr;
                        }
                        $thisuser['role'] = $setUp->getConfig('registration_role');

                        if ($defaultfolders) {
                            $arrayfolders = json_decode($defaultfolders, false);

                            if (in_array('vfm_reg_new_folder', $arrayfolders)) {
                                
                                $userfolderpath = $thisuser['name'];

                                $newpath = $setUp->getConfig('starting_dir').$userfolderpath;

                                if (!is_dir($newpath)) {
                                    mkdir($newpath);
                                }

                                $arrayfolders = array_diff($arrayfolders, array('vfm_reg_new_folder'));
                                $arrayfolders[] = $userfolderpath;
                                $userdir = json_encode(array_values($arrayfolders));
                            } else {
                                $userdir = $defaultfolders;
                            }

                            $thisuser['dir'] = $userdir;
                            if (strlen($setUp->getConfig('registration_user_quota')) > 0) {
                                $thisuser['quota'] = $setUp->getConfig('registration_user_quota');
                            }
                        }
                        unset($thisuser['key']);
                        return $thisuser;
                    }
                }
            }
            return false;
        }

        /**
         * Update users file
         *
         * @param string $option   what has been updated
         * @param string $postname username updated
         * @param array  $users    users list
         *
         * @return response
         */
        public function updateUserFile($option = '', $postname = false, $users = false)
        {
            global $setUp;

            if ($users) {
                $usrs = '$_USERS = ';
                $filepath = dirname(dirname(__FILE__)).'/_content/users/users.php';
                if (false === (file_put_contents($filepath, "<?php\n\n $usrs".var_export($users, true).";\n"))) {
                    Utils::setError('error updating users list');
                } else {
                    if ($option == 'password') {
                        Utils::setSuccess($setUp->getString('password_reset'));
                    } else {
                        if ($postname) {
                            $edited = '<strong>'.$postname.'</strong> ';
                            Utils::setSuccess($edited.$setUp->getString('updated'));
                        }
                    }
                    $_SESSION['vfm_user_name'] = null;
                    $_SESSION['vfm_logged_in'] = null;
                    $_SESSION['vfm_user_space'] = null;
                    $_SESSION['vfm_user_used'] = null;
                    $_SESSION['vfm_user_name_new'] = null;
                    session_destroy();
                }
            }
        }

        /**
         * Prepare registration user
         *
         * @param array $newusers new users list
         *
         * @return response
         */
        public function updateRegistrationFile($newusers)
        {
            $path = dirname(dirname(__FILE__)).'/_content/users/';
            $usrs = '$newusers = ';
            if (false == (file_put_contents($path.'users-new.php', "<?php\n\n $usrs".var_export($newusers, true).";\n"))) {
                return false;
            } else {
                return true;
            }
        }

        /**
         * Remove user from value
         *
         * @param array  $array array where to search
         * @param key    $key   key to search
         * @param string $value vluue to search
         *
         * @return null/$new_image
         */
        public function removeUserFromValue($array, $key, $value)
        {
            foreach ($array as $subKey => $subArray) {
                if ($subArray[$key] == $value) {
                    unset($array[$subKey]);
                }
            }
            return $array;
        }

        /**
         * Remove old standby registrations
         *
         * @param array  $newusers array where to search
         * @param key    $key      key to search
         * @param string $lifetime max lifetime
         *
         * @return null/$new_image
         */
        public function removeOldReg($newusers, $key, $lifetime)
        {
            foreach ($newusers as $subKey => $subArray) {
                $data = $subArray[$key];

                if ($data <= $lifetime) {
                    unset($newusers[$subKey]);
                    $this->updateRegistrationFile($newusers);
                }
            }
            return $newusers;
        }

        /**
         * Update uploads dir
         *
         * @param string $new_dir give or not the access
         *
         * @return updated uploads directory
         */
        public function updateUploadsDir($new_dir = false)
        {
            global $setUp;

            $old_dir = $setUp->getConfig('starting_dir');
            $oldDir = dirname(dirname(dirname(__FILE__))).'/'.basename($old_dir);

            // Nothing to update
            if (!$new_dir && file_exists($oldDir)) {
                return false;
            }

            // No new dir posted, no old dir, rebuild old dir
            if (!$new_dir && !file_exists($oldDir) && $old_dir != "./") {
                mkdir($oldDir);
                if ($this->updateHtaccess($old_dir, $setUp->getConfig('direct_links')) === false) {
                    Utils::setError('Error writing on: '.$oldDir.'/.htaccess, check CHMOD');
                }
            }

            if (strlen($new_dir)) {
                $newDir = dirname(dirname(dirname(__FILE__))).'/'.$new_dir;
                
                // New dir different from old
                if ($oldDir !== $newDir) {
                    if (!file_exists($newDir)) {
                        if ($old_dir == "./" || !file_exists($oldDir)) {
                            mkdir($newDir);
                        } else {
                            if (!rename($oldDir, $newDir)) {
                                Utils::setError('Error renaming uploads directory');
                                return false;
                            }
                        }
                    }
                    if ($this->updateHtaccess($new_dir, $setUp->getConfig('direct_links')) === false) {
                        Utils::setError('Error writing on: '.$newDir.'/.htaccess, check CHMOD');
                    }
                }
            }
            return true;
        }

        /**
         * Update .htaccess
         *
         * @param string  $starting_dir selected uploads directory
         * @param boolean $direct_links give or not the access
         *
         * @return void
         */
        public function updateHtaccess($starting_dir, $direct_links = false)
        {
            $htaccess = dirname(dirname(dirname(__FILE__))).'/'.rtrim(ltrim($starting_dir, './'), '/')."/.htaccess";

            $start_marker = "# begin VFM rules";
            $end_marker   = "# end VFM rules";

            // Split out the existing file into the preceeding lines, and those that appear after the marker
            $pre_lines = $post_lines = $existing_lines = array();

            $found_marker = $found_end_marker = false;

            if (file_exists($htaccess)) {
                $hta = file_get_contents($htaccess);  // Read the whole .htaccess file into mem
                $lines = explode(PHP_EOL, $hta); // Use newline to differentiate between records

                foreach ($lines as $line) {
                    if (!$found_marker && false !== strpos($line, $start_marker)) {
                        $found_marker = true;
                        continue;
                    } elseif (!$found_end_marker && false !== strpos($line, $end_marker)) {
                        $found_end_marker = true;
                        continue;
                    }
                    if (!$found_marker) {
                        $pre_lines[] = $line;
                    } elseif ($found_marker && $found_end_marker) {
                        $post_lines[] = $line;
                    } else {
                        $existing_lines[] = $line;
                    }
                }
            }

            $insertion = array();
            if ($starting_dir !== './') {
                $insertion[] = "<Files \"*.php\">";
                $insertion[] = "SetHandler none";
                $insertion[] = "SetHandler default-handler";
                $insertion[] = "Options -ExecCGI";
                $insertion[] = "RemoveHandler .php";
                $insertion[] = "</Files>";
                $insertion[] = "<IfModule mod_php5.c>";
                $insertion[] = "php_flag engine off";
                $insertion[] = "</IfModule>";
                if (!$direct_links) {
                    $insertion[] = "Order Deny,Allow";
                    $insertion[] = "Deny from all";
                }
                $insertion[] = "Options -Indexes";
            } else {
                // if ($direct_links) {
                    $insertion[] = "<IfModule mod_rewrite.c>";
                    $insertion[] = "RewriteEngine on";
                    $insertion[] = "RewriteCond %{REQUEST_URI}::$1 ^(.*?/)(.*)::\\2$";
                    $insertion[] = "RewriteRule ^(.*)$ - [E=BASE:%1]";
                    $insertion[] = "RewriteCond %{REQUEST_FILENAME} !-f";
                    $insertion[] = "RewriteCond %{REQUEST_FILENAME} !-d";
                    $insertion[] = "RewriteRule download/(.*)/sh/(.*)/share/(.*) %{ENV:BASE}/vfm-admin/vfm-downloader.php?q=$1&sh=$2&share=$3 [L]";
                    $insertion[] = "RewriteRule download/(.*)/h/(.*) %{ENV:BASE}/vfm-admin/vfm-downloader.php?q=$1&h=$2 [L]";
                    $insertion[] = "RewriteRule download/zip/(.*)/n/(.*) %{ENV:BASE}/vfm-admin/vfm-downloader.php?zip=$1&n=$2 [L]";
                    $insertion[] = "</IfModule>";
                // }
            }

            // Generate the new file data
            $new_file_data = implode(
                "\n", array_merge(
                    $pre_lines,
                    array( $start_marker ),
                    $insertion,
                    array( $end_marker ),
                    $post_lines
                )
            );
            // Check to see if there was a change
            if ($existing_lines === $new_file_data) {
                return true;
            }

            $fpp = fopen($htaccess, "w+");
            if ($fpp === false) {
                return false;
            }
            fwrite($fpp, $new_file_data);
            fclose($fpp);
            return true;
        }

        /**
         * Clear php cache
         *
         * @param string $path file path
         *
         * @return void
         */
        public function clearCache($path)
        {
            if (function_exists('opcache_invalidate') && strlen(ini_get("opcache.restrict_api")) < 1) {
                opcache_invalidate($path, true);
            } elseif (function_exists('apc_compile_file')) {
                apc_compile_file($path);
            }
        }
    }
}
