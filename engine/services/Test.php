<?php

namespace services {


    use system\services\Mysql;

    class Test extends Mysql {

        public function getTableName() {
            return "test";
        }

        public function getNewEntity() {
            return new \entities\Test();
        }
    }

}