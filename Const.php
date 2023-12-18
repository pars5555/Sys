<?php
$usedIp = false;
if (isset($_SERVER["HTTP_HOST"])){
    $usedIp = preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(:\d{1,5})|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $_SERVER["HTTP_HOST"]) === 1;
}else{
    echo "Missing HTTP_HOST param in the server environment!";
    exit;
}
define('HOST', $_SERVER["HTTP_HOST"]);
if (strpos(HOST, '.') === FALSE) {
    define('SITE_PATH', HOST);
    define('DOMAIN', HOST);
    define('BASE_SITE_PATH', DOMAIN);
    define('SUB_DOMAIN', '');
} else {
    define('SITE_PATH', '//' . HOST);
    if (!$usedIp){
        $__host_names = explode(".", HOST);
        $bottom_host_name = $__host_names [count($__host_names) - 2] . "." . $__host_names [count($__host_names) - 1];
        define('DOMAIN', $bottom_host_name);
        define('BASE_SITE_PATH', '//' . DOMAIN);
        define('SUB_DOMAIN', implode('.', array_slice($__host_names, 0, -2)));
        $subDomain = SUB_DOMAIN;
    }else{
        define('DOMAIN', HOST);
        define('BASE_SITE_PATH', '//' . DOMAIN);
        define('SUB_DOMAIN', 'instance');
        $subDomain = SUB_DOMAIN;
    }
}
if (empty($subDomain) || in_array($subDomain, ['www', 'dev'])) {
    define('SUB_DOMAIN_DIR_FILE_NAME', 'main');
} else {
    define('SUB_DOMAIN_DIR_FILE_NAME', $subDomain);
}
define('PROJECT_PARENT_DIR', dirname(dirname(__FILE__)));
define('PROJECT_ROOT', dirname(__FILE__));
define('ENGINE_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'engine');
define('DOMAIN_CONTROLLERS_DIR', ENGINE_DIR . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . SUB_DOMAIN_DIR_FILE_NAME);

define('LOG_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'log');
define('SERVICES_DIR_NAME', 'services');
define('SERVICES_DIR', ENGINE_DIR . DIRECTORY_SEPARATOR . SERVICES_DIR_NAME);
define('SYSTEM_DIR_NAME', 'system');
define('SYSTEM_DIR', ENGINE_DIR . DIRECTORY_SEPARATOR . SYSTEM_DIR_NAME);
define('BUILDERS_DIR', SYSTEM_DIR . DIRECTORY_SEPARATOR . 'builders');
define('SECURITY_DIR', SYSTEM_DIR . DIRECTORY_SEPARATOR . 'security');
define('SECURITY_USERS_DIR', SECURITY_DIR . DIRECTORY_SEPARATOR . 'users');
define('VIEWS_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'views');
define('PUBLIC_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'public');
define('PUBLIC_OUT_DIR', PUBLIC_DIR . DIRECTORY_SEPARATOR . 'out');
define('CSS_DIR', PUBLIC_DIR . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . SUB_DOMAIN_DIR_FILE_NAME);
define('IMG_DIR', PUBLIC_DIR . DIRECTORY_SEPARATOR . 'img');
define('IMG_UPLOAD_DIR', IMG_DIR . DIRECTORY_SEPARATOR . 'uploads');
define('JS_DIR', PUBLIC_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . SUB_DOMAIN_DIR_FILE_NAME);
define('JS_COMMON_DIR_NAME', 'common');
define('JS_COMMON_DIR', PUBLIC_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . JS_COMMON_DIR_NAME);

define('CONFIG_FILE', ENGINE_DIR . DIRECTORY_SEPARATOR . 'config/' . SUB_DOMAIN_DIR_FILE_NAME . '.json');
define('MAIN_ROUTING_FILE', ENGINE_DIR . DIRECTORY_SEPARATOR . 'routing/main.json');
define('ROUTING_FILE', ENGINE_DIR . DIRECTORY_SEPARATOR . 'routing/' . SUB_DOMAIN_DIR_FILE_NAME . '.json');
define('CONSTANTS_FILE', ENGINE_DIR . DIRECTORY_SEPARATOR . 'constants/' . SUB_DOMAIN_DIR_FILE_NAME . '.php');
define('GLOBAL_FILE', ENGINE_DIR . DIRECTORY_SEPARATOR . 'globals/' . SUB_DOMAIN_DIR_FILE_NAME . '.php');
define('DYNAMIC_ROUTE_PREFIX', '_sys_');
define('DEVTOOLS_ROUTE_PREFIX', '_sysdevtools_');
define('PROD_JS_INCLUDE_TEMPLATE_DIR_NAME', '_sys_');
define('PROD_JS_INCLUDE_TEMPLATE_FILE_NAME', 'js_include');
define('ROUTING_REGEX_START_CHAR', '{');
define('ROUTING_REGEX_END_CHAR', '}');

define('ALL_JS_FILE', SUB_DOMAIN_DIR_FILE_NAME . '/sys.js');
define('ALL_CSS_FILE', SUB_DOMAIN_DIR_FILE_NAME . '/sys.css');
define('PUBLIC_OUT_JS_FILE', PUBLIC_OUT_DIR . DIRECTORY_SEPARATOR . ALL_JS_FILE);
define('PUBLIC_OUT_CSS_FILE', PUBLIC_OUT_DIR . DIRECTORY_SEPARATOR . ALL_CSS_FILE);