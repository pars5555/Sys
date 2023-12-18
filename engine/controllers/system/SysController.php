<?php

namespace controllers\system {

    abstract class SysController {

        private $involvedControllers = [];
        private $parentController = null;
        public static $exception;
        private static $eventListeners = [];
        public static $ACCESS_METHODS_GROUP = 'group';
        public static $ACCESS_METHODS_TOKEN = 'token';
        public static $ACCESS_METHODS_OAUTH = 'oauth';

        function __construct() {
            $this->initLogger();
            foreach (self::$eventListeners as $el) {
                $el->onEvent(\system\listeners\iEventListener::INIT_CONTROLLER);
            }
            if (Sys()->isDevelopmentMode()) {
                set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext = []) {
                    $this->setException(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
                });
            }
        }

        public static function subscribeEvent(\system\listeners\iEventListener $eventListener) {
            self::$eventListeners[] = $eventListener;
        }

        public abstract function init();

        public abstract function draw();

        public function getException() {
            return self::$exception;
        }

        public function setException($exception) {
            $code = intval($exception->getCode());
            if ($code <= 0) {
                $r = new \ReflectionClass(get_class($exception));
                $exception = $r->newInstanceArgs([$exception->getMessage(), 418]);
            }
            self::$exception = $exception;
        }

        public function noAccess() {
            \system\SysExceptions::noAccessController($this);
        }

        public function noAccessAjax() {
            \system\SysExceptions::noAccessController($this);
        }

        public function getControllerName() {
            return get_class($this);
        }

        public function getAccessGroups() {
            return [];
        }

        public function getAccessMethod() {
            return self::$ACCESS_METHODS_GROUP;
        }

        public function getAccessToken() {
            return null;
        }
        
        public function getAccessTokenRequestVariableName() {
            return 'sys_access_token';
        }

        public function getInvolvedControllersClasses() {
            return [];
        }

        public function setParentController($controller) {
            $this->parentController = $controller;
        }

        public function getParentController() {
            return $this->parentController;
        }

        public function setInvolvedControllers($controllers) {
            $this->involvedControllers = $controllers;
        }

        public function addInvolvedControllers($controllers) {
            if (is_array($controllers)) {
                $this->involvedControllers = array_merge($this->involvedControllers, $controllers);
            } else {
                $this->involvedControllers[] = $controllers;
            }
        }

        public function getInvolvedControllers() {
            return $this->involvedControllers;
        }

        private function initLogger() {
            \system\services\Logger::$log_level = 'debug';
            
            $log = $this->log();
            if (!empty($log)) {
                $reflect = new \ReflectionClass($this);
                $name = str_replace(['/', '\\'], '.', $reflect->getName());
                \system\services\Logger::$log_dir = LOG_DIR . '/' . $name . '.log';
                
            }
        }
        
        /* private function initLogger() {
            $log = $this->log();
            if (!empty($log)) {
                $reflect = new \ReflectionClass($this);
                $name = str_replace(['/', '\\'], '.', $reflect->getName());
                $logFilePath = LOG_DIR . '/' . $name . '.log';
                \Logger::configure(['file' => $logFilePath, 'name' => $name, 'level' => Sys()->getConfig('log_level')], new \system\util\SysLoggerConfigurator());
                $this->logger = \Logger::getLogger($name);
                if ($log['dump_request'] == true) {
                    $args = print_r($_REQUEST, true);
                    $args = preg_replace('/\s+/', '', $args);
                    $this->logger->info(Sys()->getHostIpAddress() . ": Request dump: " . $args);
                }
                if ($log['dump_input'] == true) {
                    $this->logger->info(Sys()->getHostIpAddress() . ": Input dump: " . print_r(Sys()->request()->input(), true));
                }
                Sys()->setActiveControllerLogger($this->logger);
            }
        }*/

        /**
         * 
         * @return \Logger
         */
        protected function getLogger() {
            if (!isset($this->logger)) {
                \system\SysExceptions::loggerIsNotEnabled();
            }
            return $this->logger;
        }

        protected function log() {
            return Sys()->getConfig('log');
        }

    }

    SysController::$exception = new \Exception("", 200);
}