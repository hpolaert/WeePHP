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
 * Loader methods (for loading core libraries)
 */
/**
 * Generic instantiating method
 * Core classes loader, acts as a singleton (inspired from CodeIgniter)
 *
 * @param     string $className Class Name
 * @param     string $alias     Alias for the class
 * @param     string $dir       Libray directory
 * @param     mixed  $params    Optional parameters
 *
 * @throws    LoaderException
 *
 * @return    object
 */
function &load($className, $alias = NULL, $dir = '', $params = NULL) {
  // Static Array of instantiated classes
  static $instantiatedClasses = array();
  // Input class filepath
  $filePath = $dir . $className . '.php';
  // Alias necessary ?
  $instantiatedName = ($alias ? $alias : $className);
  // Check if the class is already instantiated
  if (isset($instantiatedClasses[$instantiatedName])) {
    // If so, do not go further
    return $instantiatedClasses[$instantiatedName];
  } // Else, load the class
  elseif (is_dir($dir)) {
    if (is_file($filePath)) {
      // Library found !
      require($filePath);
      // Store loaded classes for super controller inheritance purpose
      loadedClass($instantiatedName);
      // Extracts parameters if necessary
      if (is_array($params) && count($params) && isset($params)) {
        // Reflector to pass the array of arguments
        $reflector = new ReflectionClass($className);
        $instantiatedClasses[$instantiatedName] = $reflector->newInstanceArgs($params);
      } // Only one parameter is defined, no need for reflection
      elseif ($params !== '' && $params !== NULL) {
        $instantiatedClasses[$instantiatedName] = new $className($params);
      } // Instantiation without parameters
      else {
        $instantiatedClasses[$instantiatedName] = new $className();
      }
      return $instantiatedClasses[$instantiatedName];
    } else {
      // Input var class file name 'className' does not exist
      throw new LoaderException('F', 'Error - file for ' . $className . ' does not exist.', 0);
    }
  } else {
    // Input var folder 'dir' does not exist
    throw new LoaderException('F', 'Error - folder for ' . $className . ' does not exist.', 1);
  }
}

/**
 * Utility function to pass loaded classes to the parent controller
 * Can also store a loaded class to the static array (same purpose as above)
 *
 * @param    string $className Class Name
 *
 * @return    array
 */
function &loadedClass($className = '') {
  // Array of loaded classes
  static $loadedClassObjects = array();
  if ($className != '') {
    $loadedClassObjects[$className] = $className;
  }
  return $loadedClassObjects;
}