<?php


use PHPMailer\PHPMailer\PHPMailer;

/**
* Get additional custom fields
*/
$customfields = false;
if (file_exists('_content/users/customfields.php')) {
    include_once '_content/users/customfields.php';
}

/**
 * Update USERS
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {

    $_USERS = $gateKeeper->getUsers();
    $users = $_USERS;

    if ($get_action == "new") {
        $postnewusername = filter_input(INPUT_POST, "newusername", FILTER_SANITIZE_SPECIAL_CHARS);
        $postnewuserpass = filter_input(INPUT_POST, "newuserpass", FILTER_SANITIZE_SPECIAL_CHARS);
        $postnewuserfolder = filter_input(INPUT_POST, "newuserfolder", FILTER_SANITIZE_SPECIAL_CHARS);
        $newuserrole = filter_input(INPUT_POST, "newrole", FILTER_SANITIZE_SPECIAL_CHARS);
        $newquota = filter_input(INPUT_POST, "quota", FILTER_SANITIZE_SPECIAL_CHARS);
        $newuserfolders = false;
        $disabled = isset($_POST['disabled']);

        if (isset($_POST['newuserfolders']) || $postnewuserfolder) {
            $newuserfolders = isset($_POST['newuserfolders']) ? $_POST['newuserfolders'] : array();
        }

        $postnewusermail = filter_input(INPUT_POST, "newusermail", FILTER_VALIDATE_EMAIL);

        if ($postnewusername  || $postnewuserpass) {
            if (!$postnewusername || !$postnewuserpass) {
                Utils::setError($setUp->getString("indicate_username_and_password_for_new_user"));
            } else {
                $postnewusername = preg_replace('/\s+/', '', $postnewusername);

                $users = $_USERS;
                
                if (!$updater->findUser($postnewusername)
                    && !$updater->findUser($postnewusermail, true)
                ) {
                    $newuser = array();
                    $salt = $setUp->getConfig('salt');
                    $newuserpass = crypt($salt.urlencode($postnewuserpass), Utils::randomString());
                 
                    $newuser['name'] = $postnewusername;
                    $newuser['pass'] = $newuserpass;
                    $newuser['role'] = $newuserrole;
                    $newuser['disabled'] = $disabled;

                    if ($postnewuserfolder) {
                        $postnewuserfolder = Utils::normalizeStr($postnewuserfolder);

                        if (!file_exists(".".$setUp->getConfig('starting_dir').$postnewuserfolder)) {
                            mkdir(".".$setUp->getConfig('starting_dir').$postnewuserfolder);
                        }
                        if (!in_array($postnewuserfolder, $newuserfolders)) {
                            array_push($newuserfolders, $postnewuserfolder);
                        }
                    }

                    if ($newuserfolders) {
                        $newuserfolders = array_diff($newuserfolders, array());
                        $newuserfoldersencoded = json_encode($newuserfolders);

                        $newuser['dir'] = $newuserfoldersencoded;
                    }

                    if ($newquota) {
                        $newuser['quota'] = $newquota;
                    }

                    if ($postnewusermail) {
                        $newuser['email'] = $postnewusermail;
                        //
                        // send new user nofication
                        //
                        $mailsystem = $setUp->getConfig('email_from');

                        if (isset($_POST['usernotif']) && strlen($mailsystem) > 4) {
                            $setfrom = $mailsystem;

                            include_once 'assets/mail/vendor/autoload.php';

                            $mail = new PHPMailer();

                            $mail->CharSet = 'UTF-8';
                            $mail->setLanguage($lang);

                            if ($setUp->getConfig('smtp_enable') == true) {
                                $mail->isSMTP();
                                $mail->SMTPDebug = ($setUp->getConfig('debug_smtp') ? 2 : 0);
                                $mail->Debugoutput = 'html';
                                $smtp_auth = $setUp->getConfig('smtp_auth');

                                $mail->Host = $setUp->getConfig('smtp_server');
                                $mail->Port = (int)$setUp->getConfig('port');

                                if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
                                    $mail->SMTPOptions = array(
                                        'ssl' => array(
                                            'verify_peer' => false,
                                            'verify_peer_name' => false,
                                            'allow_self_signed' => true,
                                        )
                                    );
                                }
                                if ($setUp->getConfig('secure_conn') !== "none") {
                                    $mail->SMTPSecure = $setUp->getConfig('secure_conn');
                                }

                                $mail->SMTPAuth = $smtp_auth;

                                if ($smtp_auth == true) {
                                    $mail->Username = $setUp->getConfig('email_login');
                                    $mail->Password = $setUp->getConfig('email_pass');
                                }
                            }

                            $pulito = $setUp->getConfig('script_url');
                            $mail->setFrom($setfrom, $setUp->getConfig('appname'));
                            $mail->addAddress($newuser['email'], '<'.$newuser['email'].'>');

                            $mail->Subject = $setUp->getConfig('appname').": ".$setUp->getString('new_user');

                            // alt message
                            $altmessage = $pulito."\r\n"
                            ."A new user has been created\r\n"
                            ."username: ".$newuser['name']."\r\n"
                            .$setUp->getString('password').":".$postnewuserpass;

                            $email_logo = $setUp->getConfig('email_logo', false) ? '_content/uploads/'.$setUp->getConfig('email_logo') : 'images/px.png';

                            $mail->AddEmbeddedImage($email_logo, 'logoimg');

                            // Retrieve the email template required
                            $message = file_get_contents('_content/mail-template/template-new-user.html');

                            // Replace the % with the actual information
                            $message = str_replace('%app_name%', $setUp->getConfig('appname'), $message);
                            $message = str_replace('%app_url%', $pulito, $message);

                            $message = str_replace('%translate_new_user_has_been_created%', $setUp->getString('new_user_has_been_created'), $message);

                            $message = str_replace('%translate_username%', $setUp->getString('username'), $message);
                            $message = str_replace('%username%', $newuser['name'], $message);

                            $message = str_replace('%translate_password%', $setUp->getString('password'), $message);
                            $message = str_replace('%password%', $postnewuserpass, $message);

                            $mail->msgHTML($message);

                            $mail->AltBody = $altmessage;

                            if (!$mail->send()) {
                                Utils::setError('<strong>Mailer Error:</strong> '.$mail->ErrorInfo);
                            }
                        }
                    }

                    if (is_array($customfields)) {
                        foreach ($customfields as $customkey => $customfield) {
                            $cleanfield = false;
                            if ($customfield['type'] == 'email') {
                                $cleanfield = filter_input(INPUT_POST, $customkey, FILTER_VALIDATE_EMAIL);
                            } else {
                                if (isset($_POST[$customkey])) {
                                    if ($customfield['type'] == 'select' && is_array($_POST[$customkey])) {
                                        $cleanfield = array();
                                        foreach ($_POST[$customkey] as $value) {
                                            $cleanfield[] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
                                        }
                                        $cleanfield = json_encode($cleanfield);
                                    } else {
                                        $cleanfield = filter_input(INPUT_POST, $customkey, FILTER_SANITIZE_SPECIAL_CHARS);
                                    }
                                }
                            }
                            if ($cleanfield) {
                                $newuser[$customkey] = $cleanfield;
                            }
                        }
                    }

                    array_push($users, $newuser);
                    $admin->updateUsers($users);
                } else {
                    if ($updater->findUser($postnewusername)) {
                        $colpevole = $postnewusername;
                    }
                    if ($updater->findUser($postnewusermail, true)) {
                        $colpevole = $postnewusermail;
                    }
                    Utils::setError('<strong>'.$colpevole.'</strong> '.$setUp->getString('file_exists'));
                }
            }
        }
    }

    if ($get_action == "updatemaster") {
        $blockup = false;
        $blockupmail = false;

        $postusernameold = filter_input(INPUT_POST, "masterusernameold", FILTER_SANITIZE_SPECIAL_CHARS);
        $postusername = filter_input(INPUT_POST, "masterusername", FILTER_SANITIZE_SPECIAL_CHARS);
        $postuserpassnew = filter_input(INPUT_POST, "masteruserpassnew", FILTER_SANITIZE_SPECIAL_CHARS);
        $postusermailold = filter_input(INPUT_POST, "masterusermailold", FILTER_VALIDATE_EMAIL);
        $postusermail = filter_input(INPUT_POST, "masterusermail", FILTER_VALIDATE_EMAIL);

        $users = false;

        if ($postusername) {
            $postusername = preg_replace('/\s+/', '', $postusername);

            if ($postuserpassnew) {
                $users = $updater->updateUserPwd($postusernameold, $postuserpassnew, $users);
            }

            if ($postusername !== $postusernameold) {
                if ($updater->findUser($postusername)) {
                    $blockup = true;
                } else {
                    GateKeeper::removeCookie($postusernameold, "");
                    Updater::updateAvatar($postusernameold, $postusername, "");
                    $users = $updater->updateUserData($postusernameold, 'name', $postusername, $users);
                    $_SESSION['vfm_user_name'] = $postusername;
                }
            }

            if ($postusermail !== $postusermailold) {
                if ($updater->findUser($postusermail, true)) {
                    $blockupmail = true;
                } else {
                    $users = $updater->updateUserData($postusernameold, 'email', $postusermail, $users);
                }
            }

            if ($blockup == true || $blockupmail == true) {
                if ($blockup == true) {
                    Utils::setWarning($setUp->getString("file_exists").': '.$postusername);
                }
                if ($blockupmail == true) {
                    Utils::setWarning($setUp->getString("email_in_use"));
                }
            } else {
                $admin->updateUsers($users);
                // header('Location: ?section=users&res='.urlencode($_SESSION['success']));
                header('Location: ?section=users');
            }
        }
    }

    if ($get_action == "update") {
        $blockup = false;
        $blockupmail = false;
        $users = false;

        $postusernameold = filter_input(INPUT_POST, "usernameold", FILTER_SANITIZE_SPECIAL_CHARS);
        $postusername = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
        $postuserpassnew = filter_input(INPUT_POST, "userpassnew", FILTER_SANITIZE_SPECIAL_CHARS);
        $postuserfolder = filter_input(INPUT_POST, "userfolder", FILTER_SANITIZE_SPECIAL_CHARS);
        $quota = filter_input(INPUT_POST, "quota", FILTER_SANITIZE_SPECIAL_CHARS);
        $role = filter_input(INPUT_POST, "role", FILTER_SANITIZE_SPECIAL_CHARS);
        $delme = filter_input(INPUT_POST, "delme", FILTER_SANITIZE_SPECIAL_CHARS);
        $disabled = isset($_POST['disabled']);

        if ($delme == $postusernameold) {
            $users = $updater->deleteUser($postusernameold);
            $admin->updateUsers($users);
            Utils::setError('<strong>'.$postusernameold.'</strong>');
        } else {
            if (is_array($customfields)) {
                foreach ($customfields as $customkey => $customfield) {
                    $cleanfield = false;
                    if ($customfield['type'] == 'email') {
                        $cleanfield = filter_input(INPUT_POST, $customkey, FILTER_VALIDATE_EMAIL);
                    } else {
                        if (isset($_POST[$customkey])) {
                            if ($customfield['type'] == 'select' && is_array($_POST[$customkey])) {
                                $cleanfield = array();
                                foreach ($_POST[$customkey] as $value) {
                                    $cleanfield[] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
                                }
                                $cleanfield = json_encode($cleanfield);
                            } else {
                                $cleanfield = filter_input(INPUT_POST, $customkey, FILTER_SANITIZE_SPECIAL_CHARS);
                            }
                        }
                    }
                    if ($cleanfield) {
                        $users = $updater->updateUserData($postusernameold, $customkey, $cleanfield, $users);
                    }
                }
            }
            $userfolders = false;

            if (isset($_POST['userfolders']) || $postuserfolder) {
                $userfolders = isset($_POST['userfolders']) ? $_POST['userfolders'] : array();
            }
            $postusermailold = filter_input(INPUT_POST, "usermailold", FILTER_VALIDATE_EMAIL);
            $postusermail = filter_input(INPUT_POST, "usermail", FILTER_VALIDATE_EMAIL);

            if ($postusername) {
                $postusername = preg_replace('/\s+/', '', $postusername);

                if ($postuserpassnew) {
                    $users = $updater->updateUserPwd($postusernameold, $postuserpassnew, $users);
                }

                if ($postusername !== $postusernameold) {
                    if ($updater->findUser($postusername)) {
                        $blockup = true;
                    } else {
                        GateKeeper::removeCookie($postusernameold, "");
                        Updater::updateAvatar($postusernameold, $postusername, "");
                        $users = $updater->updateUserData($postusernameold, 'name', $postusername, $users);
                    }
                }

                if ($postusermail !== $postusermailold) {
                    if ($updater->findUser($postusermail, true)) {
                        $blockupmail = true;
                    } else {
                        $users = $updater->updateUserData($postusernameold, 'email', $postusermail, $users);
                    }
                }

                if ($postuserfolder) {
                    $postuserfolder = Utils::normalizeStr($postuserfolder);
                    if (!file_exists(".".$setUp->getConfig('starting_dir').$postuserfolder)) {
                        mkdir(".".$setUp->getConfig('starting_dir').$postuserfolder);
                    }
                    if (!in_array($postuserfolder, $userfolders)) {
                        array_push($userfolders, $postuserfolder);
                    }
                }

                $userfolders = $userfolders ? json_encode($userfolders) : $userfolders;

                $users = $updater->updateUserData($postusernameold, 'quota', $quota, $users);
                $users = $updater->updateUserData($postusernameold, 'dir', $userfolders, $users);
                if ($disabled) {
                    GateKeeper::removeCookie($postusernameold, "");
                }
                $users = $updater->updateUserData($postusernameold, 'disabled', $disabled, $users);
                
                if ($blockup == true || $blockupmail == true) {
                    if ($blockup == true) {
                        // Utils::setWarning($setUp->getString("file_exists"));
                        Utils::setWarning($postusername.' '.$setUp->getString("file_exists"));
                    }
                    if ($blockupmail == true) {
                        Utils::setWarning($setUp->getString("email_in_use"));
                    }
                } else {
                    $users = $updater->updateUserData($postusernameold, 'role', $role, $users);
                    $admin->updateUsers($users);
                }
            }
        }
    }
    header('Location:'.$script_url.'vfm-admin/?section=users');
    exit();
}
