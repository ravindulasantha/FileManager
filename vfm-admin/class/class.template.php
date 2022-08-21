<?php

if (!class_exists('Template', false)) {
  
    class Template
    {
       
        public function includeTpl( $file )
        {
            if (file_exists(dirname(dirname(__FILE__)).'/_content/template/'.$file.'.php')) {
                $path = '/_content/template/'.$file.'.php';
            } else {
                $path = '/template/'.$file.'.php';
            }
            return $path;
        }
    }
}
