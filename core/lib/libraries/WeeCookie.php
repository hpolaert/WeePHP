<?php
/**
 * WeePHP Framework
 *
 * Light Framework, quick development !
 *
 * @package       WeePHP\UserLibraries
 * @author        Hugues Polaert <hugues.polaert@gmail.com>
 * @link          http://www.weephp.net
 * @version       0.1
 */
// --------------------------------------------
if (!defined('SERVER_ROOT')) {
  exit('Direct access to this file is not allowed.');
}
/**
 * WeeCookie
 *
 * Write and retrieve cookies !
 *
 * @access        Private
 * @version       0.1
 */
class WeeCookie {
  /**
   * Internal error handler
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeeCookie
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside PXeli Framework
    $this->setInternalErrorHandler($internalErrorHandler);
  }

  /**
   * Create a cookie
   *
   * @param   string $name   Var name
   * @param   string $value  Associated value
   * @param   string $expire Expiration time (30M = 30 minutes, 30S = 30 secondes, 30D = 30 days)
   * @param   string $path   Path (by default all)
   * @param   string $domain Domain availability (by default all)
   *
   * @return  boolean
   */
  public function set($name, $value, $expire, $path = '/', $domain = NULL) {
    $expire = $this->getExpirationDate($expire);
    if ($domain !== NULL && $domain !== '') {
      return setcookie($name, $value, $expire, $path, $domain);
    } else {
      return setcookie($name, $value, $expire, $path);
    }
  }

  /**
   * Calculate the expiration time of a cookie
   *
   * @param   string $expire Expiration time
   *
   * @return  integer
   */
  private function getExpirationDate($expire) {
    switch (substr($expire, -1)) {
    case 'S' :
    case 's' :
      $expire = time() . substr($expire, 0, strlen($expire) - 1);
      break;
    case 'M' :
    case 'm' :
      $expire = time() . (substr($expire, 0, strlen($expire) - 1) * 60);
      break;
    case 'D' :
    case 'd' :
      $expire = time() . (substr($expire, 0, strlen($expire) - 1) * 60 * 24);
      break;
      // default = seconds
    default :
      $expire = time() . $expire;
    }
    return $expire;
  }

  /**
   * Fetch a cookie
   *
   * @param   string $name Cookie name
   *
   * @return  string
   */
  public function fetch($name) {
    if (!isset($_COOKIE[$name])) {
      return;
    } else {
      return $_COOKIE[$name];
    }
  }

  /**
   * Clear a cookie
   *
   * @param   string $name Cookie name
   *
   * @return  boolean
   */
  public function clear($name) {
    if (!isset($_COOKIE[$name])) {
      return;
    } else {
      unset($_COOKIE[$name]);
      return setcookie($name, '', time() - 3600);
    }
  }

  /**
   * Update a cookie
   *
   * @param   string $name     Cookie name
   * @param   string $newValue New value
   *
   * @return  boolean
   */
  public function update($name, $newValue) {
    if (!isset($_COOKIE[$name])) {
      return;
    } else {
      return setcookie($name, $newValue);
    }
  }

  /**
   * Extend a cookie
   *
   * @param   string $name          Cookie name
   * @param   string $newExpiration New expiration time
   *
   * @return  boolean
   */
  public function extend($name, $newExpiration) {
    if (!isset($_COOKIE[$name])) {
      return;
    } else {
      return setcookie($name, $_COOKIE[$name], $this->getExpirationDate($newExpiration));
    }
  }

  /**
   * Check if a cookie exists
   *
   * @param   string $name Cookie name
   *
   * @return  boolean
   */
  public function exists($name) {
    if (!isset($_COOKIE[$name])) {
      return FALSE;
    } else {
      return TRUE;
    }
  }

  // ------------- Setters / Getters -----------------
  /**
   * Setter error handling mode (either true or false)
   *
   * @param boolean $internalErrorHandler Internal Error Handler
   */
  public function setInternalErrorHandler($internalErrorHandler) {
    $this->_internalErrorHandler = $internalErrorHandler;
  }

  /**
   * Getter error handling mode (either true or false)
   * @return boolean
   */
  public function getInternalErrorHandler() {
    return $this->_internalErrorHandler;
  }
}
