<?php
//       /_sysdevtools_/tools/ClearTemplateCache

namespace controllers\system\tools {

    use controllers\system\JsonController;

    class ClearTemplateCache extends JsonController {

        public function init() {
            ini_set('max_execution_time', 120);
            $files = glob(VIEWS_DIR . '/cache/*');
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

    }

}