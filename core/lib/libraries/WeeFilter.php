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
 * WeeFilter
 *
 * Clean and sanitize vars
 *
 * @access        Private
 * @version       0.1
 */
class WeeFilter {
  // ------------- XSS Filtering   ----------------------
  // These methods filter post and get vars by reference
  // ----------------------------------------------------
  /**
   * Single post filter
   *
   * @param    string $var Input var
   *
   * @return    mixed
   */
  public function post($var) {
    if (isset($_POST[$var])) {
      $_POST[$var] = $this->rfilter($_POST[$var]);
      return $_POST[$var];
    } else {
      return NULL;
    }
  }

  /**
   * Single get filter
   *
   * @param    string $var Input var
   *
   * @return    mixed
   */
  public function get($var) {
    if (isset($_GET[$var])) {
      $_GET[$var] = $this->rfilter($_GET[$var]);
      return $_GET[$var];
    } else {
      return NULL;
    }
  }

  /**
   * Multiple post filter
   *
   * @return    void
   */
  public function postAll() {
    array_walk($_POST, array($this, 'wfilter'));
  }

  /**
   * Multiple get filter
   *
   * @return    void
   */
  public function getAll() {
    array_walk($_POST, array($this, 'wfilter'));
  }

  /**
   * Multiple get & post filter
   *
   * @return    void
   */
  public function filterAll() {
    array_walk($_POST, array($this, 'wfilter'));
    array_walk($_GET, array($this, 'wfilter'));
  }

  /**
   * Utility method for recursive array filtering
   *
   * @param    string $var Input var
   * @param    string $key Input key
   *
   * @return    string
   */
  private function wfilter(&$var, $key) {
    return $this->filter($var);
  }

  /**
   * Utility method for recursive array filtering
   *
   * @param    mixed $var Input var
   *
   * @return    string
   */
  public function filter($var) {
    if ($this->isTrueArray($var)) {
      foreach ($var as $key => $value) {
        $value[$key] = $this->filter($value);
      }
    } else {
      return $this->rfilter($var);
    }
  }

  /**
   * Utility method for signle filtering output
   *
   * @param    mixed $var Input var
   *
   * @return    string
   */
  public function rfilter($var) {
    $var = trim(htmlentities(strip_tags($var)));
    if (get_magic_quotes_gpc()) {
      $var = stripslashes($var);
    }
    $search = array("\\", "\x00", "\n", "\r", "'", '"', "\x1a");
    $replace = array("\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z");
    $var = str_replace($search, $replace, $var);
    return $var;
  }

  // ------------- Var Filtering   --------------------------
  // Filter vars according to a given expected output format
  // --------------------------------------------------------
  /**
   * Filter string with html escape for special chars
   *
   * @param    string $var Input var
   *
   * @return    string
   */
  public function filterStringSpecialChars($var) {
    return $this->varFilter($var, 'stringSpecialChars');
  }

  /**
   * Filter string with strip tags
   *
   * @param    string $var Input var
   *
   * @return    string
   */
  public function filterString($var) {
    return $this->varFilter($var, 'string');
  }

  /**
   * Filter to remove all illegal e-mail chars
   *
   * @param    string $var Input var
   *
   * @return    string
   */
  public function filterEmail($var) {
    return $this->varFilter($var, 'email');
  }

  /**
   * Filter to keep only +/- and figures
   *
   * @param    string $var Input var
   *
   * @return    string
   */
  public function filterInt($var) {
    return $this->varFilter($var, 'int');
  }

  /**
   * Filter to keep only +/-, figures, e and ,
   *
   * @param    string $var Input var
   *
   * @return    string
   */
  public function filterFloat($var) {
    return $this->varFilter($var, 'float');
  }

  /**
   * Filter to strip all url illegal chars
   *
   * @param    string $var Input var
   *
   * @return    string
   */
  public function filterUrl($var) {
    return $this->varFilter($var, 'url');
  }

  /**
   * Genric filtering method
   *
   * @param    string $var Input var
   *
   * @return    string
   */
  private function varFilter($var, $filter) {
    $output = NULL;
    switch ($var) {
    case 'stringSpecialChars' :
      $output = filter_var($var, FILTER_SANITIZE_SPECIAL_CHARS);
      break;
    case 'string' :
      $output = filter_var($var, FILTER_SANITIZE_STRING);    
      break;
    case 'email' :
      $output = filter_var($var, FILTER_SANITIZE_EMAIL);
      break;
    case 'int' :
      $output = filter_var($var, FILTER_SANITIZE_NUMBER_INT);
      break;
    case 'float' :
      $output = filter_var($var, FILTER_SANITIZE_NUMBER_FLOAT);
      break;
    case 'url' :
      $output = filter_var($var, FILTER_SANITIZE_URL);
      break;
    default :
      $output = NULL;
      break;
    }
    return $output;
  }

  // ------------- Validate Methods   -----------------
  // Validate methods check if the input var matches
  // the expected var type (int, email, etc..)
  // Optional parameter allows to returns the var
  // if this one has been validated, else returns
  // a boolean
  // --------------------------------------------------
  /**
   * Validate a integer
   *
   * @param    string  $var    Input var
   * @param    boolean $return If true returns var (if validated through filter)
   *
   * @return    mixed
   */
  public function validateInt($var, $return = FALSE) {
    return $this->validate($var, 'int', $return);
  }

  /**
   * Validate a float
   *
   * @param    string  $var    Input var
   * @param    boolean $return If true returns var (if validated through filter)
   *
   * @return    mixed
   */
  public function validateFloat($var, $return = FALSE) {
    return $this->validate($var, 'float', $return);
  }

  /**
   * Validate an email
   *
   * @param    string  $var    Input var
   * @param    boolean $return If true returns var (if validated through filter)
   *
   * @return    mixed
   */
  public function validateEmail($var, $return = FALSE) {
    return $this->validate($var, 'email', $return);
  }

  /**
   * Validate an url
   *
   * @param    string  $var    Input var
   * @param    boolean $return If true returns var (if validated through filter)
   *
   * @return    mixed
   */
  public function validateUrl($var, $return = FALSE) {
    return $this->validate($var, 'url', $return);
  }

  /**
   * Validate a var as boolean
   *
   * @param    string  $var    Input var
   * @param    boolean $return If true returns var (if validated through filter)
   *
   * @return    mixed
   */
  public function validateBoolean($var, $return = FALSE) {
    return $this->validate($var, 'bool', $return);
  }

  /**
   * Genric validate method
   *
   * @param    string  $var    Input var
   * @param    string  $filter Filter to be applied
   * @param    boolean $return If true returns var (if validated through filter)
   *
   * @return    mixed
   */
  private function validate($var, $filter, $return) {
    $output = NULL;
    $bool = FALSE;
    switch ($filter) {
    case 'email' :
      $bool = filter_var($var, FILTER_VALIDATE_EMAIL);
      break;
    case 'bool' :
      $bool = filter_var($var, FILTER_VALIDATE_BOOLEAN);
      break;
    case 'int' :
      $bool = filter_var($var, FILTER_VALIDATE_INT);
      break;
    case 'float' :
      $bool = filter_var($var, FILTER_VALIDATE_FLOAT);
      break;
	case 'url' :
      $bool = filter_var($var, FILTER_VALIDATE_URL);
      break;  
    default :
      $bool = FALSE;
      break;
    }
    // If return = true and bool = true returns var
    // If return = true and bool = false returns null
    // if return = false, returns $bool
    $output = ($return != FALSE ? ($bool != FALSE ? $var : NULL) : (bool) $bool);
    return $output;
  }

  /**
   * Safely echo session
   *
   * @param   string $index Read index
   *
   * @return  string
   */
  public function session($index) {
    if (isset($_SESSION[$index])) {
      return $_SESSION[$index];
    } else {
      return NULL;
    }
  }

  /**
   * Convert a given string to a seo friendly URl
   *
   * @param    string $string String to be converted
   *
   * @return    string
   */
  public function cleanUrl($string) {
    $output = strtolower($string);
    $output = preg_replace('/[^a-zA-Z0-9]/i', ' ', $output);
    $output = trim($output);
    $output = preg_replace('/\s+/', ' ', $output);
    $output = preg_replace('/\s+/', '-', $output);
    return $output;
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
}
