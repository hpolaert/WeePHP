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
 * Global logging methods
 */
/**
 * Logs dispatcher according to the type of message to be written
 *
 * @param    string $type    Type of log to written
 * @param    string $message Message to be written
 *
 * @return void
 */
function _log($type, $message) {
  switch ($type) {
  case 'PERF' :
  case 'STATS' :
    if (CONFIG_ENVIRONMENT == 'development' || LOG_DEVELOPMENT_STATS) {
      _fileWriter('WeePerfs', $message, $type);
    }
    break;
  case 'INFO' :
  case 'DEBUG' :
    _fileWriter('WeeDebug', $message, $type);
    break;
  default :
    _fileWriter('WeeMisc', $message, $type);
    break;
  }
}

/**
 * Write into a log file (WeePerfs/WeeDebug/WeeMisc)
 *
 * @param    string $logName Target file name
 * @param    string $message Message to be written
 * @param    string $type    Type of log to written
 *
 * @return void
 */
function _fileWriter($logName, $message, $type) {
  $today = date("Ymd");
  // Makes one different file each day
  $logLocation = DIR_LOGS . $logName . '_' . $today . '.log';
  $file = fopen($logLocation, 'a');
  // Format : [2014/05/05 20:01:20] - PERF - $message
  $str = '[' . date('Y/m/d H:i:s', time()) . '] - ' . $type . ' - ' . $message;
  fwrite($file, $str . "\n");
}
