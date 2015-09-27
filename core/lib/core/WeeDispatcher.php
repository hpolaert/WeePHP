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
 * WeeDispatcher
 *
 * Core libray to handle incoming url request
 *
 * @access        Private
 * @version       0.1
 */
class WeeDispatcher {
  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $_errors = array();
  /**
   * Internal error handler
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;
  /**
   * Controllers Directory
   * @var string $_controllerDir
   */
  protected $_controllerDir;
  /**
   * Application URL
   * @var string $_appUrl
   */
  protected $_appUrl;
  /**
   * Routes accepted var types
   * @var array $_varTypes
   */
  protected $_varTypes = array();
  /**
   * Array of the final response (Lang, Controller, Methods, Args, RouteName)
   * @var array $_finalResponse
   */
  protected $_finalResponse = array();
  /**
   * Instantiated controller
   * @var object $_finalResponse
   */
  protected $_controllerInstance;
  /**
   * Request url without get var
   * @var string $_requestUrl
   */
  protected $_requestUrl;
  /**
   * Static routes (without any dynamic elements
   * @var array $_staticRoutes
   */
  protected $_staticRoutes = array();
  /**
   * Array (nbOfElements, array(element1, element2))
   * @var array $_staticRoutes
   */
  protected $_dynamicRoutes = array();
  /**
   * Store controller (1), method (2), optional lang (3)
   * @var array $_routeParams
   */
  protected $_routeParams = array();
  /**
   * Routes name array
   * @var array $_routes
   */
  protected $_routes = array();
  /**
   * 404 Page storage
   * @var array $_404
   */
  protected $_404 = array();

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeeDispatcher
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside WeePHP Framework
    $this->setInternalErrorHandler($internalErrorHandler);
    // Set error codes
    $this->setErrorCodes();
    // Set accepted var types for routes definition
    $this->_varTypes = array('var', 'string', 'number');
  }

  /**
   * Attach URL function
   * Create a link between an url and its controller/method
   *
   * @param    string $name       Route Name
   * @param    string $uri        Url to be catched
   * @param    string $controller Controller to be called
   * @param    string $method     Method to be called
   * @param    string $lang       (Optional) Lang associated with the route
   *
   * @return    void
   */
  public function attachUrl($name, $uri, $controller, $method, $lang = '') {
    if (!empty($name) && !empty($uri) && !empty($controller) && !empty($method)) {
      // Check route existence
      if (in_array($name, $this->_routes)) {
        // The route name has already been defined, throw error
        $this->throwError(11);
      } else {
        // Add route
        $this->_routes[] = $name;
      }
      // Cleanup URL (remove front and ending slashes)
      $uri = ltrim(rtrim($uri, '/'), '/');
      // Separate treatments between "static" and "dynamic" urls
      // --> DYNAMIC
      if (strpos($uri, '@') !== FALSE) {
        $arrayOfUriSegments = explode('/', $uri);
        // Iterate through each segment
        foreach ($arrayOfUriSegments as $segment) {
          if (preg_match('/^@string:[^\s]+@|@var:[^\s]+@|@number:[^\s]+@/i', $segment)) {
            $this->_dynamicRoutes[$name][1][] = $segment;
          } else {
            if (preg_match('/^[^@]+@[^@]+$|^@[^\s]+@|^[@][^\s]+[^@]$|^[^@][^\s]+[@]$/i', $segment)) {
              // If xxx@xxx or @xxx xxx@ throw incorrect format error
              $this->throwError(6);
            } else {
              $this->_dynamicRoutes[$name][1][] = $segment;
            }
          }
        }
        if (!empty($this->_dynamicRoutes[$name][1])) {
          // Store number of elements for quick sorting
          $this->_dynamicRoutes[$name][0] = count($this->_dynamicRoutes[$name][1]);
          // Store route others params
          $this->_routeParams[$name] = array($controller, $method, $lang);
        }
      } // --> STATIC
      else {
        // Add url as it is
        $this->_staticRoutes[$name] = $uri;
        $this->_routeParams[$name] = array($controller, $method, $lang);
      }
    } else {
      // Incorrect format
      $this->throwError(7);
    }
  }

  /**
   * Attach 404 URL
   * Create a link between a 404 page and its controller/method (otherwise throws exception)
   *
   * @param    string $name       Route Name
   * @param    string $controller Controller to be called
   * @param    string $method     Method to be called
   * @param    string $lang       (Optional) Lang associated with the route
   *
   * @return    void
   */
  public function attach404($name, $controller, $method, $lang = '') {
    if (!empty($name) && !empty($controller) && !empty($method)) {
      $this->_404["name"] = $name;
      $this->_404["controller"] = $controller;
      $this->_404["method"] = $method;
      $this->_404["lang"] = $lang;
    } else {
      // Incorrect format
      $this->throwError(7);
    }
  }

  /**
   * Refresh
   * To use when content has already been sent
   *
   * @param    string  $location Target Location
   * @param    boolean $external External or internal redirection
   *
   * @return    string
   */
  public function refresh($location, $external = FALSE) {
    if (!$external) {
      $location = WEB_ROOT . $location;
    }
    echo '<META HTTP-EQUIV="Refresh" Content="0; URL=' . $location . '">';
  }

  /**
   * Redirect
   * To use when content has not already been sent
   *
   * @param    string  $location Target Location
   * @param    boolean $external External or internal redirection
   *
   * @return    string
   */
  public function redirect($location, $external = FALSE) {
    if (!$external) {
      $location = WEB_ROOT . $location;
    }
    header('Location: ' . $location . '');
  }

  /**
   * Dispatcher
   * Checks registered routes against the given url
   *
   * @param    string $url URL to be dispatched
   *
   * @return    void
   */
  public function dispatch($url) {
    // Separate get vars
    $url = explode('&', $url);
    $url = $url[0];
    // Url without &var... + cleanup
    $request = $this->_requestUrl = ltrim(rtrim($url, '/'), '/');;
    // --> Check for STATIC URL exact match
    if (in_array($request, $this->_staticRoutes)) {
      $matchedName = array_search($request, $this->_staticRoutes);
      // Build final response (controller / method association, no arguments)
      $this->_finalResponse = array(
        $matchedName,
        $this->_routeParams[$matchedName][0],
        $this->_routeParams[$matchedName][1],
        'noargs',
        $this->_routeParams[$matchedName][2]
      );
    } // --> Begin DYNMAC URL analysis
    else {
      // store request URI segments count
      $requestArray = explode('/', $request);
      $requestURISegmentsCount = count($requestArray);
      // 1st check : check if a dynamic route has a matching number of elements
      // Add array to a global array to check against each segment
      $globalCheckArrays = array();
      foreach ($this->_dynamicRoutes as $dynamicRoute) {
        if ($dynamicRoute[0] === $requestURISegmentsCount) {
          // Store current array to global check array
          $globalCheckArrays[] = $dynamicRoute[1];
        }
      }
      $globalArraysVerifiedCount = count($globalCheckArrays);
      if (!empty($globalCheckArrays)) {
        // Check each segment against each possible route segments
        $segmentCounter = 0;
        foreach ($requestArray as $segment) {
          for ($globalArraysCounter = 0; $globalArraysCounter < $globalArraysVerifiedCount; $globalArraysCounter++) {
            // 1st, check type of segment
            if (strpos($globalCheckArrays[$globalArraysCounter][$segmentCounter], '@') !== FALSE) {
              // Dynamic segment
              $dynamicSegmentArray = str_replace('@', '', $globalCheckArrays[$globalArraysCounter][$segmentCounter]);
              $dynamicSegmentArray = explode(':', $dynamicSegmentArray);
              switch ($dynamicSegmentArray[0]) {
                // String
              case $this->_varTypes[1] :
                if (!ctype_alpha($segment)) {
                  // Type does not match
                  unset($globalCheckArrays[$globalArraysCounter]);
                }
                break;
                // Integer
              case $this->_varTypes[2] :
                if (!ctype_digit($segment)) {
                  // Type does not match
                  unset($globalCheckArrays[$globalArraysCounter]);
                }
                break;
                // Var (alphanumeric) => no verification, can accept any value
              case $this->_varTypes[0] :
                // Do nothing
                break;
              }
              // If length is defined
              if (isset($dynamicSegmentArray[2])) {
                if (strlen($segment) != $dynamicSegmentArray[2]) {
                  // Length does not match, remove array from potential routes
                  unset($globalCheckArrays[$globalArraysCounter]);
                }
              }
            } else {
              // Static segment
              if ($globalCheckArrays[$globalArraysCounter][$segmentCounter] !== $segment) {
                // One or more static segment does not match, remove array from further checks
                unset($globalCheckArrays[$globalArraysCounter]);
              }
            }
          }
          $segmentCounter++;
        }
        // Readibility
        $potentialRoutes = $globalCheckArrays;
        // If collision => take the route with the most first static elements
        $potentialCount = count($potentialRoutes);
        // Sorting array to prioritize static elements over dynamic ones
        $sortingArray = array();
        if ($potentialCount == 0) {
          $this->throwError(4);
        } else {
          if ($potentialCount > 1) {
            foreach ($potentialRoutes as $index => $potentialRoute) {
              $alphaValue = '';
              foreach ($potentialRoute as $URISegment) {
                $alphaValue .= (strpos($URISegment, '@') !== FALSE) ? "b" : "a";
              }
              $sortingArray[$index] = $alphaValue;
            }
            // Get the alphabetical order of the build sentences
            $sortingArrayOrdered = $sortingArray;
            asort($sortingArrayOrdered);
            $sortingArrayOrdered = array_values($sortingArrayOrdered);
            $targetIndex = (array_search($sortingArrayOrdered[0], $sortingArray));
            $potentialRoutes = $potentialRoutes[$targetIndex];
          } else {
            $potentialRoutes = array_values($potentialRoutes);
            $potentialRoutes = $potentialRoutes[0];
          }
          // Matched name
          $matchedName = array_search(array(
            count($potentialRoutes),
            $potentialRoutes
          ), $this->_dynamicRoutes);
          // Args extraction
          $args = array();
          foreach ($potentialRoutes as $index => $segment) {
            if (strpos($segment, '@') !== FALSE) {
              $argName = str_replace('@', '', $segment);
              $argName = explode(':', $argName);
              $argName = $argName[1];
              $args[$argName] = $requestArray[$index];
            }
          }
          $this->_finalResponse = array(
            $matchedName,
            $this->_routeParams[$matchedName][0],
            $this->_routeParams[$matchedName][1],
            $args,
            $this->_routeParams[$matchedName][2]
          );
        }
      } else {
        // No dynamic match
        $this->throwError(4);
      }
    }
  }

  /**
   * Instantiator
   * Instantiate the target controller to allow user libs integration
   *
   * @return    void
   */
  public function instantiate() {
    // Setup controller, method, arguments & location
    $controller = ucfirst(strtolower($this->_finalResponse[1]));
    $location = $this->_controllerDir . DIRECTORY_SEPARATOR . $controller . 'Controller.php';
    // Controller file exists ?
    if (is_file($location)) {
      // Load controller
      require($location);
      $controller = $controller . 'Controller';
      $_controller = new $controller();
      $this->_controllerInstance = $_controller;
    } else {
      // Controller file does not exist
      $this->throwError(8);
    }
  }

  /**
   * Launcher
   * Run the application by calling selected controller and method
   *
   * @return    void
   */
  public function run() {
    $method = strtolower($this->_finalResponse[2]);
    $args = $this->_finalResponse[3];
    // Method is callable ?
    if (method_exists($this->_controllerInstance, $method)) {
      // Run the application
      call_user_func(array($this->_controllerInstance, $method), $args);
    } else {
      // Method does not exist
      $this->throwError(9);
    }
  }

  // ------------- Utility Methods   -----------------
  /**
   * Errors handler
   * Treat errors according to settings
   *
   * @param    integer $errorCode Error code to be handled
   *
   * @return    Exception/Error Message
   */
  private function throwError($errorCode) {
    if ($this->_internalErrorHandler) {
      die($this->_errors[$errorCode][1]);
    } else {
      if (isset($this->_404) && !empty($this->_404)) {
        $this->_finalResponse = array(
          $this->_404["name"],
          $this->_404["controller"],
          $this->_404["method"],
          'noargs',
          $this->_404["lang"]
        );
      } else {
        $errorMsg = $this->_errors[$errorCode][1];
        $errorCategory = $this->_errors[$errorCode][0];
        throw new DispatchException($errorCategory, $errorMsg, $errorCode);
      }
    }
  }

  /**
   * URL Cleaner
   * Remove the end forward slash if necessary
   *
   * @param    string $var Url to be cleaned
   *
   * @return    string
   */
  private function urlCleaner($var) {
    if (substr($var, -1, 1) == '/') {
      return substr($var, 0, -1);
    } else {
      return $var;
    }
  }

  /**
   * Set errors codes
   *
   * @return    void
   */
  private function setErrorCodes() {
    $this->_errors = array(
      0 => array(
        'F',
        'Error - Invalid number of dynamic arguments.'
      ),
      1 => array('F', 'Depreciated - Var type is not recognized or unknown.'),
      2 => array('F', 'Depreciated - Error - Route requirements do not match.'),
      3 => array(
        'F',
        'Depreciated - Error - Route dynamic elements types do not match.'
      ),
      4 => array('F', 'Error - No dynamic or static match'),
      5 => array('F', 'Depreciated - Invalid route URI segments number'),
      6 => array(
        'F',
        'Error - Dynamic arguments are not correctly attached to the route.'
      ),
      7 => array(
        'F',
        'Error - Invalid route construction, one or more arguments have not been set correctly.'
      ),
      8 => array('F', 'Error - Controller file does not exist.'),
      9 => array('F', 'Error - Method does not exist on the controller.'),
      10 => array('F', 'Depreciated - Invalid argument.'),
      11 => array('F', 'Error - Route names must be unique.')
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
   * Getter error handling mode (either true or false)
   * @return boolean
   */
  public function getInternalErrorHandler() {
    return $this->_internalErrorHandler;
  }

  /**
   * Setter application Url
   *
   * @param string $appUrl Application base Url
   */
  public function setAppUrl($appUrl) {
    // Remove end slash if necessary
    $this->_appUrl = $appUrl;
  }

  /**
   * Getter application Url
   * @return string
   */
  public function getAppUrl() {
    return $this->_appUrl;
  }

  /**
   * Getter requestUrl
   * @return string
   */
  public function getRequestUrl() {
    return $this->_requestUrl;
  }

  /**
   * Setter controllers directory
   *
   * @param string $controllerDir Controllers  directory
   */
  public function setControllerDir($controllerDir) {
    // Add double backslashes to prevent special characters escaping
    $this->_controllerDir = str_replace("\\", "\\\\", $controllerDir);
  }

  /**
   * Getter controllers directory
   * @return string
   */
  public function getControllerDir() {
    return $this->_controllerDir;
  }

  /**
   * Getter final response
   * @return array
   */
  public function getFinalResponse() {
    return $this->_finalResponse;
  }

  /**
   * Getter current route Lang
   * @return string
   */
  public function getCurrentRouteLang() {
    if (isset($this->_finalResponse[4])) {
      return $this->_finalResponse[4];
    } else {
      return NULL;
    }
  }

  /**
   * Getter current route name
   * @return string
   */
  public function getCurrentRouteName() {
    if (isset($this->_finalResponse[0])) {
      return $this->_finalResponse[0];
    } else {
      return NULL;
    }
  }

  /**
   * Getter current route controller
   * @return string
   */
  public function getCurrentRouteController() {
    if (isset($this->_finalResponse[1])) {
      return $this->_finalResponse[1];
    } else {
      return NULL;
    }
  }

  /**
   * Get current route method
   * @return string
   */
  public function getCurrentRouteMethod() {
    if (isset($this->_finalResponse[2])) {
      return $this->_finalResponse[2];
    } else {
      return NULL;
    }
  }
}