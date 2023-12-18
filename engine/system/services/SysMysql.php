<?php

namespace system\services {

    class SysMysql extends Mysql {

        private $tableName;

        function __construct($tableName, $entity = null) {
            parent::__construct();
            $this->tableName = $tableName;
            $this->entity= $entity;
        }

        public function getTableName() {
            return $this->tableName;
        }
        
        public function getNewEntity() {
            if (isset($this->entity)){
                return $this->entity; 
            }
            return parent::getNewEntity();
        }

    }

}
