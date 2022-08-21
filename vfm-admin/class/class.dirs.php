<?php
/**
 * Manage the folder archive
 */
if (!class_exists('Dirs', false)) {
    
    class Dirs
    {
        public $location,
        $dirs,
        $fullpath;

       
        public function __construct($location, $fullpath, $relative = '')
        {
            $this->location = $location;
            $this->openDir($fullpath, $relative);
        }

       
        public function openDir($fullpath, $relative = '')
        {
            global $setUp;
            global $gateKeeper;

            $totdirs = count($this->location->path);
            $father = $this->location->getDir(false, true, false, $totdirs -1);
            $hidden_dirs = $setUp->getConfig('hidden_dirs');
            $startingdir = $setUp->getConfig('starting_dir');
            // check if any folder is assigned to the current user
            $userpatharray = $gateKeeper->getUserInfo('dir') !== null ? json_decode($gateKeeper->getUserInfo('dir'), true) : false;

            // Block reading hidden dirs
            if (in_array(basename($father), $hidden_dirs)) {
                Utils::setError($setUp->getString('unable_to_read_dir'));
                return false;
            }

            $hidefiles = false;

            if (strlen($startingdir) < 3 && $startingdir === $this->location->getDir(true, true, false, 0)) {
                $hidefiles = true;
            }

            if (is_dir($fullpath)) {
                $fullpath = Utils::preGLob($fullpath);
                $content = glob($fullpath.'/*');

                $this->dirs = array();

                if (is_array($content)) {
                    foreach ($content as $item) {

                        if (is_dir($item)) {

                            $mbitem = Utils::mbPathinfo($item);
                            $item_basename = $mbitem['basename'];

                            // get only users' assigned folders if any
                            if ($userpatharray && !in_array($item_basename, $userpatharray) && !$this->location->editAllowed($relative)) {
                                continue;
                            }
                        
                            // Skip /vfm-admin/ if the main uploads dir is the root
                            if (!$hidefiles || ($hidefiles && !in_array($item_basename, $hidden_dirs))) {
                                $this->dirs[] = new Dir($item_basename, $this->location, $relative);
                            }
                        }
                    }
                }
            }
        }
    }
}
