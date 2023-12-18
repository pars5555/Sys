<?php

namespace services {


    use system\services\Mysql;

    class Settings extends Mysql {

        private $settingsCache;
        private $settingsMapCache = [];

        function __construct($configParamName = 'mysql') {
            parent::__construct($configParamName);
            $this->settingsCache = [];
            $this->getAllSettings();
        }

        public function getTableName() {
            return "settings";
        }

        public function getNewEntity() {
            return new \entities\Settings();
        }

        public function setValue($var, $value) {
            $row = $this->selectOneByField('var', $var);
            if (empty($row)) {
                $this->insertArray(['var' => $var, 'value' => $value]);
            } else {
                $this->updateAdvance(['var', '=', "'$var'"], ['value' => $value]);
            }
        }
        public function getJsonValueProperty($var, $property) {
            $obj = json_decode($this->getValue($var));
            if (empty($obj)) {
                return null;
            }
            if (!isset($obj->$property)) {
                return null;
            }
            return $obj->$property;
        }

        public function getValue($var, $trim = false, $fromCache = true) {
            $var = strtolower($var);
            if ($fromCache) {
                $ret = $this->settingsMapCache[$var];
            } else {
                $ret = $this->selectOneByField('var', $var, [],'value');
            }
            if ($trim) {
                return trim($ret);
            }
            return $ret;
        }

        public function getAllSettings($forceUpdate = false) {
            if (empty($this->settingsCache) || $forceUpdate) {
                $this->settingsCache = $this->selectAll();
                foreach ($this->settingsCache as $row) {
                    $this->settingsMapCache[$row->getVar()] = $row->getValue();
                }
            }
            return $this->settingsCache;
        }

    }

}