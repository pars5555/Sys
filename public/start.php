<?php
ini_set('display_errors', true);
define('START_PHP', realpath(__DIR__ . '/start.php'));
if (php_sapi_name() == "cli") {
    $_SERVER['DOCUMENT_ROOT'] = dirname(START_PHP);
    chdir(__DIR__);
    if (!isset($argv[1])) {
        echo "Please provide HOST_NAME (e.g. subdomain.domain.com, localhost)";
        exit;
			}
    if (!isset($argv[2])) {
        echo "Please provide REQUEST_URI (e.g. /_sys_/actions/Test.php)";
        exit;
    }
    if (!array_key_exists(1, $argv) || !array_key_exists(2, $argv)){
        echo 'bye';exit;
    }
    $_SERVER['HTTP_HOST'] = $argv[1];
    $_SERVER['REQUEST_URI'] = $argv[2];
    $parts = parse_url($_SERVER['REQUEST_URI']);
    if (array_key_exists('query', $parts)) {
        parse_str($parts['query'], $query);
        $_GET = $query;
    }
}
require_once ("../engine/system/System.php");
