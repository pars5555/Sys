<?php

namespace system\services {

    class Security {

        public function validate($controller) {
            switch ($controller->getAccessMethod()) {
                case \controllers\system\SysController::$ACCESS_METHODS_GROUP:
                    return $this->validateGroupAccess($controller);
                    break;

                case \controllers\system\SysController::$ACCESS_METHODS_TOKEN:
                    return $this->validateTokenAccess($controller);
                    break;

                case \controllers\system\SysController::$ACCESS_METHODS_OAUTH:
                    return $this->validateOauthAccess($controller);
                    break;
                default:
                    return false;
            }
        }

        private function validateGroupAccess($controller) {
            $controllerAccessGroups = $controller->getAccessGroups();
            if (empty($controllerAccessGroups)) {
                return true;
            }
            $authUser = sysservice('Auth')->getAuthUser();
            if (!$authUser) {
                return false;
            }
            if (!$authUser->validate()) {
                return false;
            }
            $userGroups = $authUser->getGroups();
            $intersects = array_intersect($userGroups, $controllerAccessGroups);
            $validated = !empty($intersects);
            if (!$validated) {
                return false;
            }
            return true;
        }

        
        private function validateTokenAccess($controller) {
            $token = trim(Sys()->request($controller->getAccessTokenRequestVariableName(), ''));
            return $controller->getAccessToken() === $token;
        }

        private function validateOauthAccess($controller) {
            \system\SysExceptions::notImplemented();
        }

    }

}
