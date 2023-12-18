<?php

namespace system {

    class Router {

        private $routings;
        private $requestUri;
        private static $instance = null;
        private $requestPathMatchedValues = [];

        public function __construct() {
            if (filter_var(trim($_SERVER["REQUEST_URI"], '/'), FILTER_VALIDATE_URL)) {
                $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
                if (isset($parseUrl['query'])) {
                    $_SERVER["REQUEST_URI"] = ltrim($parseUrl['path'] . '?' . $parseUrl['query'], '/');
                } else {
                    $_SERVER["REQUEST_URI"] = ltrim($parseUrl['path']);
                }
            }

            $this->requestUri = trim(preg_replace('~/+~', '/', strtok($_SERVER["REQUEST_URI"], '?')), '/');
            $this->initRoutingsInArray();
        }

        private function initRoutingsInArray() {
            $routings = Sys()->getRoutings();
            $this->routings = [];
            foreach ($routings as $uri => $value) {
                $uriParts = explode('/', trim(preg_replace('~/+~', '/', $uri), '/'));
                if (count($uriParts) > 0) {
                    $routingRootPath = $uriParts[0];
                    if (!isset($this->routings [$routingRootPath])) {
                        $this->routings [$routingRootPath] = [];
                    }
                    $this->routings [$routingRootPath][] = ['uri_parts' => $uriParts, 'data' => $value, 'dept' => count($uriParts)];
                }
            }
        }

        public static function getInstance() {
            if (!isset(self::$instance)) {
                self::$instance = new Router();
            }
            return self::$instance;
        }

        function getRequestUri() {
            return $this->requestUri;
        }

        public function route() {
            if ($this->checkFile()) {
                return;
            }
            if ($this->checkEmptyRoute()) {
                return;
            }
            if ($this->checkCssRoute()) {
                return;
            }
            if ($this->checkJsRoute()) {
                return;
            }            
            if ($this->checkSystemRoute()) {
                return;
            }
            if ($this->checkSystemDevtoolsRoute()) {
                return;
            }
            if ($this->_route()) {
                return;
            } else {
                $languageInUrlEnable = Sys()->getConfig('languages.url_first_part.enable');
                if ($languageInUrlEnable && $this->_route(true)) {
                    return;
                }
            }
            if ($this->checkInstallRoute()) {
                return;
            }
            $this->goToNotFoundPage();
        }

        function getRequestPathMatchedValues() {
            return $this->requestPathMatchedValues;
        }

        private function checkCssRoute() {
            if ($this->requestUri === 'out/' . ALL_CSS_FILE) {
                builders\CSSBuilder::streamCss();
                return true;
            }
            return false;
        }

        private function checkJsRoute() {
            if ($this->requestUri === 'out/' . ALL_JS_FILE) {
                builders\JSBuilder::streamJs();
                return true;
            }
            return false;
        }

        private function checkEmptyRoute() {
            if (empty($this->requestUri)) {
                $this->defaultRoute();
                return true;
            }
            return false;
        }

        private function goToNotFoundPage() {
            if (!array_key_exists('sysnotfound', $this->routings)) {
                SysExceptions::routeNotFound($this->requestUri);
            }
            $this->defineLanguage();
            $matchedRouting = $this->routings['sysnotfound'][0];
            $controllerObject = $this->initRouteController($matchedRouting['data']);
            $this->initRouteInvolvedControllers($matchedRouting['data'], $controllerObject);
            $controllerObject->draw();
            exit;
        }

        private function checkSystemDevtoolsRoute() {
            if (substr($this->requestUri, 0, strlen(DEVTOOLS_ROUTE_PREFIX)) === DEVTOOLS_ROUTE_PREFIX) {
               return $this->dynamicRoute('system', DEVTOOLS_ROUTE_PREFIX);
            }
            return false;
            
        }
        private function checkSystemRoute() {
            if (substr($this->requestUri, 0, strlen(DYNAMIC_ROUTE_PREFIX)) === DYNAMIC_ROUTE_PREFIX) {
               return $this->dynamicRoute();
            }
            return false;
        }

        private function checkFile() {
            $filePath = realpath(PUBLIC_DIR . DIRECTORY_SEPARATOR . $this->requestUri);
            if (file_exists($filePath) && is_file($filePath)) {
                util\FileStreamer::sendFile($filePath);
                return true;
            }
        }

        private function _route($trimUrlFirstPart = false) {
            $requestUriParts = explode('/', $this->requestUri);
            $lang = false;
            if ($trimUrlFirstPart && count($requestUriParts) >= 1) {
                $availableLangCodeArray = Sys()->getConfig('languages.url_first_part.available_lang_codes');
                if (!in_array($requestUriParts[0], $availableLangCodeArray)) {
                    return false;
                }
                $lang = $requestUriParts[0];
                array_shift($requestUriParts);
            }
            if (!empty($requestUriParts)) {
                $dept = count($requestUriParts);
                $requestRootUriPart = $requestUriParts [0];
                $matchedRoutings = $this->getMatchedRoutingsToGivenRootPath($requestRootUriPart, $dept);
                if (empty($matchedRoutings)) {
                    return false;
                }
                $matchedRouting = $this->getStrictMatchedRoutingToGivenPath($matchedRoutings, $requestUriParts);
                if (!$matchedRouting) {
                    return false;
                }
            }
            if ($trimUrlFirstPart) {
                $this->requestUri = ltrim($this->requestUri, $lang);
                $this->requestUri = trim($this->requestUri, '/');
            }
            if (empty($requestUriParts)) {
                $this->defaultRoute($lang);
                return true;
            }
            $this->defineLanguage($lang);
            $controllerObject = $this->initRouteController($matchedRouting['data']);
            $this->initRouteInvolvedControllers($matchedRouting['data'], $controllerObject);
            $controllerObject->draw();
            return true;
        }

        private function initControllerInvolvedControllers($controllerObject) {
            $controllers = [];
            foreach ($controllerObject->getInvolvedControllersClasses() as $controllerClassFullName) {
                $m = $this->initController($controllerClassFullName, $controllerObject);
                $controllers[] = $m;
                $this->initControllerInvolvedControllers($m);
            }
            $controllerObject->addInvolvedControllers($controllers);
        }

        private function initRouteInvolvedControllers($route, $controllerObject) {
            $involves = $this->getRouteInvolves($route);
            $this->initControllerInvolvedControllers($controllerObject);
            if (!empty($involves)) {
                if (!array($involves)) {
                    $involves = [$involves];
                }
                $controllers = [];
                foreach ($involves as $involve) {
                    $m = $this->initRouteController($involve, $controllerObject);
                    $controllers [] = $m;
                    $this->initRouteInvolvedControllers($involve, $m);
                }
                $controllerObject->addInvolvedControllers($controllers);
            }
        }

        private function checkInstallRoute() {
            if ($this->requestUri === 'sys_install') {
                $controllerClass = '\\controllers\\system\\install\\Index';
                $controllerObject = $this->initController($controllerClass);
                $controllerObject->draw();
                return true;
            }
            if ($this->requestUri === 'sys_install') {
                
            }
            return false;
        }

        private function getRouteInvolves($route) {
            if (array_key_exists('involve', $route) && !empty($route['involve'])) {
                return $route['involve'];
            }
            return false;
        }

        private function initRouteController($route, $parentController = null) {
            if (empty($route) || (!array_key_exists('controller', $route) || empty($route['controller'])) &&
                    (!array_key_exists('file', $route) || empty($route['file'])) &&
                    (!array_key_exists('redirect', $route) || empty($route['redirect']))
            ) {
                SysExceptions::controllerAttributeNotFound($this->requestUri);
                exit;
            }
            if (array_key_exists('controller', $route)) {
                $controllerPath = trim($route['controller']);
                $controllerClass = '\\controllers\\' . SUB_DOMAIN_DIR_FILE_NAME . '\\' . str_replace('.', '\\', $controllerPath);
                $params = [];
                if (array_key_exists('params', $route)) {
                    $params = $route['params'];
                }
                return $this->initController($controllerClass, $parentController, $params);
            }
            if (array_key_exists('file', $route)) {
                $filePath = ENGINE_DIR . '\\controllers\\' . SUB_DOMAIN_DIR_FILE_NAME . '\\' . trim($route['file']);
                include $filePath;
                exit;
            }
            if (array_key_exists('redirect', $route)) {
                header("Location: " . trim($route['redirect'])); /* Redirect browser */
                exit();
            }
        }

        private function classFileExists($controllerClassPath) {
            $use = str_replace('\\', '/', $controllerClassPath);
            $filePath = realpath(ENGINE_DIR . '/' . $use . '.php');
            return file_exists($filePath);
        }

        private function initController($controllerClassPath, $parentController = null, $params = []) {
            if (!$this->classFileExists($controllerClassPath)){
                $this->goToNotFoundPage();
            }
            try {
                $controllerObject = new $controllerClassPath();
            } catch (\Exception $exc) {
                if (Sys()->isDevelopmentMode()) {
                    throw $exc;
                }
                if ($exc->getCode() == 1045) {
                    echo 'Mysql connection error!';
                    exit;
                }
                return false;
            }
            $validated = sysservice('Security')->validate($controllerObject);
            if (!$validated) {
                if (Sys()->isAjaxRequest()) {
                    $controllerObject->noAccessAjax();
                    return false;
                } else {
                    $controllerObject->noAccess();
                    return false;
                }
            }

            $controllerObject->setParentController($parentController);
            if (!empty($params) && method_exists($controllerObject, 'addParams')) {
                $controllerObject->addParams($params);
            }
            try {
                $numberOfControllerInitFunctionParameters = (new \ReflectionMethod($controllerClassPath, 'init'))->getNumberOfParameters();
                if ($numberOfControllerInitFunctionParameters > 0 && !empty($this->requestPathMatchedValues)) {
                    call_user_func_array([$controllerObject, "init"], $this->requestPathMatchedValues);
                } else {
                    $controllerObject->init();
                    restore_error_handler();
                }
                $redirectParams = Sys()->request('_sys_redirect', null, true);
                if (!empty($redirectParams)) {
                    $params = isset($redirectParams['params']) ? $redirectParams['params'] : $redirectParams;
                    Sys()->redirectToController($redirectParams['controller'], $params);
                }
            } catch (\Exception $exc) {
                $controllerObject->setException($exc);
                if (Sys()->isDevelopmentMode()) {
                    throw $exc;
                }
            }
            return $controllerObject;
        }

        private function defaultRoute($lang = false) {
            if (!array_key_exists('sysdefault', $this->routings)) {
                SysExceptions::defaultRouteNotFound();
            }
            $matchedRouting = $this->routings['sysdefault'][0];
            $this->defineLanguage($lang);
            $controllerObject = $this->initRouteController($matchedRouting['data']);
            $this->initRouteInvolvedControllers($matchedRouting['data'], $controllerObject);
            $controllerObject->draw();
            return true;
        }

        private function dynamicRoute($controllerFirstLevelDir = SUB_DOMAIN_DIR_FILE_NAME, $dynamicRoutePrefix = DYNAMIC_ROUTE_PREFIX) {
            $this->defineLanguage();
            $controllerPath = str_replace(['/'], ['\\'], trim(substr($this->requestUri, strlen($dynamicRoutePrefix)), '\\/'));
            $controllerClass = '\\controllers\\' . $controllerFirstLevelDir . '\\' . str_replace('.', '\\', $controllerPath);
            $controllerObject = $this->initController($controllerClass, null);
            if (empty($controllerObject)) {
                return false;
            }
            $this->initControllerInvolvedControllers($controllerObject);
            $controllerObject->draw();
            return true;
        }

        public function initControllerFromJSpath($controllerJsPath, $params = []) {
            $this->defineLanguage();
            $controllerClass = '\\controllers\\' . SUB_DOMAIN_DIR_FILE_NAME . '\\' . str_replace('.', '\\', $controllerJsPath);
            $controllerObject = $this->initController($controllerClass, null, $params);
            $this->initControllerInvolvedControllers($controllerObject);
            $controllerObject->draw();
        }

        private function routeMatched($uri_parts, $requestUriParts) {
            foreach ($uri_parts as $index => $pathElement) {
                if ($pathElement[0] === ROUTING_REGEX_START_CHAR) {
                    $this->requestPathMatchedValues[] = $requestUriParts[$index];
                }
                if ($pathElement !== $requestUriParts[$index] && !$this->matchPatern($pathElement, $requestUriParts[$index])) {
                    return false;
                }
            }
            return true;
        }

        private function getStrictMatchedRoutingToGivenPath($routings, $requestUriParts) {
            foreach ($routings as $route) {
                if ($this->routeMatched($route['uri_parts'], $requestUriParts)) {
                    return $route;
                }
            }
            return false;
        }

        /**
         * Returns matched routing to given ROOT path only
         */
        private function getMatchedRoutingsToGivenRootPath($rootPath, $dept) {
            $matchedRoutings = [];
            if (isset($this->routings[$rootPath])) {
                foreach ($this->routings[$rootPath] as $route) {
                    if ($route['dept'] === $dept) {
                        $matchedRoutings [] = $route;
                    }
                }
            }
            foreach ($this->routings as $routingRootPath => $routes) {
                if ($routingRootPath [0] !== ROUTING_REGEX_START_CHAR) {
                    continue;
                }
                foreach ($routes as $route) {
                    if ($route['dept'] !== $dept) {
                        continue;
                    }
                    if ($this->matchPatern($routingRootPath, $rootPath)) {
                        $matchedRoutings[] = $route;
                    }
                }
            }
            return $matchedRoutings;
        }

        private function matchPatern($pattern, $string) {
            if ($pattern [0] !== ROUTING_REGEX_START_CHAR) {
                return $pattern === $string;
            }
            $pattern = substr($pattern, 1, -1);
            if ($pattern === 'number' && strval(intval($string)) === $string) {
                return true;
            }
            if ($pattern === 'slug') {
                return true;
            }
            if (@preg_match("/$pattern/", $string)) {
                return true;
            }
        }

        private function defineLanguage($lang = false) {
            if (!empty($lang)) {
                Sys()->setCookieLanguage($lang);
            } else {
                $lang = Sys()->getCookieParam('lang', Sys()->getConfig('languages.default_language_code', 'en'));
            }
            if (!defined('LANG')) {
                define('LANG', $lang);
            }
        }

    }

}