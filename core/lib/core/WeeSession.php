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
 * WeeSession
 *
 * Utility class to override default session handler with Redbean ORM
 * Warning : this class cannot work without RedBean
 *
 * @access        Private
 * @version       0.1
 */
class WeeSession {
  /**
   * Session opened to data writing (true/false)
   * @var    boolean $opened
   */
  protected $opened = TRUE;
  /**
   * DB session table name
   * @var string $_dbSessionTable
   */
  protected $_dbSessionTable;

  /**
   * Constructor
   *
   * @param string $dbSessionTable Input Session table
   */
  function __construct($dbSessionTable) {
    // Overrides session handler
    $this->_dbSessionTable = $dbSessionTable;
    session_set_save_handler(array($this, "_open"), array(
      $this,
      "_close"
    ), array($this, "_read"), array($this, "_write"), array(
      $this,
      "_destroy"
    ), array($this, "_gc"));
    session_start();
  }

  /**
   * Check DB connexion
   *
   * @return boolean
   */
  public function _open() {
    // If Redbean has been defined
    return TRUE;
  }

  /**
   * Close the DB connexion
   *
   * @return boolean
   */
  public function _close() {
    return TRUE;
  }

  /**
   * Read a data from a $_SESSION["index"]
   *
   * @param    integer $id Session ID (unique)
   *
   * @return    string
   */
  public function _read($id) {
    if ($row = R::getRow("SELECT data FROM " . $this->_dbSessionTable . " WHERE id = '" . $id . "'")) {
      return $row['data'];
    } else {
      return '';
    }
  }

  /**
   * Write all the session data into the DB
   *
   * @param    string $id   Session ID (unique)
   * @param    string $data Session Data
   *
   * @return    array
   */
  public function _write($id, $data) {
    $access = time();
    $sql = "REPLACE INTO " . $this->_dbSessionTable . " (id, access, data) VALUES ('" . $id . "', '" . $access . "', '" . $data . "')";
    if (R::exec($sql)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Destroy a session
   *
   * @param    integer $id Session ID (unique)
   *
   * @return    array
   */
  public function _destroy($id) {
    $sql = "DELETE FROM " . $this->_dbSessionTable . " WHERE id = '" . $id . "'";
    if (R::exec($sql)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Destroy and clean a session according to the expire time defined
   *
   * @param    integer $expire Expiration time
   *
   * @return    array
   */
  public function _gc($expire) {
    $expiration = time() - $expire;
    $sql = "DELETE FROM " . $this->_dbSessionTable . " WHERE access < " . $expiration;
    if (R::exec($sql)) {
      return TRUE;
    }
    return FALSE;
  }
}
