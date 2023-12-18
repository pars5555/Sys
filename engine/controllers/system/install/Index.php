<?php

namespace controllers\system\install {

    class Index extends \controllers\system\HtmlController {

        public function init() {
            
        }

        public function getTemplatePath() {
            return "system/install/sys_install.tpl";
        }

    }

}