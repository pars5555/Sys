<?php

namespace system\entities {

    class SysEntity {

        function __construct($fieldValuesArray = []) {
            foreach ($fieldValuesArray as $fieldName => $value) {
                $this->$fieldName = $value;
            }
        }

        public function __call($method_name, $arguments) {
            if (\system\util\TextHelper::startsWith($method_name, 'get')) {                
                $propertyName = substr($method_name, 3);
                $propertyName = strtolower(preg_replace('/\B([A-Z])/', '_$1', $propertyName));
                if (isset($this->$propertyName)){
                    return $this->$propertyName;
                }
                return null;
            }
            if (\system\util\TextHelper::startsWith($method_name, 'set')) {
                $propertyName = substr($method_name, 3);
                $propertyName = strtolower(preg_replace('/\B([A-Z])/', '_$1', $propertyName));
                $this->$propertyName = $arguments[0];
                return;
            }
            \system\SysExceptions::methodNotFound($method_name);
        }
        
        public function toJSON($includePrivateMemebers = false) {
            //@TODO implement private members case in json
            return json_encode($this);
        }
        
//        public function jsonSerialize(): mixed {
//            var_dump($this);exit;
//            return json_encode($this);
//        }

//$utc_date = DateTime::createFromFormat(
//    'Y-m-d G:i',
//    '2011-04-27 02:45',
//    new DateTimeZone('UTC')
//);
//
//$acst_date = clone $utc_date; // we don't want PHP's default pass object by reference here
//$acst_date->setTimeZone(new DateTimeZone('Australia/Yancowinna'));
    }

}
