<?php

namespace system {

    class Session {

        private static $instance = null;

        private function __construct() {
            Session::startSession();
        }

        public static function getInstance() {
            if (self::$instance == null) {
                self::$instance = new Session();
            }
            return self::$instance;
        }

        public function setCookieParam($name, $value, $expire, $domain = DOMAIN) {
            setcookie($name, $value, $expire, '/', $domain);
        }

        public function setSessionParam($name, $value) {
            $_SESSION[$name] = $value;
        }

        public function getSessionParam($key, $default = null, $filter = FILTER_DEFAULT) {
            return Request::getInstance()->session($key, $default, $filter);
        }

        public function unsetSessionParam($key) {
            if (array_key_exists($key, $_SESSION)) {
                unset($_SESSION[$key]);
            }
        }

        public function getCookieParam($key, $default = null, $filter = FILTER_DEFAULT) {
            return Request::getInstance()->cookie($key, $default, $filter);
        }

        public static function startSession() {
            if (session_status() == PHP_SESSION_NONE) {
                session_set_cookie_params(0, '/', '.' . DOMAIN);
                session_start();
            }
        }

    }

}
    