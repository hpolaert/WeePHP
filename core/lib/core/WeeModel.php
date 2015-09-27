<?php
/**
 * WeePHP Framework
 *
 * Light Framework, quick development !
 *
 * @package       WeePHP\Core
 * @author        Hugues Polaert <hugues.polaert@gmail.com>
 * @link          http://www.weephp.net
 * @version       0.1
 */
// --------------------------------------------
if (!defined('SERVER_ROOT')) {
  exit('Direct access to this file is not allowed.');
}
/**
 * WeeModel
 *
 * Provides Wee loaded classes to child models
 *
 * @access        Private
 * @version       0.1
 */
class WeeModel {
  // No use for the moment
  public function __construct() {
  }

  /**
   * __get Magic Method
   * Allows child models to access loaded classes in the super instance
   *
   * @param  string $key
   */
  function __get($key) {
    return Wee()->$key;
  }
}
