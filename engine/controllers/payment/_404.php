<?php

namespace controllers\payment {

    class _404 extends \controllers\system\HtmlController {

        public function init() {
        }

        public function getTemplatePath() {
            return "main/404.tpl";
        }
    }

}
