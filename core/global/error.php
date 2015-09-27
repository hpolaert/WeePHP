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
 * Global exceptions and error handling, further details below :
 * ALSO DEFINES ALL EXCEPTIONS TYPE (end of document)
 *
 * EXCEPTIONS
 * Methodology :
 * - Each library has its own Exceptions and Error Codes
 * - If libraries are used outside the framework => internal error handling within the class
 * - If libraries are used inside the framework  => global Exceptions handling
 * - Log every exception
 *
 * ERRORS
 * Methodology :
 * - Global error handler
 * - Log errors in production mode (except Fatal Errors), log them in development mode
 *
 * In production : Default error message will be returned (Exceptions/Errors)
 *
 * @access        Private
 * @version       0.1
 */

/**
 * Abstract WeePHP Framework Parent Exception
 *
 * Cannot be thrown
 */
abstract class WeeException extends Exception {
  /**
   * Exception category
   * @var string $_category Custom var to differentiate Functional & Technical errors
   *
   */
  protected $_category;

  /**
   * Constructor
   *
   * @param    string  $category Functional or Technical error (F/T)
   * @param    string  $message  Exception Message
   * @param    integer $code     Error code
   */
  public function __construct($category, $message, $code) {
    if (strlen((string) $code) == 2) {
      $this->_category = $category . '0' . $code;
    } else {
      $this->_category = $category . '00' . $code;
    }
    parent::__construct($message, $code);
  }

  /**
   *  Overrides default toString method
   *
   * @return string
   */
  public function __toString() {
    return get_class($this) . " : '{$this->message}' [{$this->_category}] in {$this->file} ({$this->line})\n" . "{$this->getTraceAsString()}";
  }
}

/* ------------------------------------------------------------
 * EXCEPTIONS HANDLING
 * According to environment settings
 -------------------------------------------------------------*/
function exception_handler($exception) {
  _log('DEBUG', $exception);
  _log('PERF', '*** A fatal error occured - program terminated ***');
  switch (CONFIG_ENVIRONMENT) {
  case 'production' :
    refresh(CLIENT_ERROR_PAGE, TRUE);
    break;
  case 'development' :
  default :
    print("Found " . $exception . "\n");
    print("<br /><br /><strong>Shutting down.</strong>");
    break;
  }
}

set_exception_handler('exception_handler');
/* ------------------------------------------------------------
 * ERRORS HANDLING
 * According to environment settings
 -------------------------------------------------------------*/
function error_handler($errno, $errstr, $errfile, $errline) {
  $error = NULL;
  switch ($errno) {
  case E_USER_ERROR :
    $error = "Fatal error found : $errstr on line $errline in file $errfile";
    break;
  case E_USER_WARNING :
    $error = "Warning found : $errstr on line $errline in file $errfile";
    break;
  case E_USER_NOTICE :
    $error = "Notice found : $errstr in $errfile on line $errline";
    break;
  default :
    $error = "Unknown error found [#$errno] : $errstr in $errfile on line $errline";
    break;
  }
  _log('DEBUG', $error);
  if ($errno == E_USER_ERROR && CONFIG_ENVIRONMENT == 'production') {
    _log('PERF', '*** A fatal error occured - program terminated ***');
    refresh(CLIENT_ERROR_PAGE, TRUE);
  }
  return TRUE;
}

set_error_handler("error_handler");
/* ------------------------------------------------------------
 * FATAL ERRORS HANDLING
 * According to environment settings
 -------------------------------------------------------------*/
function fatal_error_handler() {
  $errfile = 'unknown file';
  $errstr = 'shutdown';
  $errno = E_CORE_ERROR;
  $errline = 0;
  $error = error_get_last();
  if ($error !== NULL) {
    $errno = $error["type"];
    $errfile = $error["file"];
    $errline = $error["line"];
    $errstr = $error["message"];
    $msg = "Fatal error found [#$errno] : $errstr in $errfile on line $errline";
    _log('DEBUG', $msg);
    _log('PERF', '*** A fatal error occured - program terminated ***');
    if (CONFIG_ENVIRONMENT == 'production') {
      refresh(CLIENT_ERROR_PAGE, TRUE);
    }
  }
}

register_shutdown_function('fatal_error_handler');

// ------ DESCRIPTIONS
class DispatchException extends WeeException {
  // 'F000', 'Depreciated - Error - Invalid number of dynamic arguments.'
  // 'F001', 'Depreciated - Error - Var type is not recognized or unknown.'
  // 'F002', 'Depreciated - Error - Route requirements do not match.'
  // 'F003', 'Depreciated - Error - Route dynamic elements types do not match.'
  // 'F004', 'Error - No dynamic or static match'
  // 'F005', 'Depreciated - Invalid route URI segments number'
  // 'F006', 'Error - Dynamic arguments are not correctly attached to the route.'),
  // 'F007', 'Error - Invalid route construction, one or more arguments have not been set correctly.'
  // 'F008', 'Error - Controller file does not exist.'
  // 'F009', 'Error - Method does not exist on the controller.'
  // 'F010', 'Depreciated - Invalid argument.'
  // 'F011', 'Error - Route names must be unique.'
}

class TemplateException extends WeeException {
  // 'F000', 'Error - Template File does not exists.'
  // 'F001', 'Error - One child template block is not properly formatted (before/after).'
  // 'F002', 'Error - Code must be encapsulated in block tags in child template.'
  // 'F003', 'Error - An unrecognized modifier has been applied'
  // 'F004', 'Error - Method not found on called entity.'
  // 'F005', 'Error - An entity could not be found.'
  // 'F006', 'Error - Entities folder could not be found.'
  // 'F007', 'Error - Invalid number of arguments passed to a Widget.'
  // 'F008', 'Error - Widget could not be found.'
  // 'F009', 'Error - Widgets folder could not be found.'
}

class LoaderException extends WeeException {
  // 'F000', 'Error - file for '.$className.' does not exist.'
  // 'F001', 'Error - folder for '.$className.' does not exist.'
  /* PLoader Class */
  // 'F002', 'Error - User helper with system helper name detected.'
  // 'F003', 'Error - An helper could not be loaded.'
  // 'F004', 'Error - Helpers paths have not been correctly setted.'
  // 'F005', 'Error - User models folder could not be found.'
  // 'F006', 'Error - One loaded model could not be found.'
  // 'F007', 'Error - Libraries paths have not been correctly setted.'
  // 'F008', 'Error - User library with system library name detected.'
  // 'F009', 'Error - A library could not be loaded.'
  // 'F010', 'Error - Model is already loaded to super instance / two classes with same alias cannot be loaded.'
}

class PaginateException extends WeeException {
  // 'F000', 'Error - Invalid regex expression passed as parameter.'
}

class MailException extends WeeException {
  // 'F001', 'Error - No recipient has been defined.'
  // 'F002', 'Error - No sent email from has been defined.'
  // 'F003', 'Error - Email could not be sent.'
}

class AuthException extends WeeException {
  // 'F000', 'Error - User already exists.'
  // 'F001', 'Error - Username not defined when registering the user.'
  // 'F002', 'Error - Email must be defined to send the generated password or the activation token.'
  // 'F003', 'Error - Invalid email address.'
  // 'T004', 'Error - Passwords encryption cannot be handled by the current hosting server.'
  // 'T005', 'Error - Activation or password email could not be sent, terminating.'
  // 'T006', 'Error - Incoherent operation, cannot send activation email if user is already defined as activated.'
  // 'T007', 'Error - Token is null or empty.'
  // 'F008', 'Error - User is already activated/desactivated.'
  // 'F009', 'Error - Token could not be recognised.'
  // 'F010', 'Error - Account operation error, user not found.'
  // 'F011', 'Error - Authentication, username or password is empty.'
  // 'F012', 'Error - Authentication, username could not be found.'
  // 'F013', 'Error - Authentication, password is invalid.'
  // 'F014', 'Error - Auth group already exists and could not be created.'
  // 'T015', 'Error - Auth group could not be created, missing mandatory field.'
  // 'F016', 'Error - Auth group does not exist.'
  // 'F017', 'Error - Permission already exists and could not be created.'
  // 'T018', 'Error - Permission could not be created, missing mandatory field.'
  // 'F019', 'Error - Permission does not exist.'
  // 'F020', 'Error - Old password does not match.'
  // 'F021', 'Error - Update password token does not match'
  // 'F022', 'Error - User account or group is blocked'
}