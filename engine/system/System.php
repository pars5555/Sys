<?php

require_once("../vendor/autoload.php");
require_once '../Const.php';
require_once 'Exceptions.php';
require_once 'util/Prototype.php';

class System {

    private static $instance = null;
    private $config;
    private $services;
    private $sys_services;
    private $routing;

    private function __construct() {
        $this->services = [];
        $this->sys_services = [];
        $this->autoIncludeNamespaceUses();
        if (!file_exists(CONFIG_FILE)) {
            throw new \Exception('Missing config file for domain ' . CONFIG_FILE . PHP_EOL, 505);
        }
        $this->config = json_decode(file_get_contents(CONFIG_FILE), true);
        if (!empty($_REQUEST['sys_env']) && in_array($_REQUEST['sys_env'], ['prod', 'dev'])) {            
            $this->config['ENVIRONMENT'] = $_REQUEST['sys_env'];
        }
        $this->setErrorReporting();
        $this->routing = json_decode(file_get_contents(ROUTING_FILE), true);
        if (file_exists(CONSTANTS_FILE)) {
            require_once CONSTANTS_FILE;
        }
        if (file_exists(GLOBAL_FILE)) {
            require_once GLOBAL_FILE;
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new System();
        }
        return self::$instance;
    }

    public function init() {
        system\Router::getInstance()->route();
    }

    public function getRootDir() {
        return PROJECT_ROOT;
    }

    public function getConfigArray() {
        return $this->config;
    }

    public function getService($name, $params = [], $newInstance = false) {
        $fullName = '\\' . SERVICES_DIR_NAME . '\\' . $name;
        $fullName = str_replace('/', '\\', $fullName);
        $fullName = str_replace('.', '\\', $fullName);
        if (!$newInstance && array_key_exists($fullName, $this->services)) {
            return $this->services[$fullName];
        }
        try {
            $class = new ReflectionClass($fullName);
        } catch (Exception $exc) {
            return $this->getSysService($name, $params, $newInstance);
        }
        $instance = $class->newInstanceArgs(!empty($params) ? $params : []);
        $this->services[$fullName] = $instance;
        return $instance;
    }

    public function getSysService($name, $params = [], $newInstance = false) {
        $fullName = '\\' . SYSTEM_DIR_NAME . '\\' . SERVICES_DIR_NAME . '\\' . $name;
        $fullName = str_replace('/', '\\', $fullName);
        $fullName = str_replace('.', '\\', $fullName);
        if (!$newInstance && array_key_exists($fullName, $this->sys_services)) {
            return $this->sys_services[$fullName];
        }
        try {
            $class = new ReflectionClass($fullName);
            $instance = $class->newInstanceArgs(!empty($params) ? $params : []);
            $this->sys_services[$fullName] = $instance;
            return $instance;
        } catch (Exception $exc) {
            throw $exc;
        }
    }

    public function getConfig($name, $default = null) {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        $nameParts = explode('.', $name);
        if (count($nameParts) > 1 && isset($this->config[$nameParts [0]])) {
            $c = $this->config[$nameParts[0]];
            foreach ($nameParts as $key => $namePart) {
                if ($key == 0) {
                    continue;
                }
                if (isset($c[$namePart])) {
                    $c = $c[$namePart];
                } else {
                    $c = null;
                    break;
                }
            }
            if (isset($c)) {
                return $c;
            }
        }

        return $default;
    }

    public function getRoutings() {
        return $this->routing;
    }

    public function reloadPreviousePage() {
        header('Location: ' . $_SERVER["HTTP_REFERER"]);
        exit;
    }

    public function redirect($url) {
        $url = trim($url, '\\/');
        header("location: " . SITE_PATH . "/" . $url);
        exit;
    }

    public function redirectToController($controller, $params = []) {
        system\Router::getInstance()->initControllerFromJSpath($controller, $params);
        exit;
    }

    public function getRequestPathMatchedValues() {
        return system\Router::getInstance()->getRequestPathMatchedValues();
    }

    public function isDevelopmentMode() {
        $env = $this->getEnvironment();
        return substr($env, 0, 3) === 'dev';
    }

    public function isProductionMode() {
        $env = $this->getEnvironment();
        return substr($env, 0, 3) !== 'dev';
    }

    public function getEnvironment() {
        return $this->config['ENVIRONMENT'];
    }

    public function getEnv() {
        return $this->config['ENVIRONMENT'];
    }

    public function getSubDomain() {
        return SUB_DOMAIN;
    }

    public function getVersion() {
        return $this->config['VERSION'];
    }

    public function setSuccessMessage($message) {
        return \system\Session::getInstance()->setSessionParam('sys_success_message', $message);
    }

    public function setErrorMessage($message) {
        return \system\Session::getInstance()->setSessionParam('sys_error_message', $message);
    }

    public function getErrorMessage($removeFromSession = true) {
        $message = \system\Session::getInstance()->getSessionParam('sys_error_message');
        if ($removeFromSession) {
            \system\Session::getInstance()->unsetSessionParam('sys_error_message');
        }
        return $message;
    }

    public function getSuccessMessage($removeFromSession = true) {
        $message = \system\Session::getInstance()->getSessionParam('sys_success_message');
        if ($removeFromSession) {
            \system\Session::getInstance()->unsetSessionParam('sys_success_message');
        }
        return $message;
    }

    public function getRequestMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function checkRequestMethod($method = "") {
        return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
    }

    public function isPostRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public function isAjaxRequest() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        return false;
    }

    public function request($key = null, $default = null, $delete = false) {
        if (isset($key)) {
            return \system\Request::getInstance()->request($key, $default, $delete);
        }
        return \system\Request::getInstance();
    }

    public function getInputParam($key, $default = null) {
        return \system\Request::getInstance()->getInputParam($key, $default);
    }

    public function getHeaderParam($key = null, $default = null) {
        return \system\Request::getInstance()->header($key, $default);
    }

    public function getCookieParam($key = null, $default = null, $filter = FILTER_DEFAULT) {

        $lang = \system\Request::getInstance()->cookie($key, $default, $filter);
        if ($lang == 'am') {
            $lang = 'hy';
        }
        return $lang;
    }

    public function setCookieLanguage($lang, $expire = null) {
        if ($lang == 'am') {
            $lang = 'hy';
        }
        if (!isset($expire)) {
            $expire = $this->getConfig('languages.expire_seconds');
        }
        $_COOKIE['lang'] = $lang;
        \system\Session::getInstance()->setCookieParam('lang', $lang, time() + $expire);
    }

    public function getSessionParam($key = null, $default = null, $filter = FILTER_DEFAULT) {
        return \system\Request::getInstance()->session($key, $default, $filter);
    }

    public function getHostIpAddress() {
        switch (true) {
            case (!empty($_SERVER['HTTP_X_REAL_IP'])) : return $_SERVER['HTTP_X_REAL_IP'];
            case (!empty($_SERVER['HTTP_CLIENT_IP'])) : return $_SERVER['HTTP_CLIENT_IP'];
            case (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) : return $_SERVER['HTTP_X_FORWARDED_FOR'];
            case (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) : return $_SERVER['HTTP_CF_CONNECTING_IP'];
            case (!empty($_SERVER['REMOTE_ADDR'])) : return $_SERVER['REMOTE_ADDR'];
            default :
                if (php_sapi_name() == "cli") {
                    return "cli";
                }
                return 'Unknown';
        }
    }

    private function autoIncludeNamespaceUses() {
        spl_autoload_register('splAutoLoadRegisterFunction');
    }


    public function setErrorReporting() {
        if (isset($this->config['display_errors'])) {
            ini_set('display_errors', $this->config['display_errors']);
        } else {
            ini_set('display_errors', 1);
        }
        if (isset($this->config['error_reporting'])) {
            $error_reporting = 1;
            $cer = $this->config['error_reporting'];
            eval('$error_reporting = ' . $cer . ';');
            error_reporting($error_reporting);
        } else {
            error_reporting(E_ALL);
        }
    }

}


function Sys() {
    return System::getInstance();
}

function service($name, $params = [], $newInstance = false) {
    if (!is_array($params)) {
        $params = [$params];
    }
    return Sys()->getService($name, $params, $newInstance);
}

function MysqlService($tableName, $entity = null) {
    return sysservice('SysMysql', [$tableName, $entity], true);
}

function sysservice($name, $params = [], $newInstance = false) {
    if (!is_array($params)) {
        $params = [$params];
    }
    return Sys()->getSysService($name, $params, $newInstance);
}

function smarty_system_registered_function($params, $smarty) {

    if (isset($params["fn"])) {
        $fname = $params["fn"];
        $fparams = [];
        if (isset($params["params"])) {
            $fparams = $params["params"];
            if (!is_array($fparams)) {
                $fparams = [$fparams];
            }
        }
        return call_user_func_array([Sys(), $fname], $fparams);
    } else {
        return 'Missing Function Name param!';
    }
}

function smarty_system_registered_snippets_function($params, $content, $smarty, &$repeat, $template = null) {
    $projectViewsDir = str_replace('\\', '/', VIEWS_DIR);
    $templateFileFullPath = str_replace('\\', '/', $smarty->source->filepath);
    $namespace = trim(str_replace($projectViewsDir, '', $templateFileFullPath), '/');
    $lang = null;
    if (isset($params['lang'])) {
        $lang = $params['lang'];
    }
    $content = trim($content);
    if (empty($content)) {
        return "";
    }
    $returnEmptyIfEmpty = isset($params['return_empty_if_empty']);

    $ret = service('Snippet')->get($namespace, $content, $returnEmptyIfEmpty, $lang);
    if (isset($params['replace'])) {
        $ret = str_replace(array_keys($params['replace']), array_values($params['replace']), $ret);
    }
    return $ret;
}

function splAutoLoadRegisterFunction($use) {
    $use = str_replace('\\', '/', $use);
    $filePath = realpath(ENGINE_DIR . '/' . $use . '.php');
    if (file_exists($filePath)) {
        require_once($filePath);
    } else {
        if (strpos($use, 'Smarty_') !== 0) {
            \system\SysExceptions::fileNotFound($use);
        }
    }
}

Sys()->init();

