<?php

namespace controllers\system {

    abstract class HtmlController extends SysController {

        private $smarty;
        private $smartyParams = [];
        private $jsInitParams = [];
        private $jsParams = [];

        public function getParam($key) {
            if (array_key_exists($key, $this->smartyParams)) {
                return $this->smartyParams[$key];
            }
            return null;
        }

        public function getParams() {
            return $this->smartyParams;
        }

        public function addParam($key, $value) {
            $this->smartyParams[$key] = $value;
        }

        public function deleteParam($key) {
            if (isset($this->smartyParams[$key])) {
                unset($this->smartyParams[$key]);
            }
        }

        public function addParams($params) {
            if (!is_array($params)) {
                return false;
            }
            if (!empty($params)) {
                $this->smartyParams = array_merge($this->smartyParams, $params);
            }
        }

        public function addParamKey($param, $key, $value) {
            $this->smartyParams[$param][$key] = $value;
        }

        public function mergeParamKey($param, $key, $arr) {
            $this->smartyParams[$param][$key] = array_merge($this->smartyParams[$param][$key], $arr);
        }

        public function addJsInitParams($params) {
            if (!is_array($params)) {
                return false;
            }
            $this->jsInitParams = array_merge($this->jsInitParams, $params);
        }

        public function addJsInitParam($key, $value) {
            $this->jsInitParams[$key] = $value;
        }

        public function addJsProperty($key, $value) {
            $this->jsParams[$key] = $value;
        }

        public function setException($exception) {
            parent::setException($exception);
            $this->smartyParams = [];
            $this->jsInitParams = [];
            $this->jsParams = [];
        }

        public function addJsProperties($params) {
            if (!is_array($params)) {
                return false;
            }
            $this->jsParams = array_merge($this->jsParams, $params);
        }

        private function getJsAllControllerAndInvolvedControllersArray($controller = null) {
            if (!isset($controller)) {
                $controller = $this;
            }
            $controllerName = $controller->getControllerName();
            $JsControllerName = str_replace('\\', '.', trim(substr($controllerName, strpos($controllerName, '\\', strpos($controllerName, '\\') + 1)), '\\'));
            $ret = [['name' => $JsControllerName, 'params' => $controller->jsInitParams, 'properties' => $controller->jsParams]];
            foreach ($controller->getInvolvedControllers() as $m) {
                $ret = array_merge($ret, $this->getJsAllControllerAndInvolvedControllersArray($m));
            }
            return $ret;
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

        private function addConfigParamsToSmarty() {
            $configArray = Sys()->getConfigArray();
            $this->smarty->assign('sys_config', $configArray);
        }

        private function addEnvironmentParamsToSmarty() {
            $env = Sys()->getEnvironment();
            $this->smarty->assign('sys_env', $env);
        }

        private function registerSysFunctionsToSmarty() {
            //$this->smarty->registerPlugin("function", "Sys", "smarty_system_registered_function");
            $this->smarty->registerPlugin("block", "sn", "smarty_system_registered_snippets_function");
        }

        protected function registerAdditionalFunctionsToSmarty() {
            return [];
        }

        private function _registerAdditionalFunctionsToSmarty() {
            $rafs = $this->registerAdditionalFunctionsToSmarty();
            if (!empty($rafs)) {
                $this->smarty->registerPlugin($rafs[0], $rafs[1], $rafs[2]);
            }
        }

        private function addAllParamsToSmarty($controller = null) {
            if (!isset($controller)) {
                $controller = $this;
            }
            foreach ($controller->smartyParams as $key => $value) {
                $this->smarty->assign($key, $value);
            }
            foreach ($controller->getInvolvedControllers() as $m) {
                $this->addAllParamsToSmarty($m);
            }
        }

        public function drawJSON($html) {
            header('Content-Type: application/json; charset=utf-8');
            if ($this->checkExceptions()) {
                return;
            } else {
                $params = ['html' => $html, 'controllers' => $this->getJsAllControllerAndInvolvedControllersArray()];
                echo json_encode($params);
            }
        }

        private function checkExceptions() {
            $httpResponseCode = $this->getException()->getCode();
            if ($httpResponseCode !== 200) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code($httpResponseCode); // not working even with , always returns 500.  ini_set('display_errors', true);
                if (Sys()->isDevelopmentMode()) {
                    $params ['sys_error_msg'] = $this->getException()->getMessage() . "\r\n" . $this->getException()->getTraceAsString();
                } else {
                    $params ['sys_error_msg'] = $this->getException()->getMessage();
                }
                $params ['sys_error_code'] = $httpResponseCode;
                echo json_encode($params);
                return true;
            }
            return false;
        }

        public function getSnippet($name, $returnEmptyIfEmpty = false, $languageCode = null) {
            $templatePath = trim($this->getTemplatePath(), '/');
            return service('Snippet')->get($templatePath, $name, $returnEmptyIfEmpty, $languageCode);
        }

        public function draw() {
            if ($this->checkExceptions()) {
                return;
            }
            if (Sys()->isProductionMode()) {
                $this->includeProductionJS();
            }

            $this->smarty = \system\util\SysSmarty::getInstance();
            $this->smarty->registerFilter("output", [$this, 'customHeader']);
            $this->addAllParamsToSmarty();
            $this->registerSysFunctionsToSmarty();
            $this->_registerAdditionalFunctionsToSmarty();
            $this->addConfigParamsToSmarty();
            $this->addEnvironmentParamsToSmarty();
            $this->addRouteParamsToSmarty();
            $this->addRequestToSmarty();
            $this->addAuthUserToSmarty();
            $httpResponseCode = $this->getException()->getCode();
            http_response_code($httpResponseCode);
            if (Sys()->isAjaxRequest()) {
                $html = $this->smarty->fetch($this->getTemplateFullPath());
                $this->drawJSON($html);
            } else {
                if ($httpResponseCode === 200) {
                    $this->smarty->display($this->getTemplateFullPath());
                } elseif (Sys()->isDevelopmentMode()) {
                    throw $this->getException();
                }
            }
        }

        public function initSysSuccessMessage() {
            $this->addParam('sys_success_message', Sys()->getSuccessMessage());
        }

        public function initSysErrorMessage() {
            $this->addParam('sys_error_message', Sys()->getErrorMessage());
        }

        public abstract function getTemplatePath();

        protected function getTemplateFullPath() {
            return VIEWS_DIR . DIRECTORY_SEPARATOR . trim($this->getTemplatePath(), '/');
        }

        public function customHeader($tpl_output, $template) {
            $jsString = '<meta name="generator" content="Sys Framework ' . Sys()->getVersion() . '" />';
            $jsString .= '<script type="text/javascript">';
            $jsAllControllerAndInvolvedControllersNames = json_encode($this->getJsAllControllerAndInvolvedControllersArray());
            $jsString .= 'docReady(function() {Sys.ready(' . $jsAllControllerAndInvolvedControllersNames . ');});';
            $jsString .= '</script>';
            $jsString .= '</head>';
            $tpl_output = str_replace('</head>', $jsString, $tpl_output);
            if (Sys()->isProductionMode()) {
                $tpl_output = preg_replace('![\t ]*[\r]+[\t ]*!', '', $tpl_output);
            }
            return $tpl_output;
        }

        private function includeProductionJS() {
            $sysJsIncludeTemplateFile = VIEWS_DIR . DIRECTORY_SEPARATOR . SUB_DOMAIN_DIR_FILE_NAME . DIRECTORY_SEPARATOR . PROD_JS_INCLUDE_TEMPLATE_DIR_NAME . DIRECTORY_SEPARATOR . PROD_JS_INCLUDE_TEMPLATE_FILE_NAME . '_' . Sys()->getVersion() . '.tpl';
            if (!file_exists($sysJsIncludeTemplateFile)) {
                $prodJsScriptInclude = \system\builders\JSBuilder::getProdJsInclude();
                file_put_contents($sysJsIncludeTemplateFile, $prodJsScriptInclude);
            }
        }

    }

}