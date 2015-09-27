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
 * WeeController
 *
 * Super controller instance referencing all loaded libraries
 *
 * @access        Private
 * @version       0.1
 */
class WeeController {
  /**
   * Super controller instance
   * @var object $instance
   */
  private static $instance;

  /**
   * Generic Constructor
   */
  public function __construct() {
    self::$instance = &$this;
    foreach (loadedClass() as $var => $className) {
      $this->$var = &load($className);
    }
  }

  public static function &get_instance() {
    return self::$instance;
  }
}
