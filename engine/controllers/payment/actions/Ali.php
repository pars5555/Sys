<?php

namespace controllers\payment\actions {

    class Ali extends \controllers\system\JsonController {

        public function init() {
            $name = 'ali3';
            service('Test')->startTransaction();
            $rrr= service('Test')->insertArray(['name'=>$name, 'age'=>24]);
            
            
            
            
            
            $rrr= service('Test')->updateAdvance(["name='$name'"], ['age'=>52]);
            //service('Test')->commitTransaction();
            $this->addParam('aaa', 1);
            $this->addParam('bbbb', 2);
            $this->addParams(['ccc'=> 3, 'ddd'=>4]);
        }
    }

}
