<?php
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
 * WeePaginate
 *
 * Paginate various content
 *
 * @access        Private
 * @version       0.1
 */
class WeePaginate {

  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $_errors = array();

  /**
   * Array of elements to be paginated
   * @var array $_elements
   */
  protected $_elements = array();

  /**
   * Current page
   * @var integer $_currentPage
   */
  protected $_currentPage;

  /**
   * Number of pages
   * @var integer $_nbOfPages
   */
  protected $_nbOfPages;

  /**
   * Next page
   * @var integer $_nextPage
   */
  protected $_nextPage;

  /**
   * Previous page
   * @var integer $_previousPage
   */
  protected $_previousPage;

  /**
   * First page
   * @var integer $_firstPage
   */
  protected $_firstPage;

  /**
   * Last page
   * @var integer $_lastPage
   */
  protected $_lastPage;

  /**
   * Total number of elements
   * @var integer $_nbOfElements
   */
  protected $_nbOfElements;

  /**
   * Base URL without the ?page var
   * @var string $_baseURL
   */
  protected $_baseURL;

  /**
   * Internal error handler
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;

  /**
   * Widget to be parsed
   * @var string $_systemWidgetsLocation
   */
  protected $_systemWidgetsLocation;

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeePaginate
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside PXeli Framework
    $this->setInternalErrorHandler($internalErrorHandler);
    // Set error codes
    $this->setErrorCodes();
    // Set base URL
    $this->_baseURL = Wee()->router->getRequestUrl();
  }

  /**
   * Build navigation links according to a set of parameters
   *
   * @param    string $mode    Either basic (all pages are displayed) or slide (provide a range and auto add elipsis)
   * @param    array  $options Array of parameters
   *
   * @return    string
   */
  public function getNavigation($mode, $options = NULL) {
    // Basic template
    // {first} {previous} {pages} {next} {last}
    $output = NULL;
    $first = $previous = $next = $last = $pages = $slideLeft = $slideRight = '';
    // Parse settings
    $firstPageContent = (isset($options["firstPageContent"]) ? $options["firstPageContent"] : "&laquo;");
    $firstPageClass = (isset($options["firstPageClass"]) ? "class='" . $options["firstPageClass"] . "'" : "");
    $lastPageContent = (isset($options["lastPageContent"]) ? $options["lastPageContent"] : "&raquo;");
    $lastPageClass = (isset($options["lastPageClass"]) ? "class='" . $options["lastPageClass"] . "'" : "");
    $previousPageContent = (isset($options["previousPageContent"]) ? $options["previousPageContent"] : "<");
    $previousPageClass = (isset($options["previousPageClass"]) ? "class='" . $options["previousPageClass"] . "'" : "");
    $nextPageContent = (isset($options["nextPageContent"]) ? $options["nextPageContent"] : ">");
    $nextPageClass = (isset($options["nextPageClass"]) ? "class='" . $options["nextPageClass"] . "'" : "");
    $curentPageClass = (isset($options["curentPageClass"]) ? "class='" . $options["curentPageClass"] . "'" : "");
    $nonCurentPageClass = (isset($options["nonCurentPageClass"]) ? "class='" . $options["nonCurentPageClass"] . "'" : "");
    $inactiveArrowClass = (isset($options["inactiveArrowClass"]) ? "class='" . $options["inactiveArrowClass"] . "'" : "");
    $firstActive = (isset($options["firstActive"]) ? $options["firstActive"] : TRUE);
    $lastActive = (isset($options["lastActive"]) ? $options["lastActive"] : TRUE);
    $previousActive = (isset($options["previousActive"]) ? $options["previousActive"] : TRUE);
    $nextActive = (isset($options["nextActive"]) ? $options["nextActive"] : TRUE);
    $slideActive = (isset($options["slideActive"]) ? $options["slideActive"] : TRUE);
    $slideRange = (isset($options["slideRange"]) ? $options["slideRange"] : 3);
    $slideContent = (isset($options["slideContent"]) ? $options["slideContent"] : "...");
    $slideClass = (isset($options["slideClass"]) ? "class='" . $options["slideClass"] . "'" : "");
    // Begin output operation
    // Basic template, show all links
    // [FirstPage] [PreviousPage] {[Page1] [Page2] [Page3] ...} [NextPage] [LastPage]
    if ($this->_nbOfPages > 1) {
      $baseUrlPage = rtrim($this->_baseURL, "/") . "?page=";
      /** Link First Page **/
      if ($firstActive) {
        if ($this->_currentPage != 1) {
          $link = $baseUrlPage . strval($this->_firstPage);
          if ($mode === "basic") {
            $first = '<a href="' . $link . '" ' . $firstPageClass . '>' . $firstPageContent . '</a>';
          }
          else {
            $first = '<a href="' . $link . '" ' . $nonCurentPageClass . '>' . strval($this->_firstPage) . '</a>';
          }
        }
        else {
          if ($mode === "basic") {
            $first = '<span ' . $inactiveArrowClass . '>' . $firstPageContent . '</span>';
          }
          else {
            $first = '<span ' . $curentPageClass . '>' . strval($this->_firstPage) . '</span>';
          }
        }
      }
      /*********************/
      /** Link First Page **/
      if ($previousActive) {
        if ($this->_currentPage > 1) {
          $link = $baseUrlPage . strval($this->_currentPage - 1);
          $previous = '<a href="' . $link . '" ' . $previousPageClass . '>' . $previousPageContent . '</a>';
        }
        else {
          $previous = '<span ' . $inactiveArrowClass . '>' . $previousPageContent . '</span>';
        }
      }
      /*********************/
      /**   Pages Links   **/
      if ($mode === "basic") {
        for ($i = 1; $i <= $this->_nbOfPages; $i++) {
          if ($this->_currentPage == $i) {
            $pages .= '<span ' . $curentPageClass . '>' . $i . '</span>';
          }
          else {
            $link = $baseUrlPage . strval($i);
            $pages .= '<a href="' . $link . '" ' . $nonCurentPageClass . '>' . $i . '</a>';
          }
        }
      }
      else {
        $rangeRight = $rangeLeft = $slideRange;
        $startOffset = ($this->_currentPage - $rangeLeft);
        $lastOffset = ($this->_currentPage + $rangeRight);
        if ($slideActive) {
          if ($startOffset > ($this->_firstPage + 1)) {
            $slideLeft = '<span ' . $slideClass . ' >' . $slideContent . '</span>';
          }
          if ($lastOffset < (($this->_lastPage - 1))) {
            $slideRight = '<span ' . $slideClass . ' >' . $slideContent . '</span>';
          }
        }
        // print($startOffset . ' <br /> ' . $this->_currentPage . ' <br /> ' . $lastOffset);
        $i = $startOffset;
        for ($i; $i <= $lastOffset; $i++) {
          if ($i > $this->_firstPage && $i < $this->_lastPage) {
            if ($this->_currentPage == $i) {
              $pages .= '<span ' . $curentPageClass . '>' . $i . '</span>';
            }
            else {
              $link = $baseUrlPage . strval($i);
              $pages .= '<a href="' . $link . '" ' . $nonCurentPageClass . '>' . $i . '</a>';
            }
          }
          else {
            // do nothing
          }
        }
      }
      /*********************/
      /** Link Next Page  **/
      if ($nextActive) {
        if ($this->_currentPage < $this->_lastPage) {
          $link = $baseUrlPage . strval($this->_currentPage + 1);
          $next = '<a href="' . $link . '" ' . $nextPageClass . '>' . $nextPageContent . '</a>';
        }
        else {
          $next = '<span ' . $inactiveArrowClass . '>' . $nextPageContent . '</a>';
        }
      }
      /*********************/
      /** Link Last Page  **/
      if ($lastActive) {
        if ($this->_currentPage != $this->_lastPage) {
          $link = $baseUrlPage . strval($this->_lastPage);
          if ($mode === "basic") {
            $last = '<a href="' . $link . '" ' . $lastPageClass . '>' . $lastPageContent . '</a>';
          }
          else {
            $last = '<a href="' . $link . '" ' . $nonCurentPageClass . '>' . strval($this->_lastPage) . '</a>';
          }
        }
        else {
          if ($mode === "basic") {
            $last = '<span ' . $inactiveArrowClass . '>' . $lastPageContent . '</a>';
          }
          else {
            $last = '<span ' . $curentPageClass . '>' . strval($this->_lastPage) . '</a>';
          }
        }
      }
      /*********************/
      // Build final output accord to the mode
      if ($mode === "basic") {
        $output = $first . $previous . $pages . $next . $last;
      }
      else {
        $output = $previous . $first . $slideLeft . $pages . $slideRight . $last . $next;
      }
    }
    // No navigation needed
    else {
      $output = '';
    }
    return $output;
  }

  /**
   * Simple alias to paginate a given array (for instance, the result of a db query)
   *
   * @param    integer $currentPage    Either basic (all pages are displayed) or slide (provide a range and auto add elipsis)
   * @param    integer $elementsByPage Nb of elements by page to be dispalyed
   * @param    array   $array          Array to be paginated
   *
   * @return    array
   */
  public function arrayContent($currentPage, $elementsByPage, $array) {
    return $this->content($currentPage, $elementsByPage, NULL, NULL, $array);
  }

  /**
   * Core class method, paginate file/html or array content and returns it as an array
   *
   * @param    integer $currentPage    Either basic (all pages are displayed) or slide (provide a range and auto add elipsis)
   * @param    integer $elementsByPage Nb of elements by page to be dispalyed
   * @param    string  $content        Content of a file
   * @param    string  $pattern        Regex expression for array split
   * @param    array   $array          Array to be paginated
   *
   * @return    array
   */
  public function content($currentPage, $elementsByPage, $content, $pattern, $array = NULL) {
    // Final output
    $output = array();
    // Set up first page
    $this->_currentPage = (($currentPage == NULL && $currentPage == 0) ? 1 : $currentPage);
    // Calculate total number of elements
    $this->_elements = ($this->isTrueArray($array) ? $array : preg_split($pattern, $content));
    // Check if valid regex
    if (isset($pattern) && @preg_match($pattern, $content) === FALSE) {
      $this->throwError(0);
    }
    else {
      // So far so good, continue
      $this->_nbOfElements = count($this->_elements);
      $this->_nextPage = $this->_previousPage = $this->_firstPage = $this->_lastPage = NULL;
      if ($this->_nbOfElements <= 1) {
        // No pagination needed
        $this->_nbOfPages = 1;
        $output[0] = $this->_elements[0];
        $this->_currentPage = (($this->_currentPage > $this->_nbOfPages) ? 1 : $this->_currentPage);
        return $output;
      }
      else {
        // Calcule number of pages
        $this->_nbOfPages = ceil($this->_nbOfElements / $elementsByPage);
        if ($this->_currentPage > $this->_nbOfPages) {
          $this->_currentPage = 1;
        }
        if ($this->_nbOfPages > 1) {
          $this->_previousPage = ($this->_currentPage == 1 ? NULL : ($this->_currentPage - 1));
          $this->_nextPage = (($this->_currentPage + 1) < $this->_nbOfPages ? ($this->_currentPage + 1) : NULL);
          $this->_firstPage = 1;
          $this->_lastPage = $this->_nbOfPages;
          // Calculate output content
          if ($this->_currentPage == 1) {
            $startOffset = 0;
            $output = array_splice($this->_elements, $startOffset, $elementsByPage);
            return $output;
          }
          else {
            $startOffset = (($this->_currentPage - 1) * $elementsByPage);
            if ($this->_currentPage < $this->_nbOfPages) {
              // Multiple pages, select a slice of the array
              $output = array_splice($this->_elements, $startOffset, $elementsByPage);
            }
            else {
              $output = array_splice($this->_elements, $startOffset);
            }
            return $output;
          }
        }
        else {
          // No pagination needed
          $this->_nbOfPages = 1;
          $output = array_splice($this->_elements, 0);
          return $output;
        }
      }
    }
  }

  /**
   * Simple alias to paginate a given file
   *
   * @param    integer $currentPage    Current page number
   * @param    integer $elementsByPage Nb of elements by page to be dispalyed
   * @param    string  $filePath       Location of the file to be paginated
   * @param    array   $pattern        Regex expression for array split
   *
   * @return    array
   */
  public function fileContent($currentPage, $elementsByPage, $filePath, $pattern) {
    $content = file_get_contents($filePath);
    return $this->content($currentPage, $elementsByPage, $content, $pattern);
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
        'Error - Invalid regex expression passed as parameter.'
      )
    );
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
   * Errors handler
   * Treat errors according to settings
   *
   * @param    integer $errorCode Error code to be handled
   *
   * @return    Exception/Error Message
   * @throws    PaginateException
   */
  private function throwError($errorCode) {
    if ($this->_internalErrorHandler) {
      die($this->_errors[$errorCode][1]);
    }
    else {
      $errorMsg = $this->_errors[$errorCode][1];
      $errorCategory = $this->_errors[$errorCode][0];
      throw new PaginateException($errorCategory, $errorMsg, $errorCode);
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