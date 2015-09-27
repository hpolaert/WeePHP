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
 * WeeLoader
 *
 * Core libray to load models, helpers & libraries (within the application)
 *
 * @access        Private
 * @version       0.1
 */
class WeeLoader {

  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $_errors = array();

  /**
   * Store loaded helpers
   * @var array $_loadedHelpers
   */
  protected $_loadedHelpers = array();

  /**
   * Store loaded helpers
   * @var array $_loadedLibraries
   */
  protected $_loadedLibraries = array();

  /**
   * Store loaded helpers
   * @var array $_loadedModels
   */
  protected $_loadedModels = array();

  /**
   * Helpers paths
   * @var array $_helpersPaths
   */
  protected $_helpersPaths = array();

  /**
   * Libraries paths
   * @var array $_librariesPaths
   */
  protected $_librariesPaths = array();

  /**
   * Error handling method (internal / external)
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;

  /**
   * Models path
   * @var array $_modelsPath
   */
  protected $_modelsPath;

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeeLoader
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside WeePHP Framework
    $this->setInternalErrorHandler($internalErrorHandler);
    // Set error codes
    $this->setErrorCodes();
  }

  /**
   * Generic class loading method
   *
   * @param    string $class  Class name
   * @param    mixed  $params Optional parameters
   * @param    string $alias  Optional alias to be given
   * @param    string $path   Class path
   * @param    string $type   Library or model
   *
   * @return    object
   */
  private function loadClass($class, $params, $alias, $path, $type) {
    // Alias ?
    $object = strtolower($alias != null ? $alias : $class);
    if (!isset(Wee()->$object)) {
      require_once($path);
      if ($type == 'model') {
        $class = $class . 'Model';
        Wee()->$object = new $class();
      } else if($type == 'validator'){
        // Preload RB validation model
        $class = 'Model_'.$class;
        $object = new $class();  
      }
      else {
        if ($this->isTrueArray($params)) {
          // Reflector to pass the array of arguments
          $reflector = new ReflectionClass($class);
          Wee()->$object = $reflector->newInstanceArgs($params);
        }
        // 1 parameter
        elseif ($params !== '' && $params !== NULL) {
          Wee()->$object = new $class($params);
        }
        // no parameter
        else {
          Wee()->$object = new $class;
        }
      }
    }
    else {
      // Error - Model is already loaded to super instance / two classes with same alias cannot be loaded
      $this->throwError(10);
    }
  }

  /**
   * Model loading method (alias to LoadClass)
   *
   * @param    string $model Model name
   * @param    string $alias Optional alias to be given
   *
   * @return    void
   */
  public function model($model, $alias = NULL) {
    if (in_array(strtolower($this->clean($model)), $this->_loadedModels)) {
      return;
    }
    else {
      if ($this->_modelsPath !== '') {
        $model = $this->clean($model);
        $location = str_replace("\\", "/", $this->_modelsPath . $model . 'Model.php');
        if (file_exists($location)) {
          $this->_loadedModels[] = strtolower($model);
          $this->loadClass($model, NULL, $alias, $location, 'model');
        }
        else {
          // Model file could not be found
          $this->throwError(6);
        }
      }
      else {
        // Models folder could not be located
        $this->throwError(5);
      }
    }
  }

  /**
   * RedBean loading method (alias to LoadClass)
   *
   * @param    string $validator Validator name
   *
   * @return    void
   */
  public function validator($validator) {
    $initName = ucfirst(strtolower($validator));
    if (in_array(strtolower($initName), $this->_loadedModels)) {
      return;
    }
    else {
      if ($this->_modelsPath !== '') {
        $validator = 'Model_' . ucfirst(strtolower($validator)) . '.php';
        $location = str_replace("\\", "/", $this->_modelsPath . $validator);
        if (file_exists($location)) {
          $this->_loadedModels[] = $initName;
          $this->loadClass($initName, NULL, NULL, $location, 'validator');
        }
        else {
          // Model file could not be found
          $this->throwError(6);
        }
      }
      else {
        // Models folder could not be located
        $this->throwError(5);
      }
    }
  }

  /**
   * Library loading method
   *
   * @param    string $library Model name
   * @param    string $alias   Optional alias to be given
   * @param    mixed  $params  Optional parameters
   *
   * @return    void
   */
  public function library($library, $alias = NULL, $params = NULL) {
    // Indicators to avoid helpers overlapping (system vs. user)
    $filesLoaded = 0;
    $filesChecked = 0;
    if (in_array(strtolower($library), $this->_loadedLibraries)) {
      return;
    }
    else {
      // Check if required variables are properly defined
      if (empty($this->_librariesPaths) || count($this->_librariesPaths) !== 2) {
        // Else throws error
        $this->throwError(7);
      }
      else {
        foreach ($this->_librariesPaths as $path) {
          $location = str_replace("\\", "/", $path . $library . '.php');
          $filesChecked++;
          if (file_exists($location)) {
            if ($filesLoaded !== 0) {
              // One system library and user library exist and could overlap
              $this->throwError(8);
            }
            else {
              $filesLoaded++;
              $this->_loadedLibraries[] = strtolower($library);
              $this->loadClass($library, $params, $alias, $location, 'library');
            }
          }
          else {
            if ($filesChecked == 2 && $filesLoaded == 0) {
              // Library could not be found
              $this->throwError(9);
            }
          }
        }
      }
    }
  }

  /**
   * Helper loading method
   *
   * @param    array $helpers Helper(s) name
   *
   * @return    void
   */
  public function helper($helpers = array()) {
    // Indicators to avoid helpers overlapping (system vs. user)
    $filesLoaded = 0;
    $filesChecked = 0;
    // Check if required variables are properly defined
    if (empty($this->_helpersPaths) || count($this->_helpersPaths) !== 2) {
      // Else throws error
      $this->throwError(4);
    }
    if (!is_array($helpers)) {
      $helpers = array($helpers);
    }
    // Begin treatment
    foreach ($helpers as $helper) {
      $helper = $this->clean($helper);
      // check if the helper isn't already loaded
      if (in_array(strtolower($helper), $this->_loadedHelpers)) {
        continue;
      }
      // if not try to fetch it from available paths
      foreach ($this->_helpersPaths as $path) {
        $location = str_replace("\\", "/", $path . $helper . 'Helper.php');
        $filesChecked++;
        if (file_exists($location)) {
          if ($filesLoaded !== 0) {
            // One system helper and user helpers exist and could overlap
            $this->throwError(2);
          }
          else {
            $this->_loadedHelpers[] = strtolower($helper);
            $filesLoaded++;
            include($location);
          }
        }
        else {
          if ($filesChecked == 2 && $filesLoaded == 0) {
            // No helper could be found, throw error
            $this->throwError(3);
          }
        }
      }
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
    }
    else {
      return FALSE;
    }
  }

  /**
   * Utility method to clean an helper or library/class input
   *
   * @param    string $name Name to be checked
   *
   * @return    boolean
   */
  private function clean($name) {
    // Remove potential 3 or 4 chars extension
    $name = preg_replace("/\\.[^.\\s]{3,4}$/", "", $name);
    $name = ucfirst(strtolower($name));
    return $name;
  }

  /**
   * Errors handler
   * Treat errors according to settings
   *
   * @param    integer $errorCode Error code to be handled
   *
   * @throws    LoaderException
   */
  private function throwError($errorCode) {
    if ($this->_internalErrorHandler) {
      die($this->_errors[$errorCode][1]);
    }
    else {
      $errorMsg = $this->_errors[$errorCode][1];
      $errorCategory = $this->_errors[$errorCode][0];
      throw new LoaderException($errorCategory, $errorMsg, $errorCode);
    }
  }

  /**
   * Set errors codes
   *
   * @return    void
   */
  private function setErrorCodes() {
    $this->_errors = array(
      2 => array(
        'F',
        'Error - User helper with system helper name detected.'
      ),
      3 => array('F', 'Error - An helper could not be loaded.'),
      4 => array('F', 'Error - Helpers paths have not been correctly setted.'),
      5 => array('F', 'Error - User models folder could not be found.'),
      6 => array('F', 'Error - One loaded model could not be found.'),
      7 => array(
        'F',
        'Error - Libraries paths have not been correctly setted.'
      ),
      8 => array(
        'F',
        'Error - User library with system library name detected.'
      ),
      9 => array('F', 'Error - A library could not be loaded.'),
      10 => array(
        'F',
        ' Error - Model is already loaded to super instance / two classes with same alias cannot be loaded.'
      )
    );
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
   * Setter helpers paths
   *
   * @param array $paths System and application helpers path
   */
  public function setHelpersPaths($paths) {
    $this->_helpersPaths = $paths;
  }

  /**
   * Setter libraries paths
   *
   * @param array $paths System and application libraries path
   */
  public function setLibrariesPaths($paths) {
    $this->_librariesPaths = $paths;
  }

  /**
   * Setter models path
   *
   * @param string $path Application models path
   */
  public function setModelsPath($path) {
    $this->_modelsPath = $path;
  }
}
