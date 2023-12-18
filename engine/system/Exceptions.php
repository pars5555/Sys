<?php

namespace system {

    class SysExceptions {

        public static function fileNotFound($filePath) {
            header('HTTP/1.0 404 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('File not found: ' . $filePath, 10040);
            }
        }

        public static function controllerNotFound($classPath) {
            header('HTTP/1.0 500 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('Controller not found: ' . $classPath, 10041);
            }
        }

        public static function routeNotFound($route) {
            header('HTTP/1.0 404 NotFound');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('No route found with: ' . $route, 10020);
            }
        }

        public static function methodNotFound($method) {
            header('HTTP/1.0 500 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('No method found: ' . $method, 10060);
            }
        }

        public static function notImplemented() {
            header('HTTP/1.0 501 Not Implemented');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('Not Implemented', 10070);
            }
        }

        public static function defaultRouteNotFound() {
            header('HTTP/1.0 404 NotFound');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('No default route found. Please <sysdefault> route in routings.js file', 10020);
            }
        }

        public static function noMysqlConfig() {
            header('HTTP/1.0 500 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('No mysql config found in project config file. Please add <mysql> settings in config file. ("mysql": {"host": "127.0.0.1","user": "root","pass": "","name": "instmanager"})', 10020);
            }
        }

        public static function noRestfullConfig() {
            header('HTTP/1.0 500 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('No restfull config found in project config file. Please add <restfull> settings in config file. ("restfull": {"andpoint_url": "http://api.com/v2","client_id": "root","access_key": "",})', 10021);
            }
        }

        public static function noAccessController($controller) {
            header('HTTP/1.0 401 Unauthorized');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('No Access to controller (' . $controller->getControllerName() . '). User should has one of these group(s) [' . implode(', ', $controller->getAccessGroups()) . ']', 403);
            }else{
                echo 'No Access';exit;
            }
        }

        public static function unknownError() {
            header('HTTP/1.0 500 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('Unknown Error', 10100);
            }
        }

        public static function controllerAttributeNotFound($route) {
            header('HTTP/1.0 500 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('No <controller> attibute found in the route: ' . $route, 10021);
            }
        }

        public static function loggerIsNotEnabled() {
            header('HTTP/1.0 500 Forbidden');
            if (Sys()->isDevelopmentMode()) {
                throw new \Exception('Logger is not enabled for this request. Please enable logger by overriding <log()> method in your request.', 10021);
            }
        }

    }

}