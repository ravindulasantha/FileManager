<?php

if (!class_exists('VFMUpdater', false)) {
   
    class VFMUpdater
    {
       
        private $_slug = 'vfm';

        
        private $_package_zip = 'package.zip';

       
        private $_upgrade_dir = false;

       
        private $_upgrade_folder = 'UPGRADE';

       
        private $_main_dir = false;

       
        private $_log = array();
        
        private $update_url = 'https://veno.es/updates/';
        
        protected static $instance = null;

        
        public static function getInstance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        
        public function __construct()
        {
            $this->_main_dir = dirname(dirname(dirname(dirname(__FILE__))));
            $this->_upgrade_dir = $this->_main_dir.'/'.$this->_upgrade_folder;
        }

        
        public function checkUpdates()
        {
            global $setUp;
            $getinfo = $this->getInfo();
            $response = array(
                'messages' => array("We can't check the updates at the moment"),
                'result' => '',
                'license' => 0,
                'latest' => 0,
            );
            $latest = false;

            if ($getinfo) {
                $response['messages'] = array();
                $licenseinfo = json_decode($getinfo);

                if (json_last_error() === 0) {
                    // JSON is valid.
                    $response['version'] = isset($licenseinfo->version) ? $licenseinfo->version : VFM_VERSION;

                    if (version_compare(VFM_VERSION, $licenseinfo->version, '<')) {
                        $response['messages'][] = 'An updated version is available: <span class="badge rounded-pill bg-primary">'.$licenseinfo->version.'</span>';

                    }
                    if (version_compare(VFM_VERSION, $licenseinfo->version, '>=')) {
                        $response['messages'][] = 'You have the latest version.';
                        $latest = true;
                        $response['latest'] = 1;
                    }

                    if ($setUp->getConfig("license_key")) {
                        if (isset($licenseinfo->license) && isset($licenseinfo->download_url)) {
                            $serverCheck = $this->serverCheck();
                            if (!$latest && $serverCheck['enabled']) {
                                $response['license'] = 1;
                                if (isset($licenseinfo->logs)) {
                                    $response['logs'] = $licenseinfo->logs;
                                }
                            }
                        } else {
                            $response['messages'][] = 'Invalid license';
                        }
                    } else {
                        $response['messages'][] = 'Provide a license to activate automatic updates';
                    }
                } else {
                    $response['messages'][] = $getinfo;
                }
                if (isset($licenseinfo->error)) {
                    $response['messages'][] = $licenseinfo->error;
                }
            }
            return json_encode($response);
        }

        /**
         * Get package info
         *
         * @return downloaded package path
         */
        public function getInfo()
        {
            global $setUp;
            $query_args = array();
            $query_args['slug'] = $this->_slug;
            $query_args['site_url'] = urlencode($setUp->getConfig("script_url"));

            if ($setUp->getConfig("license_key")) {
                $query_args['license_key'] = $setUp->getConfig("license_key");
            }

            $getfile = $this->_update_url . '?action=get_metadata';
            foreach ($query_args as $key => $arg) {
                $getfile .= '&'.$key.'='. $arg;
            }
            $result = $this->getRemote($getfile);
            return $result;
        }

        /**
         * Get upgrade package
         *
         * @return downloaded package path
         */
        public function startUpdate()
        {
            $response = array(
                'error' => false,
                'result' => false,
            );

            $getinfo = $this->getInfo();
            if ($getinfo) {

                $licenseinfo = json_decode($getinfo);
                
                if (isset($licenseinfo->license) && isset($licenseinfo->download_url)) {
                    
                    if (!$this->isRemoteFileZip($licenseinfo->download_url)) {
                        $response['error'] = array('Download not allowed');
                        return $response;
                    }

                    $getupgrade = $this->getRemote($licenseinfo->download_url);
                    return $this->downloadPackage($getupgrade);
                }
            }
            return $this->downloadPackage($result['response']);
        }

        /**
         * Get log info
         *
         * @return info
         */
        public function getLog()
        {
            $result = $this->getRemote($this->_update_url.'package-assets/logs/'.$this->_slug.'.md');
            return $result;
        }

        /**
         * Get upgrade package
         *
         * @param string $package_data package data
         *
         * @return response
         */
        public function downloadPackage($package_data)
        {
            $response = array(
                'error' => false,
                'result' => false,
            );

            if ($package_data) {
                $upgradeDir = $this->_upgrade_dir;
                // Enable maintenance mode
                $fp = fopen(dirname($upgradeDir).'/.maintenance', 'w');

                if ($fp === false) {
                    $response['error'] = 'Failed enabling maintenance mode';
                    return $response;
                }

                fclose($fp);

                if (file_exists($upgradeDir) || mkdir($upgradeDir)) {
                    $package = $upgradeDir.'/'.$this->_package_zip;
                    if (false !== file_put_contents($package, $package_data)) {
                        $response['result'] = 'Package downloaded';
                    } else {
                        $response['error'] = 'Failed downloading the package';
                    }
                } else {
                    $response['error'] = 'Failed creating upgrade directory';
                }
            }
            return $response;
        }

        /**
         * Try to determine if a remote file is a zip.
         * 
         * @param string $url url to check
         *
         * @return bool true if the remote file is a zip, false otherwise
         */
        public function isRemoteFileZip($url = false)
        {
            if (!$url) {
                return false;
            }
            $ch = curl_init($url);

            $headers = array(
                'Range: bytes=0-4',
                'Connection: close',
            );

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2450.0 Iron/46.0.2450.0');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_VERBOSE, 0); // set to 1 to debug
            curl_setopt($ch, CURLOPT_STDERR, fopen('php://output', 'r'));

            $header = '';

            // write function that receives data from the response
            // aborts the transfer after reading 4 bytes of data
            curl_setopt(
                $ch,
                CURLOPT_WRITEFUNCTION,
                function ($curl, $data) use (&$header) {
                    $header .= $data;
                    if (strlen($header) < 4) {
                        return strlen($data);
                    }
                    return 0; // abort transfer.
                }
            );

            $result = curl_exec($ch);
            $info   = curl_getinfo($ch);

            // check for the zip magic header, return true if match, false otherwise
            return preg_match('/^PK(?:\x03\x04|\x05\x06|0x07\x08)/', $header);
        }

        /**
         * Unzip package
         *
         * @return expanded folder path
         */
        public function expandPackage()
        {
            $response = array(
                'error' => false,
                'result' => false,
            );

            $package = $this->_upgrade_dir.'/'.$this->_package_zip;

            if (file_exists($package)) {
                $expanded = dirname($package);
                $zip = new ZipArchive;
                $zipopen = $zip->open($package);
                if ($zipopen === true) {
                    $zip->extractTo($expanded.'/');
                    $zip->close();
                    // return $expanded;
                    $response['result'] = 'Package unzipped';
                }
            } else {
                $response['error'] = 'Failed unzipping archive';
            }
            return $response;
        }

        /**
         * Remove unused dirs and files
         *
         * @return expanded folder path
         */
        public function preparePackage()
        {
            $response = array(
                'error' => false,
                'result' => false,
            );
            $expanded = $this->_upgrade_dir;
            if (file_exists($expanded)) {
                $this->scanFolder($expanded.'/'.$this->_slug.'/uploads');
                $this->scanFolder($expanded.'/'.$this->_slug.'/vfm-admin/_content');
                $response['result'] = 'Ready to update files';
                $response['error'] = $this->_log;
            }
            return $response;
        }

        /**
         * Replace files
         *
         * @return expanded folder path
         */
        public function replaceFiles()
        {
            $response = array(
                'error' => false,
                'result' => false,
            );
            $expanded = $this->_upgrade_dir;
            if (file_exists($expanded)) {
                // Replace files.
                $this->scanFolder($expanded.'/'.$this->_slug, true);
                $response['result'] = 'Files replaced';
                $response['error'] = $this->_log;

            }
            return $response;
        }

        /**
         * End process
         *
         * @return expanded folder path
         */
        public function endProcess()
        {
            $response = array(
                'error' => false,
                'result' => false,
            );
            $expanded = $this->_upgrade_dir;
            if (file_exists($expanded)) {
                // Clean folder.
                $this->scanFolder($expanded);
            }
            // Disable Maintenance mode
            $maintenance = dirname($expanded).'/.maintenance';
            if (file_exists($maintenance)) {
                unlink($maintenance);
            }
            $response['result'] = 'FINISH';
            $response['error'] = $this->_log;
            return $response;
        }

        /**
         * Connect remote server
         *
         * @param string $url url to call
         *
         * @return response
         */
        public function getRemote($url)
        {
            global $setUp;
            $ch = curl_init($url);
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
                $response = curl_error($ch);
                return $response;
            }
            if (false === $getcleanfile) {
                $response = $setUp->getString('error_downloading_remote_file');
                return $response;
            }
            curl_close($ch);

            $response = $getcleanfile;

            return $response;
        }

        /**
         * Scan directory
         *
         * @param string  $directory upgrader dir
         * @param boolean $replace   replace files
         *
         * @return process the update
         */
        public function scanFolder($directory = false, $replace = false)
        {
            $response = false;

            if ($directory && file_exists($directory)) {
                foreach (glob($directory.'/{,.}*[!.]*', GLOB_MARK | GLOB_BRACE) as $file) {
                    if (is_dir($file)) {
                        $this->scanFolder(rtrim($file, '/\\'), $replace);
                    } else {
                        $this->checkFile($file, $replace);
                    }
                }
                rmdir($directory);
            }
        }

        /**
         * Replace file
         *
         * @param string  $file    upgrader dir
         * @param boolean $replace replace files
         *
         * @return process the update
         */
        public function checkFile($file, $replace = false)
        {
            if (!file_exists($file)) {
                $this->_log[] = 'File nout found: '.$file;
                return false;
            }
            
            $marker = 'UPGRADE/vfm/';
            $script_dir = $this->_main_dir;

            if ($replace === true) {
                $splitpath = explode($marker, $file);
                if (isset($splitpath[1])) {
                    $replacefile = $script_dir.'/'.$splitpath[1];
                    if (!file_exists($replacefile) || is_writable($replacefile)) {

                        if (unlink($file) !== true) {
                        // if (rename($file, $replacefile) !== true) {
                            $this->_log[] = 'Failed replacing file: '.$replacefile;
                        }
// $this->_log[] = 'FILE REPLACED: '.$replacefile;
                    } else {
                        $this->_log[] = 'File not writeable: '.$replacefile;
                    }
                }
            } else {
                unlink($file);
                // $this->_log[] = 'FILE REMOVED: '.$file;
            }
        }

        /**
         * Check server settings
         *
         * @return response
         */
        public function serverCheck()
        {
            $required = array(
                'curl' => array(
                    'name' => 'cURL',
                    'enabled' => function_exists('curl_version'),
                    'ok' => 'cURL enabled',
                    'ko' => 'Please enable PHP cURL extension',
                ),
                'writable' => array(
                    'name' => 'cURL',
                    'enabled' => is_writable($this->_main_dir) && is_writable($this->_main_dir.'/vfm-admin'),
                    'ok' => 'Main directory writable',
                    'ko' => 'The script has no permissions to write inside the main directores: <br><code class="small">' .$this->_main_dir . '</code><br><code class="small">'.$this->_main_dir.'/vfm-admin</code>',
                ),
            );

            $response = array(
                'enabled' => true,
                'details' => array(),
            );

            foreach ($required as $key => $value) {
                $response['details'][$key]['enabled'] = $value['enabled'];
                if ($value['enabled'] !== true) {
                    $response['details'][$key]['text'] = $value['ko'];
                    $response['enabled'] = false;
                } else {
                    $response['details'][$key]['text'] = $value['ok'];
                }
            }
            return $response;
        }
    }
}

/**
 * Helper function to get/return the class object
 *
 * @return VFMUpdater object
 */
function VFM_updater()
{
    return VFMUpdater::getInstance();
}

// VFM_updater();
