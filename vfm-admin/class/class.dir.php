<?php
/**
 * Hold the information about one directory in the list
 
 */
if (!class_exists('Dir', false)) {
   
    class Dir
    {
        public $name;
        public $location;
        public $modTime;

       
        public function __construct($name, $location, $relative = '')
        {
            $this->name = $name;
            // $this->name = iconv(mb_detect_encoding($name, mb_detect_order(), true), "UTF-8", $name);
            $this->location = $location;
            $this->modTime = filemtime(
                $this->location->getDir(true, false, false, 0, $relative).$name
            );
        }

        
        public function getLocation()
        {
            return $this->location->getDir(true, false, false, 0);
        }
        
       
        public function getName()
        {
            return $this->name;
        }

      
        public function getNameHtml()
        {
            return htmlspecialchars($this->name);
        }

        
        public function getNameEncoded()
        {
            return rawurlencode($this->name);
        }

       
        public function getModTime()
        {
            return $this->modTime;
        }

       
        public static function countContents($dir)
        {
            $fullpath = Utils::preGLob($dir);
            $aprila = new FilesystemIterator($fullpath, FilesystemIterator::SKIP_DOTS);

            if ($aprila) {

                $filter_files = new CallbackFilterIterator(
                    $aprila, function ($cur, $key, $iter) {
                        return $cur->isFile();
                    }
                );
                $filter_dirs = new CallbackFilterIterator(
                    $aprila, function ($cur, $key, $iter) {
                        return $cur->isDir();
                    }
                );
                $quantifiles = iterator_count($filter_files);
                $quantedir = iterator_count($filter_dirs);

            } else {
                $quantifiles = 0;
                $quantedir = 0;
            }
            $result = array(
                'files' => $quantifiles,
                'folders' => $quantedir
            );
            return $result;
        }
    }
}
