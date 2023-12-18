<?php

namespace system\services {

    abstract class Rest {

        private $protocol;
        private $host;

        function __construct($config) {
            $this->protocol = $config['protocol'];
            $this->host = trim($config['host'], '/\\');
        }

        public function get($path, $params) {
            $curl = new \Curl\Curl();
            $curl->get($this->builUrl($path), $params);
        }

        public function post($path, $urlParams = [], $params = [], $headers = []) {
            $curl = new \Curl\Curl();
            $curl->setDefaultDecoder('json');
            $curl->setHeaders($headers);
            return $curl->post($this->builUrl($path, $urlParams), $params);
        }

        private function builUrl($path = '', $urlParams = []) {
            $path = trim($path, '/\\');
            $q = '';
            if (!empty($urlParams)) {
                $q = '?'. http_build_query($urlParams, '', '&');
            }
            return $this->protocol . '://' . $this->host . '/' . $path . $q;
        }

    }

}
