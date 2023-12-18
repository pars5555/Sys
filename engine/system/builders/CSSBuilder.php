<?php

namespace system\builders {

    class CSSBuilder {

        public static function streamCss() {
            $cssFiles = \system\util\Util::getDirectoryFiles(CSS_DIR, 'css', true);
            header('Content-type: text/css');
            foreach ($cssFiles as $file) {
                $inputFile = SITE_PATH . "/css/" . SUB_DOMAIN_DIR_FILE_NAME . '/'. trim($file);
                $unique = uniqid('sys', true);
                echo '@import url("' . $inputFile . '?'.$unique.'");'. PHP_EOL;
            }
        }

        /*  public static function streamProdCss() {
            $cssFiles = \system\util\Util::getDirectoryFiles(CSS_DIR, 'css', false);
            $allFileContents = "";
            foreach ($cssFiles as $file) {
                $allFileContents .= file_get_contents($file);
            }
            header('Content-type: text/css');
            $css = self::minifyCss($allFileContents);            
            @mkdir(dirname(PUBLIC_OUT_CSS_FILE), 0755, true); 
            file_put_contents(PUBLIC_OUT_CSS_FILE, $css);
            \system\util\FileStreamer::sendFile(PUBLIC_OUT_CSS_FILE, array("mimeType" => "text/css", "cache" => true));
        }
*/
        //public static function minifyCss($css) {
        //    return str_replace(array("\r\n", "\r", "\n", "\t"), '', preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css));
       // }

    }

}