<?php

namespace system\builders {

    class JSBuilder {

        public static function streamJs() {
            //if (Sys()->isDevelopmentMode()) {
                self::streamDevJs();
            //}
        }

        public static function streamDevJs() {
            header('Content-type: text/javascript');
            $jsFiles = \system\util\Util::getDirectoryFiles(JS_COMMON_DIR, 'js', true);
            foreach ($jsFiles as $file) {
                $inputFile = SITE_PATH . "/js/common/" . trim($file);
                $unique = uniqid('sys', true);
                echo("document.write('<script type=\"text/javascript\" src=\"" . $inputFile . "?$unique\"></script>');\n\r");
            }
            $jsFiles = \system\util\Util::getDirectoryFiles(JS_DIR, 'js', true);
            foreach ($jsFiles as $file) {
                $inputFile = SITE_PATH . "/js/" . SUB_DOMAIN_DIR_FILE_NAME . '/' . trim($file);
                $unique = uniqid('sys', true);
                echo("document.write('<script type=\"text/javascript\" src=\"" . $inputFile . "?$unique\"></script>');\n\r");
            }
        }

        public static function getProdJsInclude() {
            $ret = "";
            $jsCommonFiles = \system\util\Util::getDirectoryFiles(JS_COMMON_DIR, 'js', true);
            foreach ($jsCommonFiles as $file) {
                $ret .= '<script type="text/javascript" src="/js/out/' . JS_COMMON_DIR_NAME . '/' . $file . '?' . Sys()->getVersion() . '"></script>' . "\n\r";
            }
            $jsFiles = \system\util\Util::getDirectoryFiles(JS_DIR, 'js', true);
            foreach ($jsFiles as $file) {
                $ret .= '<script type="text/javascript" src="/js/out/' . SUB_DOMAIN_DIR_FILE_NAME . '/' . $file . '?' . Sys()->getVersion() . '"></script>' . "\n\r";
            }
            self::minifyProdJs();
            return $ret;
        }

        private static function minifyProdJs() {
            $dir = PUBLIC_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'out' . DIRECTORY_SEPARATOR . JS_COMMON_DIR_NAME . DIRECTORY_SEPARATOR;

            $ret = "";
            $jsCommonFiles = \system\util\Util::getDirectoryFiles(JS_COMMON_DIR, 'js', false);
            $jsCommonFilesRel = \system\util\Util::getDirectoryFiles(JS_COMMON_DIR, 'js', true);
            foreach ($jsCommonFiles as $key => $file) {
                $relPath = $jsCommonFilesRel[$key];
                $d = dirname($dir . $relPath);
                if (!is_dir($d)) {
                    mkdir($d, 0777, true);
                }
                if (strpos('.min', $file) === false) {
                    $content = JSMinifier::minify(file_get_contents($file));
                } else {
                    $content = file_get_contents($file);
                }
                file_put_contents($dir . $relPath, $content);
            }

            $dir = PUBLIC_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'out' . DIRECTORY_SEPARATOR . SUB_DOMAIN_DIR_FILE_NAME . DIRECTORY_SEPARATOR;
            $jsCommonFiles = \system\util\Util::getDirectoryFiles(JS_DIR, 'js', false);
            $jsCommonFilesRel = \system\util\Util::getDirectoryFiles(JS_DIR, 'js', true);
            foreach ($jsCommonFiles as $key => $file) {
                $relPath = $jsCommonFilesRel[$key];
                $d = dirname($dir . $relPath);
                if (!is_dir($d)) {
                    mkdir($d, 0777, true);
                }
                if (strpos('.min', $file) === false) {
                    $content = JSMinifier::minify(file_get_contents($file));
                } else {
                    $content = file_get_contents($file);
                }
                file_put_contents($dir . $relPath, $content);
            }
        }

    }

}
