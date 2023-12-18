<?php

namespace controllers\payment\dddd {

    class Ali extends \controllers\system\HtmlController {

        public function init() {
            $this->addParam('included_in_index', $this->getTemplateFullPath());

            $allll = service('Test')->selectAll();
            $rrr= service('Test')->updateAdvance(['name="aaa"'], ['age'=>12]);
            
            var_dump($rrr);exit;
            
        }

        public function getTemplatePath() {
            return "main/ali.tpl";
        }
    }

}
