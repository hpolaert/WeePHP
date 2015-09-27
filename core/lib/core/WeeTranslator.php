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
 * WeeTranslator
 *
 * Utility class to handle and store translates
 *
 * @access        Private
 * @version       0.1
 */
class WeeTranslator {
  /**
   * Translation array
   * @var array $_lang
   */
  protected $_lang;

  /**
   * Translation a given index into the target language
   *
   * @param    string $value Index to be translated
   *
   * @return    string
   */
  public function translate($value) {
    if ((isset($this->_lang) && sizeof($this->_lang) && is_array($this->_lang)) && isset($this->_lang[$value])) {
      return $this->_lang[$value];
    } else {
      return $value;
    }
  }

  /**
   * Public setter translation array
   * Also accepts systems languages
   *
   * @param    array   $lang     Lang Array
   * @param    boolean $priority Merge order (optional)
   *
   * @return    void
   */
  public function setLang($lang, $priority = FALSE) {
    if (isset($this->_lang)) {
      $this->_lang = ($priority ? array_merge($this->_lang, $lang) : array_merge($lang, $this->_lang));
    } else {
      $this->_lang = $lang;
    }
  }
}
