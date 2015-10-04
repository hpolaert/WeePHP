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
 * WeeTemplate
 *
 * Template engine with caching capabilities
 *
 * @access        Private
 * @version       0.1
 */
class WeeTemplate {
  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $_errors = array();
  /**
   * Association of vars setted through $index->set('var', $var);
   * @var array $_values
   */
  protected $_values = array();
  /**
   * Internal error handler
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;
  /**
   * Web root
   * @var string $_webRoot
   */
  protected $_webRoot;
  /**
   * Template folder
   * @var string $_templateFolder
   */
  protected $_templateFolder;
  /**
   * Cache folder
   * @var string $_cacheFolder
   */
  protected $_cacheFolder;
  /**
   * Cache folder
   * @var string $_cacheFolder
   */
  protected $_frontCacheFolder;
  /**
   * Compiled files folder
   * @var string $_compiledFolder
   */
  protected $_compiledFolder;
  /**
   * Compiled file
   * @var string $_compiledFile
   */
  protected $_compiledFile;
  /**
   * Cached file
   * @var string $_cachedFile
   */
  protected $_cachedFile;
  /**
   * Cache activation
   * @var boolean $_cacheActive
   */
  protected $_cacheActive;
  /**
   * Cache expiration time
   * @var integer $_cacheExpirationTime
   */
  protected $_cacheExpirationTime;
  /**
   * Compilated files expiration time
   * @var integer $_compilatedExpirationTime
   */
  protected $_compilatedExpirationTime;
  /**
   * Cache extension (unique identifier)
   * @var string $_cacheExt
   */
  protected $_cacheExt;
  /**
   * Header custom content (specific .js and .css to current template)
   * @var string $_headerCustomContent
   */
  protected $_headerCustomContent;
  /**
   * Footer custom content (specific .js and .css to current template)
   * @var string $_cacheExt
   */
  protected $_footerCustomContent;
  /**
   * Header array of specific integration elements
   * @var array $_headerCustomContent
   */
  protected $_headerElements = array();
  /**
   * Footer array of specific integration elements
   * @var array $_footerElements
   */
  protected $_footerElements = array();
  /**
   * Template url location for relative integration purpose
   * @var string $_templatePath
   */
  protected $_templatePath;
  /**
   * Langs Array for trasnlations purpose
   * @var array $_lang
   */
  protected $_lang = array();
  /**
   * Route details array (parameters from WeeDispatcher)
   * @var array $_routeDetails
   */
  protected $_routeDetails = array();
  /**
   * Entities location
   * @var array $_entitiesPath
   */
  protected $_entitiesPath = array();
  /**
   * Widgets location
   * @var array $_widgetsPath
   */
  protected $_widgetsPath = array();
  /**
   * WeeDispatcher final response
   * @var array $_finalRoutingResponse
   */
  protected $_finalRoutingResponse = array();
  /**
   * Template Name for configuration purpose
   * @var string $_finalRoutingResponse
   */
  protected $_templateName;
    /**
   * Template file extension
   * @var string $_tplExt
   */
  protected $_tplExt;

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeeTEmplate
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside WeePHP Framework
    $this->setInternalErrorHandler($internalErrorHandler);
    // Set error codes
    $this->setErrorCodes();
  }

  // -------------   Core Methods   -----------------
  /**
   * Public method to render a template, or a specific template if a cache identifier is passed to the function
   *
   * @param string  $template           Template to be rendered (path/to/template/templateName) - ! without the .html extension !
   * @param boolean $staticCacheEnabled (optional, default=null) Overrides cache activation if necessary
   * @param string  $cacheExt           (optional, default=null) Defines a unique identifier for one variation of a template file
    *
   * @return string
   */
  public function render($template, $staticCacheEnabled = FALSE, $cacheExt = NULL) {
    // Template location check
    $this->_templateName = $template;
    $tplLocation = $this->_templateFolder . DIRECTORY_SEPARATOR . $template . $this->_tplExt;
    if (file_exists($tplLocation)) {
      // Initialize outputContent
      $outputContent = '';
      // Should caching be disabled ?
      if ($staticCacheEnabled) {
        $this->_cacheActive = TRUE;
      }
      // Unique identifier ?
      $this->_cacheExt = '';
      if ($cacheExt != NULL) {
        $this->_cacheExt = '_' . $cacheExt;
      }
      // If there are specific .css or .js to be integrated, build relevant html code
      $this->buildSpecificTemplateContent();
      //------------------ Files name and location
      $compiledName = basename($tplLocation, '.html') . '_compiled.php';
      $cachedName = basename($tplLocation, '.html') . $this->_cacheExt . '_cache.php';
      $compiledFileLocation = $this->_compiledFolder . DIRECTORY_SEPARATOR . $compiledName;
      $cachedFileLocation = $this->_frontCacheFolder . DIRECTORY_SEPARATOR . $cachedName;
      // Start rendering process - If cache is active
      if ($this->_cacheActive) {
        // If cached file exist and is recent enough
        if (file_exists($cachedFileLocation) && (filemtime($cachedFileLocation) > (time() - 60 * $this->_cacheExpirationTime))) {
            include($cachedFileLocation);
        }
        else {
          // Start looking for an existing compiled file or compile the template
          $outputContent = $this->lookForCompiledTplFile($compiledFileLocation, $template, TRUE);
        }
      }
      else {
        // Try to fetch last compiled version, however no cached version is created
        $outputContent = $this->lookForCompiledTplFile($compiledFileLocation, $template, FALSE);
      }
      // Returns final content
      print $outputContent;
    }
    else {
      // If template file has not been found, throw error
      $this->throwError(0);
    }
  }

  /**
   * Check if compiled file exists and is recent enough
   *
   * @param    string  $compiledFileLocation Compiled file to look for
   * @param    string  $template             Template to render if no compiled file has been found
   * @param    boolean $cache                True if a cache should be created
   *
   * @return    string
   */
  private function lookForCompiledTplFile($compiledFileLocation, $template, $cache) {
    // Final compiled output
    $output = '';
    // For debugging purpose, compiledExpireTime can be set to 0
    // to build the template everytime from scratch
    if ($this->_compilatedExpirationTime != 0) {
      if (file_exists($compiledFileLocation) && (filemtime($compiledFileLocation) > (time() - 60 * $this->_compilatedExpirationTime))) {
        // Compiled file exists, start rendering its content
        extract($this->_values);
        ob_start();
        include($compiledFileLocation);
        $output = ob_get_clean();
        // If necessary, create the according cached version
        if ($cache) {
          $this->generateFile($this->_frontCacheFolder, $template, $output, $this->_cacheExt . '_cache.php');
        }
      } else {
        $output = $this->renderBuild($template);
      }
    } else {
      $output = $this->renderBuild($template);
    }
    // Returns either found compiled file or compiled code from template
    return $output;
  }

  /**
   * Internal method to extract setted variables and returns a template compiled content (optionally generates a cached version)
   *
   * @param    string $template Template to be rendered (path/to/template/templateName) - ! without the .html extension !
   *
   * @return    string
   */
  private function renderBuild($template) {
    // Extract variables form values Array ($this->values["variable"] becomes $variable)
    extract($this->_values);
    // Compilate the template (transforms Class tags with php code)
    $output = $this->compilate($this->_templateFolder . DIRECTORY_SEPARATOR . $template . '.html');
    // Write compiled version of the template
    $this->generateFile($this->_compiledFolder, $template, $output, '_compiled.php');
    ob_start();
    // Get the compiled file
    include($this->_compiledFile);
    $outputContent = ob_get_clean();
    // If cache is active, write a cached version of the template
    if ($this->_cacheActive) {
      $this->generateFile($this->_frontCacheFolder, $template, $outputContent, $this->_cacheExt . '_cache.php');
    }
    return $outputContent;
  }

  /**
   * Core method to compilate class code into PHP
   * Template .html (mixed content) => compiled version .php
   *
   * @param    string $templateLocation Template file path
   *
   * @return    string
   */
  private function compilate($templateLocation) {
    // Extract content from template file
    $compiledCodeRequest = file_get_contents($templateLocation);
    // Check if the template extends a parent template
    if (strpos($compiledCodeRequest, '{[extends') !== FALSE) {
      // If so, flatten parent-child template
      $compiledCodeRequest = $this->flattenTemplate($compiledCodeRequest);
    }
    /* Break the input at the following tags :
     * {[noparse]} - {[/noparse]} - {[/loop]} - {[comment]} - {[/comment]}
     * {[if(condition)]} - {[elseif(condition)]} - {[function="name(arguments)"]} - {[loop="array"]}
     * {\[auth]} - {\[auth:group\([^)]*\)]} - {\[auth:perm\([^)]*\)]}
     */
    $compiledCodeRequest = preg_split('+({\[auth:group\([^)]*\)]})|({\[auth:perm\([^)]*\)]})|({\[auth]})|({\[(?:\/)?noparse\]})|({\[\/loop]})|({\[(?:\/)?comment\]})|({\[if\(.*?\)\]})|({\[elseif\(.*?\)\]})|({\[function="[^(]*\([^)]*\)"]})|({\[loop=".*?"]})+i', $compiledCodeRequest, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    // Content compiled that will be returned
    $compiledCodeResponse = '';
    // Treatment modifiers
    $isNoParse = $isIgnored = FALSE;
    // Loop depth for nested loops / loops within included templates
    $loopDepth = 0;
    // Begin code compilation
    foreach ($compiledCodeRequest as $key => $element) {
      // If $element is not "empty" (only space)
      if (!ctype_space($element)) {
        // ------------------------- Treatment modifiers (noparse/comment)
        // {[noparse]} => Renders the element as it is
        // {[comment]} => Do not render the element
        if (strpos($element, '{[noparse]}') !== FALSE) {
          $isNoParse = TRUE;
          $element = NULL;
          // Remove the element from the output
        } else {
          if (strpos($element, '{[/noparse]}') !== FALSE) {
            $isNoParse = FALSE;
            $element = NULL;
          } else {
            if (strpos($element, '{[comment]}') !== FALSE) {
              $isIgnored = TRUE;
              $element = NULL;
            } else {
              if (strpos($element, '{[/comment]}') !== FALSE) {
                $isIgnored = FALSE;
                $element = NULL;
              }
            }
          }
        }
        // Order -> content is ignored ? -> content should not be parsed ? else data extraction
        if ($isIgnored) {
          // Content is ignored do nothing
        } else {
          if ($isNoParse) {
            // Noparse is enabled, do not parse the current $element and returns it as it is
            $compiledCodeResponse .= $element;
          } else {
            // ---------------------- Core breaking tags extraction
            // CONDITION IF
            // {\[if\((.*?)\)\]} => {[if(condition)]}
            if (preg_match('+{\[if\((.*?)\)\]}+i', $element, $output)) {
              $out = '<?php if(' . $this->getCondition($output[1], $loopDepth) . '){ ?>';
              $compiledCodeResponse .= $out;
            }
            // CONDITION ELSE IF
            // {\[elseif\((.*?)\)\]} => {[elseif(condition)]}
            else {
              if (preg_match('+{\[elseif\((.*?)\)\]}+i', $element, $output)) {
                $out = '<?php } elseif(' . $this->getCondition($output[1], $loopDepth) . '){ ?>';
                $compiledCodeResponse .= $out;
              }
              // CALL AUTH GROUP (extension) :
              // {\[auth:group\(([^)]*)\)]} => {[auth:group("hello")]}
              else {
                if (preg_match('+{\[auth:group\(([^)]*)\)]}+i', $element, $output)) {
                  $out = "<?php if(\$this->callAuthCheckGroup(" . $this->getCondition($output[1], $loopDepth) . ")){ ?>";
                  $compiledCodeResponse .= $out;
                }
                // CALL AUTH PERM (extension) :
                // {\[auth:perm\(([^)]*)\)]} => {[auth:perm("hello")]}
                else {
                  if (preg_match('+{\[auth:perm\(([^)]*)\)]}+i', $element, $output)) {
                    $out = "<?php if(\$this->callAuthCheckPerm(" . $this->getCondition($output[1], $loopDepth) . ")){ ?>";
                    $compiledCodeResponse .= $out;
                  }
                  // CALL AUTH LOGGED IN(extension) :
                  // {\[auth]} => {[auth]}
                  else {
                    if (preg_match('+{\[auth]}+i', $element, $output)) {
                      $out = "<?php if(\$this->callAuthCheck()){ ?>";
                      $compiledCodeResponse .= $out;
                    }
                    // FUNCTION
                    // {\[function="([^(]*)\(([^)]*)\)"]} => {[function="name(arguments)"]}
                    else {
                      if (preg_match('+{\[function="([^(]*)\(([^)]*)\)"]}+i', $element, $output)) {
                        $out = '<?php ' . $output[1] . '(' . $this->getVars($output[2], $loopDepth, TRUE) . '); ?>';
                        $compiledCodeResponse .= $out;
                      }
                      // LOOP
                      // {\[loop="(.*?)"]} => {[loop="array"]}
                      else {
                        if (preg_match('+{\[loop="(.*?)"]}+i', $element, $output)) {
                          $loopDepth++;
                          // Defines unique iteration, key, array and value variables according to current loop depth
                          $currentArray = '$' . $this->getCondition($output[1], $loopDepth);
                          $iteration = '$iteration' . $loopDepth;
                          $key = '$key' . $loopDepth;
                          $value = '$value' . $loopDepth;
                          $compiledCodeResponse .= '<?php ' . $iteration . '=-1; ?>';
                          // Writes a condition to check if the current array is empty/setted/array, else {[else]} tag can be use to return alternative content
                          $compiledCodeResponse .= '<?php if(isset(' . $currentArray . ') && is_array(' . $currentArray . ') && sizeof(' . $currentArray . ')) foreach(' . $currentArray . ' as ' . $key . ' => ' . $value . ' ){' . $iteration . '++; ?>';
                          // Close loop
                          $compiledCodeResponse .= '<?php  ?>';
                        }
                        // CLOSE LOOP
                        // {\[\/loop]} => {[/loop]}
                        else {
                          if (preg_match('+{\[\/loop]}+i', $element, $output)) {
                            $loopDepth--;
                            $compiledCodeResponse .= '<?php } ?>';
                            // Replace all other tags which are not "breaking" tags
                          }
                          // NO BREAKING TAG FOUND
                          // Replace non breaking tags with getVars (internal content = FALSE)
                          else {
                            $output = $this->getVars($element, $loopDepth);
                            $compiledCodeResponse .= $output;
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      } else {
        // Empty space, add it as it is to keep original format
        $compiledCodeResponse .= $element;
      }
      // End foreach
    }
    // Return final output
    return $compiledCodeResponse;
  }

  /**
   * Extract vars - Second core method of WeeTemplate
   *
   * Extract non breaking tags (which do not influence other tags treatment)
   * from PTeamplate language to php code, works for external tags ({[tag]})
   * and internal content (function arguments, conditions etc..)
   *
   * @param mixed   $varOrArray Var or Array to be parsed
   * @param integer $depth      Loop depth
   * @param boolean $internal   True if current $var is inside a condition or function
   *
   * @return string
   */
  private function getVars($varOrArray, $depth, $internal = FALSE) {
    // Initialize output
    $out = '';
    // Internal to FALSE if plain tags such as {[$var]} instead of $var
    // ---------------------- EXTERNAL TAGS
    if (!$internal) {
      /* Break the content whenever a {[tag]} is found, breaking tags :
       * {[content]} - _e($var) - {[widget="name(arguments)"]} - {[include(templateLocation)]}
       * {[entity="entityName.method(argument)"]} - {[route(var)]} - {[#root#]}
       * {[#config#variable#]}
       */
      $arrayOfTags = preg_split('#({\[.+?]})|(<\/head>)|(\_e\(\$[^\)]*\))|(<\/html>)#i', $varOrArray, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
      // Begin extraction
      foreach ($arrayOfTags as $key => $element) {
        // ELSE TAG : {[else]}
        if (strpos($element, '{[else]}') !== FALSE) {
          $out .= '<?php } else { ?>';
        } // END IF TAG : {[ /IF ]}
        else {
          if (strpos($element, '{[/if]}') !== FALSE) {
            $out .= '<?php } ?>';
          } // END AUTH TAG : {[ /auth ]}
          else {
            if (strpos($element, '{[/auth]}') !== FALSE) {
              $out .= '<?php } ?>';
            } // TEMPLATE FOLDER WEB ROOT FOR INTEGRATION (JSS/CSS/IMG) PURPOSE : {[#troot#]}
            else {
              if (strpos($element, '{[#troot#]}') !== FALSE) {
                $out .= '<?php echo $this->_templatePath; ?>';
              } // WEB ROOT FOR INTERNAL LINKS : {[#root#]}
              else {
                if (strpos($element, '{[#root#]}') !== FALSE) {
                  $out .= '<?php echo $this->_webRoot; ?>';
                } // TRANSLATE TAG : \_e\(\$([^\)]*)\) => _e($var)
                else {
                  if (preg_match('+\_e\(\$([^\)]*)\)+i', $element, $output)) {
                    $out .= "<?php \$this->translate('" . $output[1] . "'); ?>";
                  } // SINGLE VAR : {\[(\$[^[.[]*)]} => {[$var|optionalModifier]}
                  else {
                    if (preg_match('+{\[(\$[^[.[]*)]}+i', $element, $output)) {
                      $out .= $this->getSingleVar($output[1], $internal, $depth);
                    } // CONSTANT : {\[\@([^[.[]*)]} => {[@constant]}
                    else {
                      if (preg_match('+{\[\@([^[.[]*)@]}+i', $element, $output)) {
                        $out .= $this->getSingleVar($output[1], $internal, NULL, TRUE);
                      } // ARRAY VAR : {\[\$([^[.]{1,})((?:\[[\w\d]{1,}\]){1,})(?:\|(.{1,}))?\]\} => {[$array[1][value][n..*]|optionalModifier]}
                      else {
                        if (preg_match('+{\[\$([^[.]{1,})((?:\[[\w\d]{1,}\]){1,})(?:\|(.{1,}))?\]\}+i', $element, $output)) {
                          $out .= $this->getArrayVar($output, $depth, FALSE);
                        } // INCLUDE : {\[include\(([^)]*)\)]} => {[include(templateLocation)]}
                        else {
                          if (preg_match('+{\[include\(([^)]*)\)]}+i', $element, $output)) {
                            $out .= $this->getInclude($output[1], $depth);
                          } // CALL ENTITY (extension) : {\[entity="([^(]*)\.([^(]*)\(([^)]*)\)"]} => {[entity="className.method(arguments)"]}
                          else {
                            if (preg_match('+{\[entity="([^(]*)\.([^(]*)\(([^)]*)\)"]\}+i', $element, $output)) {
                              $out .= "<?php \$this->callEntity('" . $output[1] . "', '" . $output[2] . "', array(" . $this->getVars($output[3], $depth, TRUE) . ")); ?>";
                            } // CALL WIDGET (extension) : {\[widget="([^(]*)\(([^)]*)\)"]} => {[widget="widgetName(optionalArguments)"]}
                            else {
                              if (preg_match('+{\[widget="([^(]*)\(([^)]*)\)"]}+i', $element, $output)) {
                                $out .= "<?php \$this->callWidget('" . $output[1] . "', array(" . $this->getVars($output[2], $depth, TRUE) . ")); ?>";
                              } // CORE CONFIG ELEMENT (extension) : {\[#config#(.*?)#]} => {[#config#parameter#]}
                              else {
                                if (preg_match('+{\[#config#(.*?)#]}+i', $element, $output)) {
                                  $out .= "<?php echo \$this->callConfig('" . $output[1] . "'); ?>";
                                } // HEADER / FOOTER SPECIFIC CONTENT : refers to buildSpecificTemplateContent
                                else {
                                  if (preg_match('+<\/head>|<\/html>+i', $element, $output)) {
                                    // Add specific integration code (.css / .js)
                                    if ($this->_headerCustomContent != NULL || $this->_footerCustomContent != NULL) {
                                      $patterns[0] = '+<\/head>+i';
                                      $patterns[1] = '+<\/html>+i';
                                      $replacements[0] = $this->_headerCustomContent . "\r\n</head>";
                                      $replacements[1] = $this->_footerCustomContent . "\r\n</html>";
                                      $element = preg_replace($patterns, $replacements, $element);
                                      $out .= $element;
                                    } else {
                                      $out .= $element;
                                    }
                                  } // STATIC CONTENT, do nothing
                                  else {
                                    $out .= $element;
                                  }
                                }
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    } else {
      // If internal, explode the string into an array base on the ',' (for functions arguments)
      // ---------------------- INTERNAL TAGS
      $arrayOfArguments = explode(',', $varOrArray);
      $tempArgsReiceverArray = array();
      $iterationIndicator = 0;
      foreach ($arrayOfArguments as $key => $arrayElement) {
        $arrayElement = trim($arrayElement);
        // NUMBER
        if (ctype_digit($arrayElement)) {
          $tempArgsReiceverArray[$iterationIndicator] = trim($arrayElement);
        } // VARIABLE OR CONSTANT
        else {
          if (strpos($arrayElement, '$') !== FALSE || strpos($arrayElement, '@') !== FALSE || strpos($arrayElement, '#') !== FALSE) {
            // SINGLE VAR
            if (preg_match('+\$[^]]*$+i', $arrayElement, $output)) {
              $tempArgsReiceverArray[$iterationIndicator] = trim($this->getSingleVar($arrayElement, $internal, $depth));
            } // CONSTANT
            else {
              if (preg_match('+\@([^]]*)$+i', $arrayElement, $output)) {
                $tempArgsReiceverArray[$iterationIndicator] = trim($this->getSingleVar($output[1], $internal, NULL, TRUE));
              } // ARRAY VAR
              else {
                if (preg_match('/^\$([^[\|]+)((?:(?:\[.+\])+))(?:\|(.{1,}))?/i', $arrayElement, $output)) {
                  $tempArgsReiceverArray[$iterationIndicator] = trim($this->getArrayVar($output, $depth, TRUE));
                } // CONFIG VAR
                else {
                  if (preg_match('/#config#(.*?)#/i', $arrayElement, $output)) {
                    $tempArgsReiceverArray[$iterationIndicator] = trim($this->callConfig($output[1]));
                  }
                }
              }
            }
          } // BY DEFAULT => STRING
          else {
            $tempArgsReiceverArray[$iterationIndicator] = trim($arrayElement);
          }
        }
        $iterationIndicator++;
      }
      // Implode array to get argument1, argument2 etc..
      $strArguments = implode(', ', $tempArgsReiceverArray);
      $out .= $strArguments;
    }
    return $out;
  }

  /**
   * Return condition as PHP code from WeeTemplate tags
   *
   * @param    string  $condition Condition to be parsed
   * @param    integer $depth     Current loop depth
   *
   * @return    string
   */
  private function getCondition($condition, $depth) {
    $returnOutput = array();
    // Add a whitespace to match first var
    $condition = ' ' . $condition;
    // Break the content when a var, constant or function is found
    $arrayOfElements = preg_split('/([^,]\$[^\s\=\)\,]+)|(\@[^\s]+)|(#config#.*?#)|(function="[^(]+\([^)]*\)")/i', $condition, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $iteration = 0;
    // Begin treatment
    foreach ($arrayOfElements as $key => $arrayElement) {
      $arrayElement = trim($arrayElement);
      // SINGLE VAR : (^\$[^\[\]]*$) => $var
      if (preg_match('/(^\$[^\[\]]*$)/i', $arrayElement, $output)) {
        $returnOutput[$iteration] = $this->getSingleVar($arrayElement, TRUE, $depth);
      } // CONFIG VAR
      else {
        if (preg_match('/#config#(.*?)#/i', $arrayElement, $output)) {
          $returnOutput[$iteration] = "\$this->callConfig('" . $output[1] . "')";
        } // CONSTANT : ^\@([^\[\]]*$) => @constant
        else {
          if (preg_match('+^\@([^\[\]]*$)+i', $arrayElement, $output)) {
            $returnOutput[$iteration] = $this->getSingleVar($output[1], TRUE, NULL, TRUE);
          } // FUNCTION : ^function="([^(]*)\(([^)]*)\) => function="name(arguments)"
          else {
            if (preg_match('+^function="([^(]*)\(([^)]*)\)"+i', $arrayElement, $output)) {
              $returnOutput[$iteration] = $output[1] . '(' . $this->getVars($output[2], $depth, TRUE) . ')';
            } // ARRAY VAR : ^\$([^[\|]+)((?:(?:\[.+\])+))(?:\|(.{1,}))? => $array[1][name][@constant]|optionalModifier
            else {
              if (preg_match('/^\$([^[\|]+)((?:(?:\[.+\])+))(?:\|(.{1,}))?/i', $arrayElement, $output)) {
                $returnOutput[$iteration] = $this->getArrayVar($output, $depth, TRUE);
              } // STATIC CONTENT
              else {
                $returnOutput[$iteration] = $arrayElement;
              }
            }
          }
        }
      }
      $iteration++;
    }
    // Convert the condition array into a string
    $returnOutput = implode(' ', $returnOutput);
    return $returnOutput;
  }

  /**
   * Internal method to extract a single var and optionally applies a modifier to it
   *
   * @param    string  $var      Variable to be parsed
   * @param    boolean $internal Internal or external var (internal for inside a function)
   * @param    integer $depth    Current loop depth
   * @param    boolean $constant Constant or variable
   *
   * @return    string
   */
  private function getSingleVar($var, $internal, $depth = NULL, $constant = FALSE) {
    // Initialize output
    $returnOutput = '';
    // Constant treatment
    if ($constant) {
      // If modifier is found, apply it
      if (strpos($var, '|') !== FALSE) {
        $returnOutput = $this->getModifier($var);
      } else {
        $returnOutput = $var;
      }
    } // Variable treatment
    else {
      // If modifier is found, apply it
      if (strpos($var, '|') !== FALSE) {
        $returnOutput = $this->getModifier($var);
      } else {
        // Converts loops variables to php variable with their depth
        if (strpos($var, 'key') !== FALSE) {
          $returnOutput = '$key' . $depth;
        } else {
          if (strpos($var, 'iteration') !== FALSE) {
            $returnOutput = '$iteration' . $depth;
          } else {
            if (strpos($var, 'value') !== FALSE) {
              $returnOutput = '$value' . $depth;
            } else {
              $returnOutput = $var;
            }
          }
        }
      }
    }
    // If variable is "isolated", print it
    if (!$internal) {
      $returnOutput = '<?php echo ' . $returnOutput . '; ?>';
    }
    return $returnOutput;
  }

  /**
   * Internal method to extract an array var or a loop var
   * Exemple : {[$array[0][index][2][@CONSTANT]|optionalModifier]} => optionalModifier($array[0]["index"][2][CONSTANT])
   *
   * @param    string  $arrayVars Array/Loop var
   * @param    integer $depth     Current depth
   * @param    boolean $internal  Internal or external var
   *
   * @return    string
   */
  private function getArrayVar($arrayVars, $depth, $internal = FALSE) {
    $outVar = array(
      'var' => NULL,
      'elements' => NULL,
      'modifier' => NULL,
      'finalOutput' => NULL
    );
    // Array of var, elements, modifier and final output
    $outVar['var'] = $arrayVars[1];
    $outVar['elements'] = $arrayVars[2];
    $outVar['modifier'] = (isset($arrayVars[3]) ? $arrayVars[3] : NULL);
    if ($outVar['var'] == 'value') {
      $outVar['finalOutput'] = '$value' . $depth;
    } else {
      $outVar['finalOutput'] = '$' . $outVar['var'];
    }
    // Convert the indexes of the array (example [index] => ["index"])
    $outVar['finalOutput'] .= $this->getArrayElements($outVar['elements'], $depth);
    // If a modifier is found, apply it
    if ($outVar['modifier'] != NULL) {
      $tempVar = $outVar['finalOutput'] . '|' . $outVar['modifier'];
      $outVar['finalOutput'] = $this->getModifier($tempVar);
    }
    // If variable is "isolated", print it
    if (!$internal) {
      $outVar['finalOutput'] = '<?php echo ' . $outVar['finalOutput'] . '; ?>';
    }
    return $outVar['finalOutput'];
  }

  /**
   * Internal method called by extractArrayVar to convert the indexes of an array/loop var
   *
   * @param    string $arrayElements Indexes to be parsed
   *
   * @return    string
   */
  private function getArrayElements($arrayElements) {
    $returnOutput = '';
    // Break the indexes into an array (example : [0][index][3] => array("0","index","3"))
    $arrayOfElements = preg_split('+(\[[\w]*]{1,})+i', $arrayElements, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    foreach ($arrayOfElements as $key => $arrayElement) {
      $tempVar = str_replace(array('[', ']'), array('', ''), $arrayElement);
      // If number or var, do nothing
      if (ctype_digit($tempVar) || strpos($tempVar, '$') !== FALSE) {
        $returnOutput .= '[' . $tempVar . ']';
        // If constant, substract the @
      } else {
        if (strpos($arrayElement, '@') !== FALSE) {
          $tempConstant = str_replace('@', '', $tempVar);
          $returnOutput .= $tempConstant;
          // Else the var must be a string, return it as such
        } else {
          $returnOutput .= '["' . $tempVar . '"]';
        }
      }
    }
    return $returnOutput;
  }

  /**
   * Internal method to convert include tag and returns template file
   *
   * @param    string   $includeTemplate Template to be included
   * @param    interger $loopdepth       Current loop depth
   *
   * @return  string
   */
  private function getInclude($includeTemplate, $loopdepth) {
    // Initialize vars
    $returnOutput = '';
    $templateName = $includeTemplate;
    $returnOutput .= '<?php $tpl = new ' . get_class($this) . '(); ';
    // Class parameters inheritance
    $returnOutput .= '$tpl->setInternalErrorHandler( $this->_internalErrorHandler ); ';
    $returnOutput .= '$tpl->setWebRoot( $this->_webRoot ); ';
    $returnOutput .= '$tpl->setCacheFolder( $this->_cacheFolder ); ';
    $returnOutput .= '$tpl->setTemplateFolder( $this->_templateFolder ); ';
    $returnOutput .= '$tpl->setCacheActive( $this->_cacheActive ); ';
    $returnOutput .= '$tpl->setCacheExpirationTime( $this->_cacheExpirationTime ); ';
    $returnOutput .= '$tpl->setCompiledFilesExpirationTime( $this->_compilatedExpirationTime ); ';
    $returnOutput .= '$tpl->setTemplatePath( $this->_templatePath ); ';
    $returnOutput .= '$tpl->set( $this->_values ); ';
    // Optional class paremeters
    if ($this->_entitiesPath && $this->_entitiesPath != '') {
      $returnOutput .= '$tpl->setEntitiesPath( $this->_entitiesPath ); ';
    }
    if ($this->_widgetsPath && $this->_widgetsPath != '') {
      $returnOutput .= '$tpl->setWidgetsPath( $this->_widgetsPath ); ';
    }
    if ($this->_lang && !empty($this->_lang)) {
      $returnOutput .= '$tpl->setLang( $this->_lang ); ';
    }
    if ($this->_finalRoutingResponse && !empty($this->_finalRoutingResponse)) {
      $returnOutput .= '$tpl->setInnerTemplateRouteDetails( $this -> _finalRoutingResponse ); ';
    }
    // Cache unique identifier is inherited if defined
    if ($this->_cacheExt != NULL) {
      $returnOutput .= 'echo $tpl->render("' . $templateName . '", "' . $this->_cacheExt . '");';
    } else {
      $returnOutput .= 'echo $tpl->render("' . $templateName . '");';
    }
    $returnOutput .= "?>\n";
    return $returnOutput;
  }

  /**
   * Internal method used by extractSingleVar and extractArrayVar to apply a modifier
   *
   * List of supported php modifiers :
   * ucfirst - strtolower - strtoupper - ucwords
   * n2lbr - strlen - str_word_count - strip_tags
   * htmlspecialchars - addslashes - stripslashes
   * htmlentities
   *
   * Custom modifiers :
   * |truncateChars    =>  Cut a string according to a chars count
   * |truncateWords    =>  Cut a string according to a word count
   * |cleanUrl        =>    Makes a SEO URL friendly string
   *
   * @param string $var Variable to which we apply a modifier
   *
   * @return string
   */
  private function getModifier($var) {
    // Initialize vars
    $returnModifier = NULL;
    $returnOutput = '';
    // Separate modifier
    $outputArray = explode('|', $var);
    // Maximum number of words chars for truncate methods
    $modifier = explode(':', $outputArray[1]);
    // I have chosen to voluntarily restrict the functions which can be used by the class, in order to avoid unexpected results
    // As you can see, different modifiers can refer to the same function
    $phpFunctions = array(
      "capitalize" => "ucfirst",
      "ucfirst" => "ucfirst",
      "lowercase" => "strtolower",
      "strtolower" => "strtolower",
      "uppercase" => "strtoupper",
      "strtopupper" => "strtopupper",
      "capitalizeWords" => "ucwords",
      "ucwords" => "ucwords",
      "n2lbr" => "n2lbr",
      "lenght" => "strlen",
      "strlen" => "strlen",
      "count" => "count",
      "wordCount" => "str_word_count",
      "str_word_count" => "str_word_count",
      "strip_tags" => "strip_tags",
      "htmlspecialchars" => "htmlspecialschars",
      "addslashes" => "addslashes",
      "stripslashes" => "stripslashes",
      "htmlentities" => "htmlentities",
    );
    // If php modifier match
    if (isset($phpFunctions[$modifier[0]])) {
      $returnModifier = $phpFunctions[$modifier[0]];
      $returnOutput = $returnModifier . '(' . trim($outputArray[0]) . ')';
    } else {
      // Internal modifiers which require more than one operation
      if ($modifier[0] == "truncateChars") {
        $returnOutput = "\$this->truncateChars(" . $outputArray[0] . "," . $modifier[1] . ")";
      } else {
        if ($modifier[0] == "truncateWords") {
          $returnOutput = "\$this->truncateWords(" . $outputArray[0] . "," . $modifier[1] . ")";
        } else {
          if ($modifier[0] == "cleanUrl") {
            $returnOutput = "\$this->cleanUrl(" . $outputArray[0] . ")";
          } else {
            if ($modifier[0] == "alt") {
              $returnOutput = "\$this->altText(" . $outputArray[0] . ",\"" . $modifier[1] . "\")";
            } else {
              // Unrecognized modifier
              $this->throwError(3);
            }
          }
        }
      }
    }
    return $returnOutput;
  }

  /**
   * Core public setter for template vars
   *
   * @param    string $variable Variable to be setted
   * @param    string $value    Value to be associated with the variable
   *
   * @return    void
   */
  public function set($variable, $value = NULL) {
    if (is_array($variable)) {
      $this->_values += $variable;
    } else {
      $this->_values[$variable] = $value;
    }
  }

  /**
   * Core Public getter
   *
   * @param    string $key Variable to be returned
   *
   * @return    mixed
   */
  public function get($key) {
    return $this->values[$key];
  }

  // -------------   Extensions    -----------------
  /**
   * Core extension to handle parent-child template inheritance
   *
   * @param    string $childContent Child template content
   *
   * @return    string
   */
  private function flattenTemplate($childContent) {
    // Initialize vars
    $parentTemplateFile = NULL;
    $parentTemplateContent = '';
    $childTemplateContent = array();
    //-------------------------- Child Template Extraction
    // Explode the child template in blocks and loop through it
    $arrayOfContents = preg_split('#({\[extends\([^)]+\)]})|({\[content\([^\)]+\)\]\}.*?\{\[\/content]})#s', $childContent, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    foreach ($arrayOfContents as $key => $element) {
      // If $element is not "empty" (only space)
      if (!ctype_space($element)) {
        // #{\[extends\(([^)]+)\)\]}#s => {[extends(parentTemplate)]} non breaking space
        if (preg_match('#{\[extends\(([^)]+)\)\]}#s', $element, $output)) {
          // Remove element from output
          $element = NULL;
          // Set parent template file
          $parentTemplateFile = $output[1];
          // #{\[content\(([^\)]+)\)\]\}(.*?)\{\[\/content]}#s => {[content(id)]}block content{[/content]} non breaking space
        } else {
          if (preg_match('#{\[content\(([^\)]+)\)\]\}(.*?)\{\[\/content]}#s', $element, $output)) {
            // Check if additional parameter exists
            if (strpos($output[1], ',') !== FALSE) {
              // The additional parameter will be either 'before' or 'after' to add content to the parent
              // block instead of overriding it
              $tmpBlockParameters = explode(',', $output[1]);
              $optionalParameter = trim($tmpBlockParameters[1]);
              if ($optionalParameter == 'before' || $optionalParameter == 'after') {
                $childTemplateContent[trim($tmpBlockParameters[0])] = array(
                  trim($output[2]),
                  $optionalParameter
                );
              } else {
                // Invalid additional parameter (not before/after or override)
                $this->throwError(1);
              }
            } else {
              // No additional parameter found, child block will override parent block
              $childTemplateContent[trim($output[1])] = array(
                trim($output[2]),
                'override'
              );
            }
          } else {
            // Code found outside block tags in child template
            $this->throwError(2);
          }
        }
      }
      // End foreach arrayOfContents
    }
    //-------------------------- Parent Template Extraction
    // Load parent template file
    $parentContent = file_get_contents($this->_templateFolder . DIRECTORY_SEPARATOR . $parentTemplateFile . '.html');
    // Explode parent template in blocks
    $arrayOfBlocks = preg_split('#({\[block\([^\)]+\)\]\}.*?\{\[\/block]})|({\[block\([^\)]+\)\]\})#s', $parentContent, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    foreach ($arrayOfBlocks as $key => $element) {
      if (!ctype_space($element)) {
        // #{\[block\(([^\)]+)\)\]\}(.*?)\{\[\/block]}#s => {[block(id,overrideMethod)]}content{[/block]}
        if (preg_match('#{\[block\(([^\)]+)\)\]\}(.*?)\{\[\/block]}#s', $element, $output)) {
          // First check if an existing child block exists
          if (array_key_exists(trim($output[1]), $childTemplateContent)) {
            // Check if child block should override entirely parent content
            if ($childTemplateContent[trim($output[1])][1] == 'override') {
              $parentTemplateContent .= $childTemplateContent[trim($output[1])][0];
            } else {
              if ($childTemplateContent[trim($output[1])][1] == 'before') {
                // Otherwise, add the child block either before or after parent content
                $parentTemplateContent .= $childTemplateContent[trim($output[1])][0] . $output[2];
              } else {
                if ($childTemplateContent[trim($output[1])][1] == 'after') {
                  $parentTemplateContent .= $output[2] . $childTemplateContent[trim($output[1])][0];
                } else {
                  // Invalid additional parameter (not before/after or override)
                  $this->throwError(1);
                }
              }
            }
          } else {
            // No child block has been found, return parent content
            $parentTemplateContent .= $output[2];
          }
          // #{\[block\(([^\)]+)\)\]\}#s => {[block(id)]}
          // Shortcut for block override
        }
        if (preg_match('#{\[block\(([^\)]+)\)\]\}#s', $element, $output)) {
          if (array_key_exists(trim($output[1]), $childTemplateContent)) {
            $parentTemplateContent .= $childTemplateContent[trim($output[1])][0];
          } else {
            // No child block has been found, return nothing
          }
        } else {
          $parentTemplateContent .= $element;
        }
      } else {
        // Add element even if blank to keep some formatting
        $parentTemplateContent .= $element;
      }
    }
    return $parentTemplateContent;
  }

  /**
   * Call an entity (full MVC pattern from inside the template)
   * Not recommended as it's against MVC principles, however extends
   * the possibilities for small to medium-sized projects
   *
   * @param    string $controller Name of the entity
   * @param    string $method     Method to be called
   * @param    string $params     Method arguments
   *
   * @return    object
   *
   */
  private function callEntity($controller, $method, $params) {
    $controller = ucfirst($controller);
    $method = strtolower($method);
    if (is_dir($this->_entitiesPath)) {
      // Entity => Name_entity.php
      $targetPath = $this->_entitiesPath . DIRECTORY_SEPARATOR . $controller . '_entity.php';
      if (file_exists($targetPath)) {
        require($targetPath);
        $entity = new $controller();
        if (method_exists($entity, $method)) {
          call_user_func_array(array($entity, $method), $params);
        } else {
          // Method does not exist
          $this->throwError(4);
        }
      } else {
        // Entity does not exist
        $this->throwError(5);
      }
    } else {
      // Entities folder could not be found
      $this->throwError(6);
    }
  }

  /**
   * Call a widget (html element which can include dynamic arguments & translations)
   * Exemple :
   * Widget "HelloWorld_widget.html" content : Hello {[1]} how are {[2]} ?
   * {[widget="HelloWorld('world','you')"]}
   * Output : Hello world how are you ?
   * Works also with all WeeTemplate var tags
   *
   * @param    string $widget Widget name
   * @param    array  $mixed  Array or single var to be parsed
   *
   * @return    string
   */
  private function callWidget($widget, $params = NULL) {
    // Initialize vars
    $returnOutput = '';
    // Begin treatment
    if (is_dir($this->_widgetsPath)) {
      // File name : widget
      $targetPath = $this->_widgetsPath . DIRECTORY_SEPARATOR . strtolower($widget) . '_widget.html';
      if (file_exists($targetPath)) {
        $content = file_get_contents($targetPath);
        $contentArray = preg_split('+({\[[^\s]*]})|(\_e\(\$[^\)]*\))+i', $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $countEntry = count($params);
        $uniqueWidgetVars = array();
        // Extract {[x]}' from widget file
        // If {[x]} is used multiple times, affects it only once
        // $uniqueWidgetVars = ["{[1]}", "{[2]}"] etc..
        foreach ($contentArray as $index => $element) {
          if (preg_match('/{\[([0-9]+)\]\}/i', $element, $output)) {
            if (!in_array($element, $uniqueWidgetVars)) {
              $uniqueWidgetVars[] = $element;
            }
          }
        }
        // CountWidget = Unique number of widget vars to be replaced
        $countWidget = count($uniqueWidgetVars);
        // Check if our number of arguments in callWidget match the number of unique widget vars to be parsed
        if ($countEntry === $countWidget) {
          foreach ($contentArray as $key => $value) {
            // Widget var {\[([0-9]+)\]\} => {[x]}
            if (preg_match('/{\[([0-9]+)\]\}/i', $value, $output)) {
              print $params[$output[1] - 1];
            } // Translation \_e\(\$([^\)]*)\) => _e()
            else {
              if (preg_match('+\_e\(\$([^\)]*)\)+i', $value, $output)) {
                $this->translate($output[1]);
              } // Nothing special, return value
              else {
                print $value;
              }
            }
          }
        } else {
          // Mismatch between the number of arguments and number of widget tags
          $this->throwError(7);
        }
      } else {
        // Widget could not be found
        $this->throwError(8);
      }
    } else {
      // Widgets folder could not be found
      $this->throwError(9);
    }
  }

  /**
   * Call a core config variable (from WeeDispatcher & WeeTemplate)
   *
   * [routeName] => Matched Route Name
   * [controller] => Controller
   * [method] => Method
   * [lang] => Matched Lang
   * [templateName] => Template Name
   * [cacheActive] => Cache active
   * [cacheExpirationTime] => Cache expiration time
   *
   * @param    string $element Config element index
   *
   * @return    string
   */
  private function callConfig($element) {
    return $this->_finalRoutingResponse[$element];
  }

  /**
   * Check if current user is logged in
   *
   * @return  boolean
   */
  private function callAuthCheck() {
    return Wee()->auth->isLoggedin();
  }

  /**
   * Check if current user is in a specific group
   *
   * @param   string $group ID or group name
   *
   * @return  boolean
   */
  private function callAuthCheckGroup($group) {
    return Wee()->auth->currentUserCheckGroup($group);
  }

  /**
   * Check if current user has a specific permission
   *
   * @param   string $perm ID or permission name
   *
   * @return  boolean
   */
  private function callAuthCheckPerm($perm) {
    return Wee()->auth->currentUserCheckPermission($perm);
  }


  // -------------    Modifiers    -----------------
  /**
   * Internal Modifier to cut a string to the closest entire word and add an ellipsis
   *
   * @param    string  $string String to be truncated
   * @param    integer $chars  Maximum number of chars
   *
   * @return    string
   */
  private function truncateChars($string, $chars) {
    return current(explode("\n", wordwrap($string, $chars, "...\n")));
  }

  /**
   * Internal modifier, replace var with default content if null
   *
   * @param    string  $string             String to be verified
   * @param    integer $alternativeContent Maximum number of chars
   *
   * @return    string
   */
  private function altText($string, $alternativeContent) {
    if (!isset($string)) {
      return $alternativeContent;
    } else {
      return $string;
    }
  }

  /**
   * Internal Modifier to cut a string according to a word count and add an ellipsis
   *
   * @param    string  $string String to be truncated
   * @param    integer $words  Maximum number of words
   *
   * @return    string
   */
  private function truncateWords($string, $words) {
    $ellipsis = '...';
    $truncated = preg_replace('/((\w+\W*){' . ($words - 1) . '}(\w+))(.*)/', '${1}', $string);
    if ($truncated != $string) {
      return $truncated . $ellipsis;
    } else {
      return $string;
    }
  }

  /**
   * Internal Modifier to convert a string into a SEO friendly url-string
   *
   * @param    string $string String to be converted
   *
   * @return    string
   */
  private function cleanUrl($string) {
    $output = strtolower($string);
    $output = preg_replace('/[^a-zA-Z0-9]/i', ' ', $output);
    $output = trim($output);
    $output = preg_replace('/\s+/', ' ', $output);
    $output = preg_replace('/\s+/', '-', $output);
    return $output;
  }

  // ------------- Utility Methods -----------------
  /**
   * Internal helper method to generate either a compiled file or cached file
   *
   * @param    string $folder  Target folder
   * @param    string $file    Template location
   * @param    string $content Content to be put inside the file
   * @param    string $ext     Extension of the file
   *
   * @return    void
   */
  private function generateFile($folder, $file, $content, $ext) {
    // Create target folder if it does not exist
    if (!is_dir($folder)) {
      mkdir($folder, 0755, TRUE);
    }
    // Define target file name to put content
    $outputFile = $folder . DIRECTORY_SEPARATOR . $this->templateNameCleaner($file) . $ext;
    if ($ext == '_compiled.php') {
      // write a compiled version
      $this->_compiledFile = $outputFile;
    } else {
      // write a cached version
      $this->_cachedFile = $outputFile;
    }
    file_put_contents($outputFile, $content);
  }

  /**
   * Utility method to build specific .css / .js integration code
   * in the current template
   *
   * @return void
   */
  private function buildSpecificTemplateContent() {
    $this->extractSpecificTemplateContent($this->_headerElements, 'header');
    $this->extractSpecificTemplateContent($this->_footerElements, 'footer');
  }

  /**
   * Utility method to build specific .css / .js integration code
   * in the current template
   *
   * @param    array  $htmlElement Array to be parsed
   * @param    string $receiver    Output as html code
   *
   * @return    void
   */
  private function extractSpecificTemplateContent($htmlElement, $receiver) {
    if ($this->isTrueArray($htmlElement)) {
      $input = '';
      foreach ($htmlElement as $key => $value) {
        // File exists ?
        if (file_exists($this->_templateFolder . '/' . $value)) {
          // Javascript or css ?
          if (strpos($value, '.js') !== FALSE) {
            $input = "<?php echo '<script src=\"" . $this->_templatePath . $value . "\"></script>'; ?>" . PHP_EOL;
          } else {
            $input = "<?php echo '<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $this->_templatePath . $value . "\">'; ?>" . PHP_EOL;
          }
          // Header / Footer
          if ($receiver === 'header') {
            if ($this->_headerCustomContent == '' || $this->_headerCustomContent == NULL) {
              $this->_headerCustomContent .= $input;
            } else {
              $this->_headerCustomContent .= PHP_EOL . $input;
            }
          } else {
            if ($this->_footerCustomContent == '' || $this->_footerCustomContent == NULL) {
              $this->_footerCustomContent .= $input;
            } else {
              $this->_footerCustomContent .= PHP_EOL . $input;
            }
          }
        }
      }
    }
  }

  /**
   * Utility method to add custom integration elements to current template
   *
   * @param    array $varOrArray     Var or array of .css / .js specific integration
   * @param    array $headerOrFooter Receiver
   *
   * @return    void
   */
  private function addSpecificContent($varOrArray, $headerOrFooter) {
    // If multiple elements, map each one
    if ($this->isTrueArray($varOrArray)) {
      foreach ($varOrArray as $key => $value) {
        if ($headerOrFooter === 'header') {
          $this->_headerElements[] = $value;
        } else {
          $this->_footerElements[] = $value;
        }
      }
    } else {
      if ($headerOrFooter === 'header') {
        $this->_headerElements[] = $varOrArray;
      } else {
        $this->_footerElements[] = $varOrArray;
      }
    }
  }

  /**
   * Utility method (alias) to add custom integration elements to the header
   *
   * @param    array $varOrArray Var or array of .css / .js specific integration
   *
   * @return    void
   */
  public function addToHeader($varOrArray) {
    $this->addSpecificContent($varOrArray, 'header');
  }

  /**
   * Utility method (alias) to add custom integration elements to the footer
   *
   * @param    array $varOrArray Var or array of .css / .js specific integration
   *
   * @return    void
   */
  public function addToFooter($varOrArray) {
    $this->addSpecificContent($varOrArray, 'footer');
  }

  /**
   * Errors handler
   * Treat errors according to settings
   *
   * @param    integer $errorCode Error code to be handled
   *
   * @return    Exception/Error Message
   * @throws    TemplateException
   */
  private function throwError($errorCode) {
    if ($this->_internalErrorHandler) {
      die($this->_errors[$errorCode][1]);
    } else {
      $errorMsg = $this->_errors[$errorCode][1];
      $errorCategory = $this->_errors[$errorCode][0];
      throw new TemplateException($errorCategory, $errorMsg, $errorCode);
    }
  }

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

  /**
   * Set errors codes
   *
   * @return    void
   */
  private function setErrorCodes() {
    $this->_errors = array(
      0 => array(
        'F',
        'Error - Template File does not exists.'
      ),
      1 => array(
        'F',
        'Error - One child template block is not properly formatted (before/after).'
      ),
      2 => array(
        'F',
        'Error - Code must be encapsulated in block tags in child template.'
      ),
      3 => array('F', 'Error - An unrecognized modifier has been applied'),
      4 => array('F', 'Error - Method not found on called entity.'),
      5 => array('F', 'Error - An entity could not be found.'),
      6 => array('F', 'Error - Entities folder could not be found.'),
      7 => array(
        'F',
        'Error - Invalid number of arguments passed to a Widget.'
      ),
      8 => array('F', 'Error - Widget could not be found.'),
      9 => array('F', 'Error - Widgets folder could not be found.')
    );
  }

  /**
   * Sanitize Path (escape specials characters in system paths)
   *
   * @param    string $input String to be sanitized
   *
   * @return    string
   */
  private function sanitizePath($input) {
    return str_replace("\\", "/", $input);
  }

  /**
   * Rename template file path
   *
   * @param    string $filepath Given filepath to be renamed
   *
   * @return    string
   */
  private function templateNameCleaner($filepath) {
    $parsedName = str_replace('/', '_', $filepath);
    return $parsedName;
  }

  /**
   * Internal functions to hanle translations
   *
   * @param    string $value Var to be translated
   *
   * @return    string
   */
  private function translate($value) {
    if ($this->isTrueArray($this->_lang) && isset($this->_lang[$value])) {
      print $this->_lang[$value];
    } else {
      print $value;
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

  /**
   * Setter Web Root system path
   *
   * @param string $templateFolder Web Root Folder
   */
  public function setWebRoot($webRoot) {
    $this->_webRoot = $this->sanitizePath($webRoot);
  }

  /**
   * Getter Web Root
   * @return string
   */
  public function getWebRoot() {
    return $this->_webRoot;
  }

  /**
   * Setter Cache and Compiled Folders
   *
   * @param string $cacheFolder Cache Folder
   */
  public function setCacheFolder($cacheFolder) {
    $this->_cacheFolder = $cacheFolder;
    $this->_frontCacheFolder = $cacheFolder . DIRECTORY_SEPARATOR . 'cache';
    $this->_compiledFolder = $cacheFolder . DIRECTORY_SEPARATOR . 'compiled';
  }

  /**
   * Getter Cache and Compiled Folders
   * @return array
   */
  public function getCacheFolder() {
    return $this->_cacheFolder;
  }

  /**
   * Setter Template Folder
   *
   * @param string $templateFolder Template Folder
   */
  public function setTemplateFolder($templateFolder) {
    $this->_templateFolder = $this->sanitizePath($templateFolder);
  }

  /**
   * Getter template folder
   * @return string
   */
  public function getTemplateFolder() {
    return $this->_templateFolder;
  }

  /**
   * Setter Cache Activation
   *
   * @param boolean $cacheActivation Cache Activation
   */
  public function setCacheActive($cacheActivation) {
    $this->_cacheActive = $cacheActivation;
  }

  /**
   * Getter cache activation
   * @return boolean
   */
  public function getCacheActive() {
    return $this->_cacheActive;
  }

  /**
   * Setter Cache expiration time
   *
   * @param integer $cacheExpirationTime Cache Expiration Time
   */
  public function setCacheExpirationTime($cacheExpirationTime) {
    $this->_cacheExpirationTime = $cacheExpirationTime;
  }

  /**
   * Getter cache expiration time
   * @return integer
   */
  public function getCacheExpirationTime() {
    return $this->_cacheExpirationTime;
  }

  /**
   * Setter Compilated files expiration time
   *
   * @param integer $compilatedExpirationTime Cache Expiration Time
   */
  public function setCompiledFilesExpirationTime($compilatedExpirationTime) {
    $this->_compilatedExpirationTime = $compilatedExpirationTime;
  }

  /**
   * Getter compilated files expiration time
   * @return integer
   */
  public function getCompiledFilesExpirationTime() {
    return $this->_compilatedExpirationTime;
  }

  /**
   * Setter Template web path
   *
   * @param string $templatePath Template Path for integration purpose
   */
  public function setTemplatePath($templatePath) {
    $this->_templatePath = $templatePath;
  }

  /**
   * Setter lang array for translation purpose
   *
   * @param array $lang Lang array
   */
  public function setLang($lang) {
    $this->_lang = $lang;
  }

  /**
   * Public setter to configure Entities path
   *
   * @param string $path Entities path
   */
  public function setEntitiesPath($path) {
    $this->_entitiesPath = $path;
  }

  /**
   * Public setter to configure Widgets path
   *
   * @param string $path Widgets path
   */
  public function setWidgetsPath($path) {
    $this->_widgetsPath = $path;
  }

  /**
   * Public setter to configure Template extension
   *
   * @param string $path Widgets path
   */
  public function setTplExt($ext) {
    $this->_tplExt = $ext;
  }

  /**
   * Setter optional route details from WeePHP Framework (WeeDispatcher)
   *
   * @param array $routeConfig Lang array
   *
   * Elements :
   * [routeName] => Matched Route Name
   * [controller] => Controller
   * [method] => Method
   * [lang] => Matched Lang
   * [templateName] => Template Name
   * [cacheActive] => Cache active
   * [cacheExpirationTime] => Cache expiration time
   */
  public function setRouteDetails($routeConfig) {
    $this->_finalRoutingResponse['routeName'] = $routeConfig[0];
    $this->_finalRoutingResponse['controller'] = $routeConfig[1];
    $this->_finalRoutingResponse['method'] = $routeConfig[2];
    $this->_finalRoutingResponse['args'] = $routeConfig[3];    
    $this->_finalRoutingResponse['lang'] = $routeConfig[4];
    $this->_finalRoutingResponse['templateName'] = $this->_templateName;
    $this->_finalRoutingResponse['cacheActive'] = $this->_cacheActive;
    $this->_finalRoutingResponse['cacheExpirationTime'] = (string) $this->_cacheExpirationTime;
  }

  // Include specific method
  public function setInnerTemplateRouteDetails($routeConfig) {
    $this->_finalRoutingResponse = $routeConfig;
  }
}
