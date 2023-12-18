<?php

namespace system {

    class Request {

        private static $instance;
        private $headers;
        private static $input = null;
        private static $inputJsonDecoded = null;

        private function __construct() {
            
        }

        public static function getInstance() {
            if (self::$instance == null) {
                self::$instance = new Request();
            }
            return self::$instance;
        }

        public function getInt($key, $default = null, $delete = false) {
            return intval($this->request($key, $default, $delete));
        }
        
        public function getFloat($key, $default = null, $delete = false) {
            return floatval($this->request($key, $default, $delete));
        }
        
        public function getArray($key, $delimiter = ',') {
            $arr = urldecode(trim($this->request($key, '')));
            if (empty($arr)){
                return [];
            }
            return explode($delimiter, $arr);
        }
        
        public function request($key, $default = null, $delete = false) {
            $ret = $this->get($key, $default, $delete);
            if ($ret === $default) {
                return $this->post($key, $default, $delete);
            }
            return $ret;
        }

        public function get($key, $default = null, $delete = false) {
            if (array_key_exists($key, $_GET)) {
                $ret = $_GET[$key];
                if ($delete) {
                    unset ($_GET[$key]);
                }
                return $ret;
            }
            return $default;
        }

        public function post($key, $default = null, $delete = false) {
            if (array_key_exists($key, $_POST)) {
                $ret = $_POST[$key];
                if ($delete) {
                    unset ($_POST[$key]);
                }
                return $ret;
            }
            return $default;
        }

        public function cookie($key, $default = null, $filter = FILTER_DEFAULT) {
            if (array_key_exists($key, $_COOKIE)) {
                return filter_var($_COOKIE[$key], $filter);
            }
            return $default;
        }

        public function session($key, $default = null, $filter = FILTER_DEFAULT) {
            if (array_key_exists($key, $_SESSION)) {
                return filter_var($_SESSION[$key], $filter);
            }
            return $default;
        }
        
        public function header($key, $default = null) {
            if (!isset($this->headers)){
            $this->headers = getallheaders();
            }
            if (array_key_exists($key, $this->headers)) {
                return $this->headers[$key];
            }
            return $default;
        }
        
        public function input() {
            if (!isset(self::$input)) {
                self::$input = file_get_contents('php://input');
            }
            return self::$input;                           
        }

        public function inputJson($assoc = false) {
            return json_decode($this->input(), $assoc);
        }

        public function getInputParam($key = null, $default = null) {
            if ($inputJsonDecoded === null) {
                $inputJsonDecoded = $this->inputJson();
            }
            if (isset($inputJsonDecoded) && isset($inputJsonDecoded->$key)) {
                return $inputJsonDecoded->$key;
            }
            return $default;
        }

    }

}
    