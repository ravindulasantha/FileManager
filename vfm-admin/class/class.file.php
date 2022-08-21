<?php
/**
 * Hold the information about single file in the list

 */
if (!class_exists('File', false)) {
    
    class File
    {
        public $name;
        public $location;
        public $size;
        public $type;
        public $modTime;
       
        public function __construct($name, $location, $relative = '')
        {
            $this->name = $name;
            // $this->name = iconv(mb_detect_encoding($name, mb_detect_order(), true), "UTF-8", $name);
            $this->location = $location;
            $this->relative = $relative;
        }

        /**
         * Get name
         *
         * @return name
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * Get name encoded
         *
         * @return name urlencoded
         */
        public function getNameEncoded()
        {
            return rawurlencode($this->name);
        }

        /**
         * Get name html formatted
         *
         * @return HTML name
         */
        public function getNameHtml()
        {
            return htmlspecialchars($this->name);
        }

        /**
         * Get file size
         *
         * @return size
         */
        public function getSize()
        {
            // return $this->size;
            return Utils::getFileSize($this->location->getDir(true, false, false, 0, $this->relative).$this->name);
        }

        /**
         * Get type
         *
         * @return file type
         */
        public function getType()
        {
            // return $this->type;
            return Utils::getFileExtension($this->location->getDir(true, false, false, 0, $this->relative).$this->name);
        }

        /**
         * Get time
         *
         * @return mod time
         */
        public function getModTime()
        {
            // return $this->modTime;
            return filemtime($this->location->getDir(true, false, false, 0, $this->relative).$this->name);
        }

        /**
         * Check if file is image
         *
         * @return true/false
         */
        public function isImage()
        {
            $types = array(
                'jpg',
                'jpeg',
                'gif',
                'png',
                'webp',
            );
            $type = strtolower($this->getType());
            
            if (in_array($type, $types)) {
                return true;
            }
            return false;
        }

        /**
         * Check if file is audio playable
         *
         * @return true/false
         */
        public function isAudio()
        {
            $types = array(
                'mp3',
                'wav',
                'flac',
                'aac',
            );
            $type = strtolower($this->getType());

            if (in_array($type, $types)) {
                return true;
            }
            return false;
        }

        /**
         * Check if file is video playable
         *
         * @return true/false
         */
        public function isVideo()
        {
            $types = array(
                'mp4',
                'webm',
                'ogg',
                'ogv',
                // 'flv',
            );
            $type = strtolower($this->getType());

            if (in_array($type, $types)) {
                return true;
            }
            return false;
        }

        /**
         * Check if file is a pdf
         *
         * @return true/false
         */
        public function isPdf()
        {
            if (strtolower($this->getType()) == 'pdf') {
                return true;
            }
            return false;
        }

        /**
         * Check if file is valid for creating thumbnail
         *
         * @return true/false
         */
        public function isValidForThumb()
        {
            global $setUp;
            if ($setUp->getConfig('thumbnails') !== true && $setUp->getConfig('inline_thumbs') !== true) {
                return false;
            }
            if ($this->isImage() || ($this->isPdf() && ImageServer::isEnabledPdf())
            ) {
                return true;
            }
            return false;
        }

        /**
         * Check if file is valid for playing audio
         *
         * @return true/false
         */
        public function isValidForAudio()
        {
            global $setUp;
            if ($setUp->getConfig('playmusic') == true
                && $this->isAudio()
            ) {
                return true;
            }
            return false;
        }

        /**
         * Check if file is valid for playing video
         *
         * @return true/false
         */
        public function isValidForVideo()
        {
            global $setUp;
            if ($setUp->getConfig('playvideo') == true
                && $this->isVideo()
            ) {
                return true;
            }
            return false;
        }

    }
}
