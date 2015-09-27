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
 * WeeConfig
 *
 * Handle global configuration operations
 *
 * @access        Private
 * @version       0.1
 */
class WeeConfig {
  /**
   * Config array
   * @var array $_config
   */
  protected $_config;
  /**
   * Database config table
   * @var string $_dbConfigTable
   */
  protected $_dbConfigTable;

  /**
   * Store a config value within an optional sub array
   *
   * @param    mixed  $var      Var
   * @param    mixed  $value    Value
   * @param    string $subarray Sub array
   *
   * @return    void
   */
  public function set($var, $value, $subarray = NULL) {
    if ($subarray != NULL) {
      // Define and store vars into sub array
      $this->_config[$subarray][$var] = $value;
    } else {
      // Else flat vars storing
      $this->_config[$var] = $value;
    }
  }

  /**
   * Store an array as a config subarray
   *
   * @param    string $name     Array name
   * @param    string $subarray Sub array values
   *
   * @return    void
   */
  public function setAll($name, $subarray) {
    if ($name != NULL && $this->isTrueArray($subarray)) {
      $this->_config[$name] = $subarray;
    }
  }

  /**
   * Retrieve a config value
   *
   * @param    string $key      Var
   * @param    string $subarray Sub array
   *
   * @return    string
   */
  public function fetch($key, $subarray = NULL) {
    if ($subarray != NULL) {
      if (isset($this->_config[$subarray][$key])) {
        return $this->_config[$subarray][$key];
      }
    } else {
      if (isset($this->_config[$key])) {
        return $this->_config[$key];
      }
    }
  }

  /**
   * Retrieve all settings from a subarray
   *
   * @param    string $subarray Sub array
   *
   * @return    array
   */
  public function fetchAll($subarray) {
    if (isset($this->_config[$subarray])) {
      return $this->_config[$subarray];
    } else {
      return;
    }
  }

  /**
   * Set a configuration parameter in the database
   *
   * @param   string $name  Name of the parameter
   * @param   string $value Associated value
   *
   * @return  integer
   */
  public function setDBVar($name, $value) {
    if ($name !== NULL && $name !== '' && $value !== NULL && $value !== '') {
      $name = strtolower($name);
      $configElement = R::findOne($this->_dbConfigTable, 'param = ?', array($name));
      if ($configElement !== NULL) {
        $configElement->value = $value;
        $id = R::store($configElement);
        return $id;
      } else {
        $configElement = R::dispense($this->_dbConfigTable);
        $configElement->param = $name;
        $configElement->value = $value;
        $id = R::store($configElement);
        return $id;
      }
    }
  }

  /**
   * Retrieve a database configuration item
   *
   * @param   string $name Name of the parameter
   *
   * @return  string
   */
  public function fetchDBVar($name) {
    $name = strtolower($name);
    $configElement = R::findOne($this->_dbConfigTable, 'param = ?', array($name));
    return $configElement->value;
  }

  /**
   * Delete a database configuration item
   *
   * @param   string $name Name of the parameter
   *
   * @return  void
   */
  public function deleteDBVar($name) {
    $name = strtolower($name);
    $configElement = R::findOne($this->_dbConfigTable, 'param = ?', array($name));
    if ($configElement !== NULL) {
      R::trash($configElement);
    }
  }

  // ------------- Utility Methods   -----------------
  /**
   * Utility method to check if a given variable is a true array
   *
   * @param    array $array Array to be checked
   *
   * @return    boolean
   */
  private function isTrueArray($array) {
    if (is_array($array) && isset($array) && count($array)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  // ------------- Setters / Getters -----------------
  /**
   * Set database config table name
   *
   * @param   string $configTable Name of the database config table
   *
   * @return  void
   */
  public function setConfigTable($configTable) {
    $this->_dbConfigTable = $configTable;
  }
}
