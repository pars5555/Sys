<?php

namespace system\entities {

    class SysObjectWrapper implements \JsonSerializable {

        private $object;

        function __construct($obj) {
            $this->object = $obj;
        }

        function get($propName, $defaultValue = null) {
            if (isset($this->object->$propName)) {
                return $this->object->$propName;
            }
            return $defaultValue;
        }

        function set($propName, $propValue) {
            $this->object->$propName = $propValue;
        }

        public function jsonSerialize() {
            
        }

    }

}
