<?php
/**
 * Control authentication
 
 */
if (!class_exists('GateKeeper', false)) {
    
    class GateKeeper
    {

        private $_USERS;
        private $_newusers;

        /**
         * Set session and language
         *
         * @return set _USERS
         */
        public function __construct()
        {
            $this->_USERS = $this->loadUsers();
            $this->_newusers = $this->loadUsersNew();
        }
        /**
         * Check user satus
         *
         * @param string $relative relative path to call
         * @param string $callfrom file from we call (used for admin captcha) '' or '_admin'
         *
         * @return $message
         */
        public function init($relative = 'vfm-admin/', $callfrom = '')
        {
            global $updater;
            global $setUp;

            if (isset($_GET['logout'])) {
                $unsetuser = isset($_SESSION['vfm_user_name']) ? $_SESSION['vfm_user_name'] : false;
                $_SESSION['vfm_user_name'] = null;
                $_SESSION['vfm_logged_in'] = null;
                $_SESSION['vfm_user_name_new'] = null;
                $_SESSION['vfm_user_space'] = null;
                $_SESSION['vfm_user_used'] = null;
                GateKeeper::removeCookie($unsetuser, $relative);
                // session_destroy(); // keep language selection and minor preferences
            } else {
                if (!GateKeeper::isUserLoggedIn()) {
                    $this->checkCookie();
                }
            }

            $postusername = filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $postuserpass = filter_input(INPUT_POST, 'user_pass', FILTER_SANITIZE_SPECIAL_CHARS);
            $rememberme = filter_input(INPUT_POST, 'vfm_remember', FILTER_SANITIZE_SPECIAL_CHARS);

            if ($postusername && $postuserpass) {
                if (Utils::checkCaptcha('show_captcha'.$callfrom)) {
                    if ($this->isUser($postusername, $postuserpass)) {
                        if ($rememberme === 'yes') {
                            GateKeeper::setCookie($postusername);
                        }
                        if (isset($_SESSION['vfm_user_name_new']) && strlen($_SESSION['vfm_user_name_new']) > 0) {
                            $postusername = $_SESSION['vfm_user_name_new'];
                            // unset old sensitive username
                            $users = $updater->updateUserData($postusername, 'sensitive', false);
                            $updater->updateUserFile('', false, $users);
                        }

                        $_SESSION['vfm_user_name'] = $postusername;
                        $_SESSION['vfm_logged_in'] = 1;

                        $usedspace = $this->getUserSpace();

                        if ($usedspace !== false) {
                            $userspace = $this->getUserInfo('quota')*1024*1024;
                            $_SESSION['vfm_user_used'] = $usedspace;
                            $_SESSION['vfm_user_space'] = $userspace;
                        } else {
                            $_SESSION['vfm_user_used'] = null;
                            $_SESSION['vfm_user_space'] = null;
                        }
                        if ($setUp->getConfig('notify_login') && $callfrom !== '_admin') {
                            Logger::logAccess();
                        }
                    } else {
                        Utils::setWarning($setUp->getString('wrong_pass'));
                    }
                } else {
                    Utils::setWarning($setUp->getString('wrong_captcha'));
                }
                header('location:?dir=');
                exit;
            }
        }

        /**
         * Delete multifile
         *
         * @return updates total available space
         */
        public function getUserSpace()
        {
            global $setUp;
            if ($this->getUserInfo('dir') !== null
                && $this->getUserInfo('quota') !== null
            ) {
                $totalsize = 0;
                $userfolders = json_decode($this->getUserInfo('dir'), true);
                $userfolders = $userfolders ? $userfolders : array();

                foreach ($userfolders as $myfolder) {
                    $checkfolder = urldecode($setUp->getConfig('starting_dir').$myfolder);
                    if (file_exists($checkfolder)) {
                        $ritorno = Utils::getDirSize($checkfolder);
                        $totalsize += $ritorno['size'];
                    }
                }
                return $totalsize;
            }
            return false;
        }

        /**
         * Login validation
         *
         * @param string $userName user name
         * @param string $userPass password
         *
         * @return true/false
         */
        public function isUser($userName, $userPass)
        {
            global $setUp;
            $salt = $setUp->getConfig('salt');
            $passo = $salt.urlencode($userPass);
            $users = $this->getUsers();

            // foreach ($users as $user) {
            //     if (isset($user['sensitive']) && $user['sensitive'] === $userName) {
            //         if (crypt($passo, $user['pass']) == $user['pass']) {
            //             $_SESSION['vfm_user_name_new'] = $user['name'];
            //             Utils::setWarning('<span>'.$setUp->getString('your_new_username_is').' <strong>'.$user['name'].'</strong></span>');
            //             return true;
            //         }
            //         break;
            //     }
            // }
            if ($users) {
                foreach ($users as $user) {
                    if (strtolower($user['name']) === strtolower($userName)) {
                        if (crypt($passo, $user['pass']) === $user['pass']) {
                            if (isset($user['disabled']) && $user['disabled'] === true) {
                                Utils::setError($setUp->getString('account_disabled'));
                                return false;
                            }
                            return true;
                        }
                        break;
                    }
                }
            }
            return false;
        }

        /**
         * Check if login is required to view lists
         *
         * @return true/false
         */
        public static function isLoginRequired()
        {
            global $setUp;
            if ($setUp->getConfig('require_login') == false) {
                return false;
            }
            return true;
        }

        /**
         * Check if user is logged in
         *
         * @return true/false
         */
        public static function isUserLoggedIn()
        {
            if (isset($_SESSION['vfm_user_name'])
                && isset($_SESSION['vfm_logged_in'])
                && $_SESSION['vfm_logged_in'] == 1
            ) {
                return true;
            }
            return false;
        }

        /**
         * Check if target action is allowed
         *
         * @param string $action action to check
         *
         * @return true/false
         */
        public function isAllowed($action)
        {
            global $setUp;
            if ($action && $this->isAccessAllowed()) {
                $role = $this->getUserInfo('role');
                $role = $role == null ? 'guest' : $role;

                if ($role == 'superadmin') {
                    return true;
                }

                $base_actions = array(
                    'view_enable',
                    'viewdirs_enable',
                    'download_enable',
                );

                // Base actions true for all except Guest and User
                if (in_array($action, $base_actions) && $role !== 'guest' && $role !== 'user') {
                    return true;
                }

                $role_ext = $role == 'admin' ? '' : '_'.$role;

                return $setUp->getConfig($action.$role_ext);
            }
            return false;
        }

        /**
         * Check if user can access
         *
         * @return true/false
         */
        public function isAccessAllowed()
        {
            if (!$this->isLoginRequired() || $this->isUserLoggedIn()) {
                return true;
            }
            return false;
        }

        /**
         * Get user info ('name', 'role', 'dir', 'email')
         *
         * @param int $info index of corresponding user info
         *
         * @return info requested
         */
        public function getUserInfo($info)
        {
            if ($this->isUserLoggedIn()
                && isset($_SESSION['vfm_user_name'])
                && strlen($_SESSION['vfm_user_name']) > 0
            ) {
                $username = $_SESSION['vfm_user_name'];
                $curruser = $this->getCurrentUser($username);

                if (isset($curruser[$info]) && strlen($curruser[$info]) > 0) {
                    return $curruser[$info];
                }
            }
            return null;
        }

        /**
         * Get user's avatar image, or return default
         *
         * @param string $username  user to search
         * @param string $adminarea relative
         * @param string $size      size in pixel
         *
         * @return image path
         */
        public static function getAvatar($username, $adminarea = 'vfm-admin/', $size = '25')
        {
            global $setUp;
            $avaimg = md5($username).'.png';
            
            if (!file_exists($adminarea.'_content/avatars/'.$avaimg)) {
                $imgtag = '<img data-name="'.$username.'" class="rounded-circle avatar avadefault" width="'.$size.'">';
            } else {
                $imgtag = '<img class="rounded-circle avatar" width="'.$size.'" src="'.$setUp->getConfig('script_url').'vfm-admin/_content/avatars/'.$avaimg.'">';
            }
            return $imgtag;
        }

        /**
         * Check if user is SuperAdmin
         *
         * @return true/false
         */
        public function isSuperAdmin()
        {
            if ($this->getUserInfo('role') === 'superadmin') {
                return true;
            }
            return false;
        }

        /**
         * Check if user is MasterAdmin
         *
         * @return true/false
         */
        public function isMasterAdmin()
        {
            $users = $this->getUsers();
            $king = array_shift($users);
            if ($king === $this->getCurrentUser($this->getUserInfo('name'))) {
                return true;
            }
            return false;
        }

        /**
         * Get all users from users.php
         *
         * @return users array
         */
        public function getUsers()
        {
            return $this->_USERS;
        }


        /**
         * Load users from users.php
         *
         * @return users array
         */
        public function loadUsers()
        {
            $_USERS = false;
            $filepath = dirname(dirname(__FILE__)).'/_content/users/users.php';
            if (file_exists($filepath)) {
                include $filepath;
            }
            return $_USERS;
        }

        /**
         * Get all users from users-new.php
         *
         * @return users array
         */
        public function getUsersNew()
        {
            return $this->_newusers;
        }

        /**
         * Load users from users.php
         *
         * @return users array
         */
        public function loadUsersNew()
        {
            $newusers = false;
            $filepath = dirname(dirname(__FILE__)).'/_content/users/users-new.php';
            if (file_exists($filepath)) {
                include $filepath;
            }
            return $newusers;
        }
        /**
         * Get user data by username
         *
         * @param string $search username to search
         *
         * @return user array requested
         */
        public function getCurrentUser($search)
        {
            $currentuser = array();
            $users = $this->_USERS;
            if ($users) {
                foreach ($users as $user) {
                    if (isset($user['name'])) {
                        if (strtolower($user['name']) == strtolower($search)) {
                            $currentuser = $user;
                            return $currentuser;
                        }
                    }
                }
            }
            return false;
        }

        /**
         * Return current username
         *
         * @return username
         */
        public static function getUserName()
        {
            return isset($_SESSION['vfm_user_name']) ? $_SESSION['vfm_user_name'] : '--';
        }

        /**
         * Check if SuperAdmin has access to the area
         *
         * @param string $permission relative
         *
         * @return image path
         */
        public function canSuperAdmin($permission)
        {
            global $setUp;
            if ($this->isSuperAdmin()) {
                if ($this->isMasterAdmin()) {
                    return true;
                }
                return $setUp->getConfig($permission);
            }
            return false;
        }

        /**
         * Show login box
         *
         * @return true/false
         */
        public function showLoginBox()
        {
            if (!$this->isUserLoggedIn()
                && count($this->getUsers()) > 0
            ) {
                return true;
            }
            return false;
        }

        /**
         * Set remember me cookie
         *
         * @param string $postusername user name
         *
         * @return cookie and key set
         */
        public static function setCookie($postusername = false)
        {
            global $setUp;
            $_REMEMBER = GateKeeper::getRemember();

            $rewrite = false;
            $salt = $setUp->getConfig('salt');
            $rmsha = md5($salt.sha1($postusername.$salt));
            $rmshaved = md5($rmsha);
            $expires = time()+ (60*60*24*365);

            if (PHP_VERSION_ID >= 70300) {
                setcookie(
                    'rm', $rmsha,
                    // ['expires' => $expires, 'httponly' => true]
                    ['expires' => $expires, 'httponly' => true, 'samesite' => 'strict']
                );
                setcookie(
                    'vfm_user_name', $postusername,
                    // ['expires' => $expires, 'httponly' => true]
                    ['expires' => $expires, 'httponly' => true, 'samesite' => 'strict']
                );
            } else {
                setcookie('rm', $rmsha, $expires);
                setcookie('vfm_user_name', $postusername, $expires);
            }

            if (array_key_exists($postusername, $_REMEMBER)
                && $_REMEMBER[$postusername] !== $rmshaved
            ) {
                $rewrite = true;
            }

            if (!array_key_exists($postusername, $_REMEMBER)
                || $rewrite == true
            ) {
                $_REMEMBER[$postusername] = $rmshaved;
                $rmb = '$_REMEMBER = ';
                if (false == (file_put_contents('vfm-admin/_content/users/remember.php', "<?php\n\n $rmb".var_export($_REMEMBER, true).";\n"))) {
                    Utils::setError('error setting your remember key');
                    return false;
                }
            }
        }

        /**
         * Remove remember me cookie
         *
         * @param string $postusername user name
         * @param string $path         relative path to users/
         *
         * @return updated remember.php file
         */
        public static function removeCookie($postusername = false, $path = 'vfm-admin/')
        {
            // global $_REMEMBER;
            $_REMEMBER = GateKeeper::getRemember();

            $expires = time()+ (60*60*24*365);

            if (PHP_VERSION_ID >= 70300) {
                setcookie(
                    'rm',
                    '',
                    // ['expires' => $expires, 'httponly' => true]
                    ['expires' => $expires, 'httponly' => true, 'samesite' => 'strict']
                );
            } else {
                setcookie('rm', '', $expires);
            }
            // setcookie('rm', '', time() - (60*60*24*365));

            if ($postusername && $_REMEMBER) {
                if (array_key_exists($postusername, $_REMEMBER)) {
                    unset($_REMEMBER[$postusername]);
                
                    $rmb = '$_REMEMBER = ';
                    if (false == (file_put_contents($path.'_content/users/remember.php', "<?php\n\n $rmb".var_export($_REMEMBER, true).";\n"))
                    ) {
                        Utils::setError('error resetting remember key');
                        return false;
                    }
                }
            }
        }

        /**
         * Check rememberme cookie
         *
         * @return checkKey() | false
         */
        public function checkCookie()
        {
            if (isset($_COOKIE['rm']) && isset($_COOKIE['vfm_user_name'])) {
                $name = $_COOKIE['vfm_user_name'];
                $key = $_COOKIE['rm'];
                return $this->checkKey($name, $key);
            }
            return false;
        }

        /**
         * Check rememberme cookie
         *
         * @return $_REMEMBER
         */
        public static function getRemember()
        {
            include dirname(dirname(__FILE__)).'/_content/users/remember.php';
            return $_REMEMBER;
        }

        /**
         * Check remember me key
         *
         * @param string $name user name
         * @param string $key  rememberme key
         *
         * @return login via cookie
         */
        public function checkKey($name, $key)
        {
            $_REMEMBER = GateKeeper::getRemember();

            if (array_key_exists($name, $_REMEMBER)) {
                if ($_REMEMBER[$name] === md5($key)) {
                    $_SESSION['vfm_user_name'] = $name;
                    $_SESSION['vfm_logged_in'] = 1;

                    $usedspace = $this->getUserSpace();

                    if ($usedspace !== false) {
                        $userspace = $this->getUserInfo('quota')*1024*1024;
                        $_SESSION['vfm_user_used'] = $usedspace;
                        $_SESSION['vfm_user_space'] = $userspace;
                    } else {
                        $_SESSION['vfm_user_used'] = null;
                        $_SESSION['vfm_user_space'] = null;
                    }
                    return true;
                } else {
                    GateKeeper::removeCookie($name);
                }
            }
            return false;
        }
    }
}
