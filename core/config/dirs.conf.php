<?php
// WeePHP Framework directory structure
// Application
define('DIR_A_CACHE', APPLICATION_DIR . DS . 'cache');
define('DIR_A_CACHE_FRONT', DIR_A_CACHE . DS . 'front');
define('DIR_A_CACHE_MISC', DIR_A_CACHE . DS . 'misc');
define('DIR_A_CONTROLLERS', APPLICATION_DIR . DS . 'controllers' . DS);
define('DIR_A_ENTITIES', APPLICATION_DIR . DS . 'entities' . DS);
define('DIR_A_FRONT', APPLICATION_DIR . DS . 'front');
define('DIR_A_FRONT_RES', DIR_A_FRONT . DS . 'res');
define('DIR_A_HELPERS', APPLICATION_DIR . DS . 'helpers' . DS);
define('DIR_A_LANGS', APPLICATION_DIR . DS . 'langs' . DS);
define('DIR_A_LIB', APPLICATION_DIR . DS . 'lib' . DS);
define('DIR_A_MODELS', APPLICATION_DIR . DS . 'models' . DS);
define('DIR_A_ROUTES', APPLICATION_DIR . DS . 'routes' . DS);
// System
define('DIR_S_CONFIG', CONFIG_DIR);
define('DIR_S_CONFIG_DEVELOPMENT', DIR_S_CONFIG . DS . 'development');
define('DIR_S_CONFIG_PRODUCTION', DIR_S_CONFIG . DS . 'production');
define('DIR_S_CONSOLE', CORE_DIR . DS . 'console' . DS);
define('DIR_S_GLOBAL', CORE_DIR . DS . 'global' . DS);
define('DIR_S_HELPERS', CORE_DIR . DS . 'helpers' . DS);
define('DIR_S_RES', CORE_DIR . DS . 'upload' . DS);
define('DIR_S_LIB', CORE_DIR . DS . 'lib' . DS . 'core' . DS);
define('DIR_S_LIB_USER', CORE_DIR . DS . 'lib' . DS . 'libraries' . DS);
define('DIR_S_LIB_EXTERNAL', CORE_DIR . DS . 'lib' . DS . 'external' . DS);
// Logs
define('DIR_LOGS', SERVER_ROOT . DS . 'logs' . DS);