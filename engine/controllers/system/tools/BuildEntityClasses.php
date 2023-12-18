<?php
//       /_sysdevtools_/tools/BuildEntityClasses
namespace controllers\system\tools {

    use controllers\system\JsonController;

    class BuildEntityClasses extends JsonController {

        public function init() {
            $files = \system\util\Util::getDirectoryFiles(SERVICES_DIR, 'php', true);
            $data = [];
            foreach ($files as $serviceName) {
                $serviceName = str_replace(['/', '.php'], ['\\', ''], $serviceName);
                if (service($serviceName) instanceof \system\services\Mysql) {
                    $data[$serviceName] = service($serviceName)->buildEntityClass();
                }
            }
            $this->addParams($data);
        }
    }

}