<?php
namespace system\cli {


    abstract class BaseBackgroundController extends \controllers\system\JsonController {

        public function init() {
            $__sys_data_row_id = intval(Sys()->request('__sys_data_row_id', 0));
            $processData = null;
            if ($__sys_data_row_id > 0) {
                $processData = service('BackgroundProcessesData')->getPrcessData($__sys_data_row_id);
            }
            $this->doAction($processData);
        }

        abstract protected function doAction($data);
    }

}


    