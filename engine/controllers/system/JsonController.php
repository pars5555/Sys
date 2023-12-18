<?php

namespace controllers\system {

    abstract class JsonController extends SysController {

        private $params = [];

        public function addParam($key, $value) {
            $this->params[$key] = $value;
        }

        public function getParam($key) {
            if (array_key_exists($key, $this->params)) {
                return $this->params[$key];
            }
            return null;
        }

        public function addParams($params) {
            $this->params = array_merge($this->params, (array)$params);
        }

        public function deleteParam($key) {
            if (isset($this->params[$key])) {
                unset($this->params[$key]);
            }
        }

        public function compileTemplate($templateRelativePath, $params) {
            $this->smarty = \system\util\SysSmarty::getInstance();
            $this->addGivenParamsToSmarty($params);
            $this->addConfigParamsToSmarty();
            $this->addEnvironmentParamsToSmarty();
            $this->addRouteParamsToSmarty();
            $this->addRequestToSmarty();
            $this->addAuthUserToSmarty();
            $httpResponseCode = $this->getException()->getCode();
            http_response_code($httpResponseCode);
            return $this->smarty->fetch($this->getTemplateFullPath($templateRelativePath));
        }
        
        private function getTemplateFullPath($templateRelativePath) {
            return VIEWS_DIR . DIRECTORY_SEPARATOR . trim($templateRelativePath, '/');
        }

        public function draw() {
            header('Content-Type: application/json');
            $httpResponseCode = $this->getException()->getCode();
            if (!(intval($httpResponseCode) >= 100 && intval($httpResponseCode) <= 530)) {
                $httpResponseCode = 500;
            }
            http_response_code($httpResponseCode);
            if ($httpResponseCode !== 200) {
                if (Sys()->isDevelopmentMode()) {
                    $this->params['sys_error_msg'] = $this->getException()->getMessage() . "\r\n" . $this->getException()->getTraceAsString();
                } else {
                    $this->params['sys_error_msg'] = $this->getException()->getMessage();
                }
                $this->params['sys_error_code'] = $httpResponseCode;
            }

            if (Sys()->isDevelopmentMode()) {
                echo json_encode($this->params, JSON_PRETTY_PRINT) . "\r\n";
            } else {
                echo json_encode($this->params) . "\r\n";
            }
        }

        private function addGivenParamsToSmarty($params) {
            foreach ($params as $key => $value) {
                $this->smarty->assign($key, $value);
            }
        }

        private function registerSysFunctionsToSmarty() {
            $this->smarty->registerPlugin("block", "sn", "smarty_system_registered_snippets_function");
        }
        
        private function addConfigParamsToSmarty() {
            $configArray = Sys()->getConfigArray();
            $this->smarty->assign('sys_config', $configArray);
        }
        
        private function addEnvironmentParamsToSmarty() {
            $env = Sys()->getEnvironment();
            $this->smarty->assign('sys_env', $env);
        }
        
        private function addRouteParamsToSmarty() {
            $this->smarty->assign('sys_route', \system\Router::getInstance()->getRequestUri());
        }
        
        private function addRequestToSmarty() {
            $this->smarty->assign('sys_request', Sys()->request());
        }
        
        private function addAuthUserToSmarty() {
            $authUser = sysservice('Auth')->getAuthUser();
            $authUserObject = null;
            if (isset($authUser)) {
                $authUserObject = $authUser->getObject();
            }
            $this->smarty->assign('sys_auth_user', $authUserObject);
        }

    }

}