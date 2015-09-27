<?php
if (!defined('SERVER_ROOT')) {
  exit('Direct access to this file is not allowed.');
}
/**
 * WeePHP Framework
 *
 * Light Framework, quick development !
 *
 * @package       WeePHP
 * @author        Hugues Polaert <hugues.polaert@gmail.com>
 * @link          http://www.weephp.net
 * @version       0.1
 */
// --------------------------------------------
/**
 * WeePHP
 *
 * Bootstrap - Load core libraries and global error handling
 */
/**#@+
 * Constants
 */
/**
 * Framework identity
 */
define('FRAMEWORK_NAME', 'WeePHP Framework');
/**
 * Version
 */
define('WEEPHP_VERSION', '0.1');
/**
 * Year
 */
define('DEVELOPMENT_YEAR', '2014-2015');
// ----------------------- CONFIG FILES
// Load config files according to environment settings
loadConfigFile('dirs');
loadConfigFile('db');
loadConfigFile('config');
loadConfigFile('mails');
/**
 * Config Loader
 * Utility function to load config files according to environment settings
 *
 * @param    string $configFile Config File to be loaded
 *
 * @return    void
 */
function loadConfigFile($configFile) {
  $environmentPath = CONFIG_DIR . DS . strtolower(CONFIG_ENVIRONMENT) . DS;
  $environmentPath .= strtolower(CONFIG_SERVER_ENVIRONMENT) . '_' . strtolower($configFile) . '.conf' . PHP_EXT;
  $defaultPath = CONFIG_DIR . DS . strtolower($configFile) . '.conf' . PHP_EXT;
  if (is_file($environmentPath) && filesize($environmentPath) != 0) {
    require($environmentPath);
  } else {
    if (is_file($defaultPath) && filesize($defaultPath) != 0) {
      require($defaultPath);
    } else {
      exit('One or more config file(s) could not be loaded, check core config folder path.');
    }
  }
}

/**
 * Global Methods Loader
 *
 * @param    string $file File to be loaded
 *
 * @return    void
 */
function loadGlobalFunctions($file) {
  $path = DIR_S_GLOBAL . $file . PHP_EXT;
  if (is_file($path) && filesize($path) != 0) {
    require($path);
  } else {
    exit('One or more global method(s) could not be loaded, check core global folder path.');
  }
}

// ----------------------- GLOBAL METHODS -------
loadGlobalFunctions('logger');
loadGlobalFunctions('misc');
loadGlobalFunctions('error');
loadGlobalFunctions('loader');
loadGlobalFunctions('langs');
// ----------------------------------------------
// From this point forward, custom error handling
// ----------------------------------------------
/* ------------------------------------------------------------
 * MONITORING APPLICATION PERFORMANCES
 * Initialization
 -------------------------------------------------------------*/
$timer = &load('WeeReporting', 'timer', DIR_S_LIB);
$timer->initialize();
/* ------------------------------------------------------------
 * REDBEAN ORM
 * Initialization
 -------------------------------------------------------------*/
$timer->startTimer('ORM', 'DATABASE ORM Initialization');
require(DIR_S_LIB_EXTERNAL . 'Rb.php');
R::setup('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USERNAME, DB_PASSWORD);
$timer->endTimer('ORM');
/* ------------------------------------------------------------
 * CONFIG
 * Global seetings array
 -------------------------------------------------------------*/
$timer->startTimer('CONFIG', 'CONFIG Initialization');
$config = &load('WeeConfig', 'config', DIR_S_LIB_USER);
$config->setConfigTable(DB_PREFIX . DB_CONFIG_TABLE_NAME);
$timer->endTimer('CONFIG');
/* ------------------------------------------------------------
 * SESSION // ENCRYPTER
 * Sessions Handler and encryption
 -------------------------------------------------------------*/
$timer->startTimer('SESSI', 'SESSION Initialization');
$session = &load('WeeSession', 'session', DIR_S_LIB, array(
  DB_PREFIX . DB_SESSIONS_TABLE_NAME
));
$timer->endTimer('SESSI');
/* ------------------------------------------------------------
 * ROUTER
 * URI request parser
 -------------------------------------------------------------*/
$timer->startTimer('RPROC', 'RENDERING PROCESS Initialization (dispatch/parse/languages/render)');
$timer->startTimer('DSPTCH', 'DISPATCHER Initialization - Fetching Controller/Method');
$router = &load('WeeDispatcher', 'router', DIR_S_LIB);
$router->setAppUrl(WEB_ROOT);
$router->setControllerDir(DIR_A_CONTROLLERS);
// Routes definition
require(DIR_A_ROUTES . 'routes.conf.php');
parse_str($_SERVER['QUERY_STRING'], $_GET);
$router->dispatch($_SERVER['QUERY_STRING']);
// Retrieve configuration, array :
// 0 - Route Match Name || 1 - Controller || 2 - Method
// 3 - Optional arguments || 4 - Lang
$routerConfig = $router->getFinalResponse();
$config->set('routerCfg', $routerConfig);
$routerLang = $router->getCurrentRouteLang();
$timer->endTimer('DSPTCH');
/* ------------------------------------------------------------
 * LANGUAGES HANDLER
 * Retrieve or define the lang to be loaded
 -------------------------------------------------------------*/
// Retrieve languages from dir as an array => ('en', 'fr', etc..)
$timer->startTimer('LANG', 'LANGUAGES & TRANSLATIONS Initialization');
if (!isset($_SESSION['lang'])) {
  $_SESSION['lang'] = 'default';
}
$availableLanguages = listLanguages(DIR_A_LANGS);
$defaultLangFilePath = DIR_A_LANGS . DEFAULT_LANGUAGE . '.php';
$lang = retrieveLang($routerLang, $availableLanguages, $defaultLangFilePath, DIR_A_LANGS);
$config->set('lang', $_SESSION['lang']);
$config->set('currLangArray', $lang);
$translator = &load('WeeTranslator', 'translator', DIR_S_LIB);
$translator->setLang($lang);
$timer->endTimer('LANG');
/* ------------------------------------------------------------
 * TEMPLATES PARSER
 * Load Template parser class
 -------------------------------------------------------------*/
$timer->startTimer('TPL', 'TEMPLATE PARSER Initialization');
$view = &load('WeeTemplate', 'view', DIR_S_LIB);
$timer->endTimer('TPL');
/* ------------------------------------------------------------
 * LOADER
 * Provides all loaded libraries to child controllers
 -------------------------------------------------------------*/
$timer->startTimer('LDR', 'LOADER Initialization');
$load = &load('WeeLoader', 'load', DIR_S_LIB);
$load->setHelpersPaths(array(DIR_S_HELPERS, DIR_A_HELPERS));
$load->setLibrariesPaths(array(DIR_S_LIB_USER, DIR_A_LIB));
$load->setModelsPath(DIR_A_MODELS);
$timer->endTimer('LDR');
/* ------------------------------------------------------------
 * FRAMEWORK HOOKS
 * Register hooks within the application
 -------------------------------------------------------------*/
$timer->startTimer('HOO', 'HOOKS Initialization');
$hooks = &load('WeeHooks', 'hooks', DIR_S_LIB);
$timer->endTimer('HOO');
/* ------------------------------------------------------------
 * CORE MODEL
 * Provides all loaded libraries to child controllers
 -------------------------------------------------------------*/
$timer->startTimer('SMO', 'SUPER MODEL Initialization');
require(DIR_S_LIB . 'WeeModel.php');
$timer->endTimer('SMO');
/* ------------------------------------------------------------
 * CORE CONTROLLER
 * Provides all loaded libraries to child controllers
 -------------------------------------------------------------*/
$timer->startTimer('SCO', 'SUPER CONTROLLER Initialization');
require(DIR_S_LIB . 'WeeController.php');
// Global method to return parent controller instance
function Wee() {
  static $Wee;
  // If already exists returns it, else get super controller instance
  isset($Wee) || $Wee = WeeController::get_instance();
  return $Wee;
}

$timer->endTimer('SCO');
/* ------------------------------------------------------------
 * WARM UP
 * Preload controller, set method and args
 -------------------------------------------------------------*/
$timer->startTimer('C&M', 'FETCHING Controller & Method');
$hooks->loadPreDispatchingHook();
$router->instantiate();
$hooks->loadPostDispatchingHook();
/*-------------------------------------------------------------
 /* #############################################################
 * /////////////////////////////////////////////////////////////
 * -------------------------------------------------------------
 * AUXILIARY LIBS
 -------------------------------------------------------------*/
$timer->startTimer('DBAL', 'AUXILIARY LIBRARIES Initialization');
Wee()->load->library('WeeDBGenericAccess', 'db', NULL);
Wee()->load->library('WeeFilter', 'filter', NULL);
Wee()->load->library('WeePaginate', 'paginate', NULL);
Wee()->load->library('WeeFile', 'filehandler', NULL);
Wee()->load->library('WeeCookie', 'cookie', NULL);
Wee()->load->library('WeeImg', 'image', NULL);
Wee()->load->library('WeeMail', 'wmail', NULL);
Wee()->load->library('WeeAuth', 'auth', NULL);
Wee()->load->library('WeeGal', 'gallery', NULL);
$timer->endTimer('DBAL');
/* -------------------------------------------------------------
 * /////////////////////////////////////////////////////////////
 * #############################################################
/* ------------------------------------------------------------
 * IGNITION !
 * Fetch controller and method, render, close DB
 -------------------------------------------------------------*/
$hooks->loadPreApplicationHook();
$router->run();
$hooks->loadPostApplicationHook();
$timer->endTimer('C&M');
$timer->endTimer('RPROC');
R::close();
$timer->stop();