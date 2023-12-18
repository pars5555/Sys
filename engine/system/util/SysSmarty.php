<?php

namespace system\util {

    class SysSmarty extends \Smarty {

        private static $instance = null;

        function __construct() {
            parent::__construct();
            $this->setTemplateDir(VIEWS_DIR);
            $this->setCompileDir(VIEWS_DIR . '/cache');
            $this->setCompileCheck(Sys()->getConfig('smarty.compile_check'));
            $this->setCacheLifetime(Sys()->getConfig('smarty.cache_lifetime'));
            $this->setCaching(Sys()->getConfig('smarty.caching'));
            $this->assign("SITE_PATH", SITE_PATH);
            $this->assign("BASE_SITE_PATH", BASE_SITE_PATH);
            $this->assign("HOST", HOST);
            $this->assign("VIEWS_DIR", VIEWS_DIR);
        }

        public static function getInstance() {
            if (is_null(self::$instance)) {
                self::$instance = new SysSmarty();
            }

            return self::$instance;
        }

        public static function compileString($string, $params = []) {
            $smarty = \system\util\SysSmarty::getInstance();
            $smarty->assign('sc', service('Snippet'));
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $smarty->assign($key, $value);
                }
            }
            return $smarty->fetch('string:' . $string);
        }

    }

}
    