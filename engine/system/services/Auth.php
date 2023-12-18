<?php

namespace system\services {

    class Auth {

        private $userCache;

        public function logout() {
            $user = $this->getAuthUser();
            if (isset($user)) {
                $user->logout();
            }
        }

        public function isGuest() {
            $authUser = $this->getAuthUser();
            return empty($authUser) || !($authUser->getId() > 0);
        }

        public function getAuthUser() {
            if ($this->userCache) {
                return $this->userCache;
            }
            $loginConf = Sys()->getConfig("login");
            $typeMd5 = "";
            if ($loginConf['type'] === 'cookie') {
                $typeMd5 = \system\Request::getInstance()->cookie($loginConf['params']['user_type']);
                $userHash = \system\Request::getInstance()->cookie($loginConf['params']['user_hash']);
                $userId = \system\Request::getInstance()->cookie($loginConf['params']['user_id']);
            } else {
                $typeMd5 = \system\Request::getInstance()->session($loginConf['params']['user_type']);
                $userHash = \system\Request::getInstance()->session($loginConf['params']['user_hash']);
                $userId = \system\Request::getInstance()->session($loginConf['params']['user_id']);
            }
            if ($typeMd5) {
                $user = $this->findUserObjectByTypeMd5($typeMd5);
                if (empty($user)) {
                    return null;
                }
                $user->setHash($userHash);
                $user->setId($userId);
                if (!($user->validate($userId))) {
                    $user->logout();
                    return null;
                }
                $this->userCache = $user;
                return $user;
            }
            return null;
        }

        private function findUserObjectByTypeMd5($typeMd5) {
            $userTypeFiles = \system\util\Util::getDirectoryFiles(SECURITY_USERS_DIR, 'php', true);
            foreach ($userTypeFiles as $fileName) {
                $classFullName = trim('system\\security\\users\\' . $fileName, '.php');
                if ($typeMd5 === md5($classFullName)) {
                    return new $classFullName();
                }
            }
            return false;
        }

    }

}
