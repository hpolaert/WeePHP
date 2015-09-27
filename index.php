<?php
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
// Setup
if (!file_exists('core/config/config.conf.php')) {
  header("location: ./install");
}
// --------------------------------------------
/**
 * Index.php
 *
 * Set up core constants and vars required to launch
 * the application and load the libraries
 *
 */
/**
 * @var string $applicationFolder User controllers, models, libraries, utilities and frontend folder
 */
$applicationFolder = 'application';
/**
 * @var string $coreFolder System files and core libraries folder
 */
$coreFolder = 'core';
/**
 * @var string $configFolder Configuration folder (with subfolders for each environment)
 */
$configFolder = 'config';
/**#@+
 * Constants
 */
/**
 * Directory Separator
 */
define('DS', DIRECTORY_SEPARATOR);
/**
 * Server root from current file path
 */
define('SERVER_ROOT', str_replace('\\', '\\\\', realpath(dirname(__FILE__))));
/**
 * Environment configuration (either development or production)
 */
define('CONFIG_ENVIRONMENT', 'development');
/**
 * Server environment configuration (either local or remote)
 */
define('CONFIG_SERVER_ENVIRONMENT', 'local');
/**
 * PHP files extension
 */
define('PHP_EXT', '.php');
/**
 * Template files extension
 */
define('TPL_EXT', '.tpl');
/**
 * Bootstrap core file
 */
define('BOOTSTRAP', 'Wee' . PHP_EXT);
/**
 * @var string $corePath Core system path
 */
$corePath = SERVER_ROOT . DS . $coreFolder;
if (!is_dir($corePath)) {
  exit('Your system core folder path could not be defined, please check if the the folder exists.');
} else {
  /**
   * Core system path
   */
  define('CORE_DIR', $corePath);
}
/**
 * @var string applicationPath            Application path
 */
$applicationPath = SERVER_ROOT . DS . $applicationFolder;
if (!is_dir($applicationPath)) {
  exit('Your application folder path could not be defined, please check if the the folder exists.');
} else {
  /**
   * Application path
   */
  define('APPLICATION_DIR', $applicationPath);
}
/**
 * @var string configPath                Configuration path
 */
$configPath = SERVER_ROOT . DS . $coreFolder . DS . $configFolder;
if (!is_dir($configPath)) {
  exit('Your configuration folder path could not be defined, please check if the the folder exists.');
} else {
  /**
   * Configuration path
   */
  define('CONFIG_DIR', $configPath);
}
/**
 * Error reporting
 */
switch (CONFIG_ENVIRONMENT) {
case 'development' :
  error_reporting(E_ALL);
  break;
case 'production' :
  error_reporting(0);
  break;
default :
  exit('Your environment has not been set correctly.');
}
/**
 * Launch the application !
 */
$bootstrapPath = CORE_DIR . DS . BOOTSTRAP;
if (is_file($bootstrapPath)) {
  require($bootstrapPath);
} else {
  exit('Bootstrap file could not been found.');
}