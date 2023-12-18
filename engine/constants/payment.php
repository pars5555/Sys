<?php


$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$projectRoot = trim(dirname($documentRoot), '/');
$dataDir = trim(dirname($projectRoot), '/');

if (\system\util\Util::isLinux()){
    $dataDir = '/' . $dataDir;
    $projectRoot = '/' . $projectRoot;
}

define('INTERNAL_DATA_DIR', $projectRoot.'/data');
define('EXTERNAL_DATA_DIR', PROJECT_PARENT_DIR.'/instances_control_center_data');