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
 * WeeDBGenericAccess
 *
 * Handle generic db operations through RB
 *
 * @access        Private
 * @version       0.1
 */
class WeeDBGenericAccess {
  /**
   * Current Language
   * @var string $_lang ;
   */
  protected $_lang;
  /**
   * Database langs table
   * @var string $_dbLangsTable
   */
  protected $_dbLangsTable;

  /**
   * Class constructor
   *
   * @return    WeeDBGenericAccess
   */
  public function __construct() {
    $this->_lang = Wee()->config->fetch('lang');
    $this->_lang;
  }

  //////////////////////////////////////////////////////
  // CRUD OPERATIONS
  // Insert, update, delete
  //////////////////////////////////////////////////////
  /**
   * Insert method
   *
   * @param    string  $table   Object to be stored
   * @param    mixed   $columns Columns affected
   * @param    mixed   $values  Values to be stored
   * @param    boolean $bypass  Optional - bypass columns extraction
   *
   * @return    void
   */
  public function insert($table, $columns, $values, $bypass = FALSE) {
    // By default, look for translations
    if (!$bypass) {
      // Columns extraction for translation purpose
      $translatedVars = $this->translateColumns($columns, $values);
      $columns = $translatedVars['columns'];
      $values = $translatedVars['values'];
    }
    $count = 0;
    $table = strtolower($table);
    // If sub element = Array, then proceed recursively
    if ($this->isTrueArray($values[0])) {
      $count = count($values);
    }
    // Multiple inserts
    if ($count > 1) {
      for ($i = 0; $i < $count; $i++) {
        $object = $id = NULL;
        $object = R::dispense($table);
        if (!$this->isTrueArray($columns)) {
          $object->$columns = $values[$i][0];
        } else {
          foreach ($columns as $key => $column) {
            $object->$column = $values[$i][$key];
          }
        }
        R::store($object);
      }
    } else {
      // Signle insert
      $object = R::dispense($table);
      // One column
      if (!$this->isTrueArray($columns)) {
        $object->$columns = $values;
      } else {
        foreach ($columns as $key => $column) {
          $object->$column = $values[$key];
        }
      }
      R::store($object);
    }
  }

  /**
   * Update method
   *
   * @param    string  $table      Object to be stored
   * @param    mixed   $columns    Columns affected
   * @param    mixed   $values     Values to be stored
   * @param    mixed   $conditions Conditions
   * @param    boolean $bypass     Optional - bypass columns extraction
   *
   * @return    void
   */
  public function update($table, $columns, $values, $conditions, $bypass = FALSE) {
    // Look for translated columns, by default bypass is false
    // Bypass allows a slight gain in performance
    if (!$bypass) {
      // Columns extraction for translation purpose
      $translatedVars = $this->translateColumns($columns, $values);
      $columns = $translatedVars['columns'];
      $values = $translatedVars['values'];
    }
    // Buildy query condition
    $queryCondition = $this->getCondition($conditions);
    $table = strtolower($table);
    $j = 0;
    // Init query statement
    $queryOperation = 'SET ';
    // Multiple columns
    if ($this->isTrueArray($columns)) {
      foreach ($columns as $key => $column) {
        $value = $values[$key];
        $delimiter = '';
        if (!is_int($value)) {
          $value = "'" . $value . "'";
        }
        if ($j !== count($columns) && $j !== 0) {
          $delimiter = ', ';
        }
        $queryOperation .= $delimiter . $column . '=' . $value;
        $j++;
      }
    } else {
      // Single column
      $value = $values;
      if (!is_int($value)) {
        $value = "'" . $value . "'";
      }
      $queryOperation .= $columns . '=' . $value;
    }
    if ($queryCondition != "") {
      $queryCondition = 'WHERE ' . $queryCondition;
    }
    $finalyQuery = 'UPDATE ' . $table . ' ' . $queryOperation . ' ' . $queryCondition;
    R::exec($finalyQuery);
  }

  /**
   * Delete a row / set of rows according to a condition / set of conditions
   *
   * @param    string $table      Table
   * @param    mixed  $conditions Conditions
   *
   * @return    void
   */
  public function delete($table, $conditions = NULL) {
    if ($conditions != NULL) {
      $query = 'DELETE FROM ' . strtolower($table) . ' WHERE ' . $this->getCondition($conditions);
    } else {
      $query = 'DELETE * FROM ' . strtolower($table);
    }
    R::Exec($query);
  }

  //////////////////////////////////////////////////////
  // FETCH METHODS
  // Fetch, single row, multiple rows, column(s), cell
  //////////////////////////////////////////////////////
  /**
   * Generic fetch method, accepts common SQL operators
   * Works as an array representation
   *
   * @param    string  $table         Table
   * @param    array   $where         Where (condition or set of conditions)
   * @param    array   $orderby       Order by (directly as a string)
   * @param    array   $limit         Limit (directly as a string)
   * @param    boolean $forceLanguage Retrieve a specific language
   *
   * @return    array
   */
  public function fetch($table, $where = NULL, $orderby = NULL, $limit = NULL, $forceLanguage = FALSE) {
    $query = 'SELECT * FROM ' . strtolower($table) . ' ';
    if ($where != NULL) {
      $query .= 'WHERE ' . $this->getCondition($where) . ' ';
    }
    if ($orderby != NULL) {
      $query .= 'ORDER BY ' . $orderby . ' ';
    }
    if ($limit != NULL) {
      $query .= 'LIMIT ' . $limit;
    }
    $rows = R::GetAll($query);
    if ($forceLanguage !== NULL) {
      return $this->extractResults($rows, $forceLanguage);
    } else {
      return $rows;
    }
  }

  /**
   * Fetch a single cell from a table
   *
   * @param    string $table      Table
   * @param    string $column     Column name
   * @param    mixed  $conditions Conditions
   *
   * @return    string
   */
  public function fetchCell($table, $column, $conditions) {
    $query = 'SELECT ' . strtolower($column) . ' FROM ' . strtolower($table) . ' WHERE ' . $this->getCondition($conditions) . ' LIMIT 1';
    $cell = R::getCell($query);
    return $cell;
  }

  /**
   * Fetch a row from a table
   *
   * @param    string  $table         Table
   * @param    mixed   $conditions    Conditions
   * @param    boolean $forceLanguage Retrieve a specific language
   *
   * @return    array
   */
  public function fetchRow($table, $conditions, $forceLanguage = FALSE) {
    $query = 'SELECT * FROM ' . strtolower($table) . ' WHERE ' . $this->getCondition($conditions) . ' LIMIT 1';
    $row = R::getRow($query);
    if ($forceLanguage !== NULL) {
      return $this->extractResults($row, $forceLanguage);
    } else {
      return $row;
    }
  }

  /**
   * Fetch a column from a table
   *
   * @param    string $table  Table
   * @param    string $column Column
   *
   * @return    array
   */
  public function fetchColumn($table, $column) {
    $query = 'SELECT ' . strtolower($column) . ' FROM ' . strtolower($table);
    $column = R::getCol($query);
    return $column;
  }

  /**
   * Get last inserted row
   *
   * @param    string  $table         Table
   * @param    boolean $forceLanguage Retrieve a specific language
   *
   * @return    array
   */
  public function lastRow($table, $forceLanguage = FALSE) {
    $query = 'SELECT * FROM ' . strtolower($table) . ' ORDER BY id DESC LIMIT 1';
    $row = R::getRow($query);
    if ($forceLanguage !== NULL) {
      return $this->extractResults($row, $forceLanguage);
    } else {
      return $row;
    }
  }

  //////////////////////////////////////////////////////
  // MISCELLANEOUS METHODS
  // Counting, query conditions, query, last ID
  //////////////////////////////////////////////////////
  /**
   * Extract a condition from an array
   *
   * @param    array $conditions Single or array of conditions
   *
   * @return    string
   */
  private function getCondition($conditions) {
    $queryCondition = '';
    $i = 0;
    // Multiple conditions
    if ($this->isTrueArray($conditions[0])) {
      foreach ($conditions as $condition) {
        $conditionValue = $condition[2];
        $conditionAndOr = '';
        if (!is_int($conditionValue)) {
          $conditionValue = "'" . $conditionValue . "'";
        }
        if ($i !== count($conditions) && $i !== 0) {
          // By default add AND operator, otherwise retrieve 4
          if (array_key_exists(3, $condition)) {
            $conditionAndOr = trim($condition[3]) . ' ';
          } else {
            $conditionAndOr = 'AND ';
          }
        } else {
          $conditionAndOr = '';
        }
        $queryCondition .= $conditionAndOr . $condition[0] . " " . $condition[1] . " " . $conditionValue . " ";
        $i++;
      }
    } else {
      // Single condition
      $conditionValue = $conditions[2];
      if (!is_int($conditionValue)) {
        // Add quote for string values
        $conditionValue = "'" . $conditionValue . "'";
      }
      $queryCondition .= $conditions[0] . " " . $conditions[1] . " " . $conditionValue . " ";
    }
    return $queryCondition;
  }

  /**
   * Get last inserted row ID
   *
   * @param    string $table Table
   *
   * @return    integer
   */
  public function lastID($table) {
    $query = 'SELECT MAX(Id) FROM ' . strtolower($table);
    $id = R::getCell($query);
    return $id;
  }

  /**
   * Exec sql query through RB
   *
   * @param    string $query SQL query
   *
   * @return    mixed
   */
  public function query($query) {
    R::exec($query);
  }

  /**
   * Exec sql count query through RB
   *
   * @param    string $table      Target table
   * @param    mixed  $conditions Optional conditions
   *
   * @return    integer
   */
  public function countRows($table, $conditions = NULL) {
    $query = 'SELECT COUNT(*) as nb FROM ' . strtolower($table);
    if ($conditions !== NULL) {
      $query = $query . ' WHERE ' . $this->getCondition($conditions);
    }
    $result = R::getAll($query);
    return $result[0]["nb"];
  }

  /**
   * Exec sql column count query through RB
   *
   * @param    string $column     Column name
   * @param    string $table      Target table
   * @param    mixed  $conditions Optional conditions
   *
   * @return    integer
   */
  public function countColumn($column, $table, $conditions = NULL) {
    $query = 'SELECT COUNT(' . strtolower($column) . ') as nb FROM ' . strtolower($table);
    if ($conditions !== NULL) {
      $query = $query . ' WHERE ' . $this->getCondition($conditions);
    }
    $result = R::getAll($query);
    return $result[0]["nb"];
  }

  /**
   * Exec sql distinct column count query through RB
   *
   * @param    string $column     Column name
   * @param    string $table      Target table
   * @param    mixed  $conditions Optional conditions
   *
   * @return    integer
   */
  public function countDistinctColumn($column, $table, $conditions = NULL) {
    $query = 'SELECT COUNT(DISTINCT ' . strtolower($column) . ') as nb FROM ' . strtolower($table);
    if ($conditions !== NULL) {
      $query = $query . ' WHERE ' . $this->getCondition($conditions);
    }
    $result = R::getAll($query);
    return $result[0]["nb"];
  }

  //////////////////////////////////////////////////////
  // ORM RELATED METHODS
  // Parents-children relationships
  //////////////////////////////////////////////////////
  /**
   * Retrieve children from an object ID
   *
   * @param    string  $linkedTable   Target table
   * @param    integer $id            Row ID
   * @param    string  $childType     Child type
   * @param    string  $sort          Optional sort parameter
   * @param    boolean $forceLanguage Retrieve a specific language
   *
   * @return  array
   */
  public function getChildren($linkedTable, $id, $childType, $sort, $forceLanguage = FALSE) {
    $item = R::load($linkedTable, $id);
    $sort = ($sort !== NULL ? $sort : 'id DESC');
    $children = NULL;
    if (!$item->isEmpty()) {
      $ownedChildrenList = 'own' . ucfirst(strtolower($childType)) . 'List';
      $sharedChildrenList = 'shared' . ucfirst(strtolower($childType)) . 'List';
      $ownedChildren = $item->with(' ORDER BY ' . $sort)->{$ownedChildrenList};
      $sharedChildren = $item->with(' ORDER BY ' . $sort)->{$sharedChildrenList};
      if (count($ownedChildren) > 0) {
        $children = $ownedChildren;
      } else {
        $children = $sharedChildren;
      }
      if (count($children) != 0) {
        $children = R::exportAll($children);
      }
    }
    if ($forceLanguage !== NULL && $children != NULL) {
      return $this->extractResults($children, $forceLanguage);
    } else {
      return $children;
    }
  }

  /**
   * Alias to Retrieve one child
   *
   * @param    string  $linkedTable   Target table
   * @param    integer $id            Row ID
   * @param    string  $childType     Child type
   * @param    integer $childID       Child ID
   * @param    boolean $forceLanguage Retrieve a specific language
   *
   * @return  array
   */
  public function getChild($linkedTable, $id, $childType, $childID, $forceLanguage = FALSE) {
    $item = R::load($linkedTable, $id);
    $child = NULL;
    if (!$item->isEmpty()) {
      $ownedChildrenList = 'own' . ucfirst(strtolower($childType)) . 'List';
      $sharedChildrenList = 'shared' . ucfirst(strtolower($childType)) . 'List';
      $ownedChild = $item->{$ownedChildrenList}[$childID];
      $sharedChild = $item->{$sharedChildrenList}[$childID];
      if (count($ownedChild) > 0) {
        $child = $ownedChild;
      } else {
        $child = $sharedChild;
      }
      if (!empty($child)) {
        $child = R::exportAll($child);
      }
    }
    if ($forceLanguage !== NULL && $child != NULL) {
      return $this->extractResults($child, $forceLanguage);
    } else {
      return $child;
    }
  }

  /**
   * Add child object to an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    string  $childType   Child type
   * @param    array   $columns     Columns / values association
   * @param    array   $values      Columns / values association
   * @param    boolean $om          One to many / many to many
   * @param    boolean $bypass      Optional - bypass columns extraction
   *
   * @return    integer
   */
  public function addChild($linkedTable, $id, $childType, $columns, $values, $om = TRUE, $bypass = FALSE) {
    // By default, look for translations
    if (!$bypass) {
      // Columns extraction for translation purpose
      $translatedVars = $this->translateColumns($columns, $values);
      $columns = $translatedVars['columns'];
      $values = $translatedVars['values'];
    }
    $item = R::load($linkedTable, $id);
    $childrenList = ($om ? 'own' . ucfirst(strtolower($childType)) . 'List' : 'shared' . ucfirst(strtolower($childType)) . 'List');
    if (!$item->isEmpty()) {
      $childObject = R::dispense(strtolower($childType));
      if ($this->isTrueArray($columns)) {
        for ($i = 0; $i < count($columns); $i++) {
          $childObject->$columns[$i] = $values[$i];
        }
      } else {
        $childObject->$columns = $values;
      }
      $item->{$childrenList}[] = $childObject;
      $id = R::store($item);
      return $id;
    }
  }

  /**
   * Remove child from an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    string  $childType   Child Type
   * @param    string  $childTypeID rating ID to be removed
   *
   * @return    void
   */
  public function removeChild($linkedTable, $id, $childType, $childTypeID = NULL) {
    $item = R::load($linkedTable, $id);
    if (!$item->isEmpty()) {
      $ownedChildrenList = 'xown' . ucfirst(strtolower($childType)) . 'List';
      $sharedChildrenList = 'shared' . ucfirst(strtolower($childType)) . 'List';
      if ($childTypeID == NULL) {
        $item->{$ownedChildrenList} = array();
        $item->{$sharedChildrenList} = array();
      } else {
        unset($item->{$ownedChildrenList}[$childTypeID]);
        unset($item->{$sharedChildrenList}[$childTypeID]);
      }
      R::store($item);
    }
  }

  /**
   * Alias to remove all children for a given object
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    string  $childType   Child Type
   *
   * @return    void
   */
  public function removeChildren($linkedTable, $id, $childType) {
    $this->removeChild($linkedTable, $id, $childType);
  }

  //////////////////////////////////////////////////////
  // TRANSLATIONS
  // Convert and extract columns' translations
  //////////////////////////////////////////////////////
  /**
   * Extract columns if translations is needed
   *
   * @param    array $columns Columns to be extracted
   * @param    array $values  Values to be extracted
   *
   * @return    array
   */
  private function translateColumns($columns, $values) {
    if ($this->isTrueArray($columns)) {
      // Multiple columns check
      foreach ($columns as $key => $column) {
        // If field is xxx::fieldname, extract the language
        if (strpos($column, '::') !== FALSE) {
          $translate = TRUE;
          // Extract the var name (no regex for performance issues)
          $var = substr($column, (strpos($column, '::') + 2), (strlen($column) - (strpos($column, '::') + 2)));
          // Extract the lang as a suffix
          $suffix = substr($column, 0, strpos($column, '::'));
          // If suffix === Lang => look for framework current setted language
          if ($suffix === 'lang') {
            if ($this->_lang !== 'default') {
              $suffix = $this->_lang;
            } else {
              // Language is default, no need to create a new column
              $originalvar = $var;
              $translate = FALSE;
            }
          }
          // Build new column name as var_lang
          $translatedcol = $var . '_' . $suffix;
          $output = (isset($originalvar) ? $originalvar : $translatedcol);
          // Avoid writing multiple times the same column name
          // At this point, not only we set the translated column name & value
          // but also affect the same value to the column name without suffix
          // to facilitate db transaction
          if (!in_array($var, $columns)) {
            $columns[$key] = $var;
            if ($translate) {
              // If a language must be setted, add a new column var
              $columns[] = $output;
              // Store the lang in a table (bypass translations extractions)
              $availableLanguages = R::getAll('SELECT * FROM ' . $this->_dbLangsTable);
              if (!$this->inArrayRec($suffix, $availableLanguages)) {
                $this->insert($this->$_dbLangsTable, 'lang', $suffix, TRUE);
              }
              // If multiple inserts, add new value (associated with $colums[] = $output) to each array
              if ($this->isTrueArray($values[0])) {
                foreach ($values as $mykey => $value) {
                  $values[$mykey][] = $values[$mykey][$key];
                }
              } else {
                // Or just a single array
                $values[] = $values[$key];
              }
            }
          } else {
            // Field with language suffix already exists
            // Unset current key to avoid columns duplication
            unset($columns[$key]);
            $columns[] = $output;
            // Store the lang in a table (bypass translations extractions)
            $availableLanguages = R::getAll('SELECT * FROM ' . $this->_dbLangsTable);
            if (!$this->inArrayRec($suffix, $availableLanguages)) {
              $this->insert($this->$_dbLangsTable, 'lang', $suffix, TRUE);
            }
            if ($this->isTrueArray($values[0])) {
              foreach ($values as $myotherkey => $singleval) {
                // Create new value at the end
                $values[$myotherkey][] = $values[$myotherkey][$key];
                // And unset current key
                unset($values[$myotherkey][$key]);
              }
            } else {
              $values[] = $values[$key];
              unset($values[$key]);
            }
          }
        }
      }
    } else {
      // Same workflow but for a single column to be translated (rare case)
      if (strpos($columns, '::') !== FALSE) {
        $translate = TRUE;
        $var = substr($columns, (strpos($columns, '::') + 2), (strlen($columns) - (strpos($columns, '::') + 2)));
        $suffix = substr($columns, 0, strpos($columns, '::'));
        if ($suffix === 'lang') {
          if ($this->_lang !== 'default') {
            $suffix = $this->_lang;
          } else {
            $originalvar = $var;
            $translate = FALSE;
          }
        }
        $translatedcol = $var . '_' . $suffix;
        $output = (isset($originalvar) ? $originalvar : $translatedcol);
        $outputCols = array();
        // We can use [0] as there will be only 2 columns (without suffix and with suffix)
        $outputCols[0] = $var;
        if ($translate) {
          $outputCols[] = $output;
          $availableLanguages = R::getAll('SELECT * FROM ' . $this->_dbLangsTable);
          if (!$this->inArrayRec($suffix, $availableLanguages)) {
            $this->insert($this->$_dbLangsTable, 'lang', $suffix, TRUE);
          }
          if ($this->isTrueArray($values[0])) {
            // One column, multiple inserts
            foreach ($values as $mykey => $value) {
              $values[$mykey][] = $values[$mykey][0];
            }
          } else {
            $values[] = $values[0];
          }
        }
        $columns = $outputCols;
      }
    }
    // Return a combination of columns and value
    return array('columns' => $columns, 'values' => $values);
  }

  /**
   * Make sure the default column name fits the current setted framework language, unless otherwise defined
   * Cycle through arrays and sub arrays
   *
   * @param    array $array         Table
   * @param    mixed $forceLanguage Force the query to retrieve a specific language if this one exists
   *
   * @return    array
   */
  private function extractResults($array, $forceLanguage) {
    // Multiple rows
    if ($this->isTrueArray($array[0])) {
      foreach ($array as $key => $subarray) {
        $array[$key] = $this->extractResultsArray($array[$key], $forceLanguage);
      }
    } else {
      // Single row
      $array = $this->extractResultsArray($array, $forceLanguage);
    }
    return $array;
  }

  /**
   * Concrete translation retrieving method
   * Basically, languages are handled as following :
   *  1 - Force language = null : get the framework language (either as defined in the route or as default setting)
   *  2 - Force language = false : do not execute translations operations (strongly advised for monolanguage websites)
   *  3 - Force language = en : will check if "colum_en" exists in the target table, else selects default framework language
   *
   * @param    array $array         Table (as a reference)
   * @param    mixed $forceLanguage Force the query to retrieve a specific language if this one exists
   *
   * @return    array
   */
  private function extractResultsArray(&$array, $forceLanguage) {
    // Retrieve db supported languages (outside the loop for performance issues)
    $availableLanguages = R::getAll('SELECT * FROM ' . $this->_dbLangsTable);
    // Begin extraction
    foreach ($array as $key => $value) {
      if ($this->isTrueArray($availableLanguages)) {
        // Loop through all possible languages
        foreach ($availableLanguages as $langKey => $lang) {
          // Check if a lang extension exists
          $currentLang = $lang['lang'];
          $langSuffix = '_' . $currentLang;
          $frameworkLangSuffix = '_' . $this->_lang;
          $defaultKey = '';
          // Extract current value
          if ((strlen($key) - strlen($langSuffix)) > strlen($key) && strpos($key, $langSuffix, (strlen($key) - strlen($langSuffix))) != NULL) {
            // Extract current Key lang
            $currentKeyLangSuffix = substr($key, (strlen($key) - strlen($langSuffix)), strlen($langSuffix));
            // Extract current Key base var
            $currentKeyWithoutLangSuffix = substr($key, 0, (strlen($key) - strlen($langSuffix)));
            if (isset($array[$currentKeyWithoutLangSuffix])) {
              if (($forceLanguage != FALSE && $forceLanguage != NULL) && $currentKeyLangSuffix === '_' . $forceLanguage) {
                $array[$currentKeyWithoutLangSuffix] = $value;
              } else {
                if ($currentKeyLangSuffix === $frameworkLangSuffix && ($forceLanguage == FALSE && $forceLanguage == NULL)) {
                  // Current key lang suffix match framework current language setting, redefine base column value
                  // Also looks if a specific language must be retrieved
                  // Make sure base column exists
                  $array[$currentKeyWithoutLangSuffix] = $value;
                }
              }
            }
          }
        }
      }
    }
    return $array;
  }

  //////////////////////////////////////////////////////
  // TAGS SYSTEM
  // Handle basic tagging operations
  //////////////////////////////////////////////////////
  /**
   * Add tag(s) to an object
   *
   * @param    string $table Object
   * @param    string $id    Id
   * @param    mixed  $tags  Tags
   *
   * @return    void
   */
  public function addTags($table, $id, $tags) {
    $item = R::load($table, $id);
    if (!$item->isEmpty()) {
      R::addTags($item, $tags);
    }
  }

  /**
   * Remove tag(s) from an object
   *
   * @param    string $table Object
   * @param    string $id    Id
   * @param    mixed  $tags  Tags
   *
   * @return    void
   */
  public function removeTag($table, $id, $tags) {
    $item = R::load($table, $id);
    if (!$item->isEmpty()) {
      R::untag($item, $tags);
    }
  }

  /**
   * Check if an object has certain tags
   *
   * @param    string  $table Object
   * @param    string  $id    Id
   * @param    mixed   $tags  Tags
   * @param    boolean $all   All tags must be attached
   *
   * @return    boolean
   */
  public function hasTags($table, $id, $tags, $all = FALSE) {
    $item = R::load($table, $id);
    $hasTags = FALSE;
    if (!$item->isEmpty()) {
      $hasTags = R::hasTag($item, $tags, $all);
    }
    return $hasTags;
  }

  /**
   * Get all tags associated to an object
   *
   * @param    string $table Object
   * @param    string $id    Id
   *
   * @return    array
   */
  public function getTagsByObjectID($table, $id) {
    $item = R::load($table, $id);
    $tags = NULL;
    if (!$item->isEmpty()) {
      $tags = R::tag($item);
    }
    return $tags;
  }

  /**
   * Get objects tagged with an array of tags
   *
   * @param    string $table Object
   * @param    mixed  $tags  Tags
   *
   * @return  array
   */
  public function getTaggedObjects($table, $tags) {
    $taggedObjects = R::tagged($table, $tags);
    $taggedObjects = R::exportAll($taggedObjects);
    return $taggedObjects;
  }

  //////////////////////////////////////////////////////
  // COMMENT SYSTEM
  // Handle basic commenting operations
  //////////////////////////////////////////////////////
  /**
   * Retrieve comment by its id
   *
   * @param    string $id Comment id
   *
   * @return    array
   */
  public function getComment($id) {
    $comment = R::load('comment', $id);
    $comment = R::exportAll($comment);
    return $comment;
  }

  /**
   * Retrieve comments from an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    string  $sort        Optional sort parameter
   * @param    string  $condition   Optional condition parameter
   *
   * @return    array
   */
  public function getAllComments($linkedTable, $id, $sort = 'id DESC', $condition = NULL) {
    $item = R::load($linkedTable, $id);
    $sort = ($sort !== NULL ? $sort : 'id DESC');
    $comments = $item->with($condition . ' ORDER BY ' . $sort)->ownCommentList;
    $comments = R::exportAll($comments);
    return $comments;
  }

  /**
   * Add comments to an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    array   $columns     Columns / values association
   * @param    array   $values      Columns / values association
   *
   * @return    integer
   */
  public function addComment($linkedTable, $id, $columns, $values) {
    $item = R::load($linkedTable, $id);
    if (!$item->isEmpty()) {
      $comment = R::dispense('comment');
      for ($i = 0; $i < count($columns); $i++) {
        $comment->$columns[$i] = $values[$i];
      }
      $item->ownCommentList[] = $comment;
      $id = R::store($item);
      return $id;
    }
  }

  /**
   * Update comment content
   *
   * @param    integer $id      Row ID
   * @param    array   $columns Columns / values association
   * @param    array   $values  Columns / values association
   *
   * @return    integer
   */
  public function updateComment($id, $columns, $values) {
    $comment = R::load('comment', $id);
    if (!$comment->isEmpty()) {
      if ($this->isTrueArray($columns)) {
        for ($i = 0; $i < count($columns); $i++) {
          $comment->$columns[$i] = $values[$i];
        }
      } else {
        $comment->$columns = $values;
      }
    }
    $id = R::store($comment);
    return $id;
  }

  /**
   * Remove comment from an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    integer $commentID   Comment ID to be removed
   *
   * @return    void
   */
  public function removeComment($linkedTable, $id, $commentID = NULL) {
    $item = R::load($linkedTable, $id);
    if (!$item->isEmpty()) {
      if ($commentID == NULL) {
        $item->xownCommentList = array();
      } else {
        unset($item->xownCommentList[$commentID]);
      }
      R::store($item);
    }
  }

  /**
   * Alias to remove all comments
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   *
   * @return    void
   */
  public function removeAllComments($linkedTable, $id) {
    $this->removeComment($linkedTable, $id);
  }

  //////////////////////////////////////////////////////
  // RATING SYSTEM
  // Handle basic rating operations
  //////////////////////////////////////////////////////
  /**
   * Retrieve ratings from an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    string  $sort        Optional sort parameter
   * @param    string  $condition   Optional condition parameter
   *
   * @return array
   */
  public function getAllRatings($linkedTable, $id, $sort = 'id DESC', $condition = NULL) {
    $item = R::load($linkedTable, $id);
    $sort = ($sort !== NULL ? $sort : 'id DESC');
    $ratings = $item->with($condition . ' ORDER BY ' . $sort)->ownRatingList;
    $ratings = R::exportAll($ratings);
    return $ratings;
  }

  /**
   * Add ratings to an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    array   $columns     Columns / values association
   * @param    array   $values      Columns / values association
   *
   * @return    integer
   */
  public function addRating($linkedTable, $id, $columns, $values) {
    $item = R::load($linkedTable, $id);
    if (!$item->isEmpty()) {
      $rating = R::dispense('rating');
      for ($i = 0; $i < count($columns); $i++) {
        $rating->$columns[$i] = $values[$i];
      }
      $item->ownRatingList[] = $rating;
      $id = R::store($item);
      return $id;
    }
  }

  /**
   * Remove rating from an object ID
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   * @param    string  $ratingID    rating ID to be removed
   *
   * @return    void
   */
  public function removeRating($linkedTable, $id, $ratingID = NULL) {
    $item = R::load($linkedTable, $id);
    if (!$item->isEmpty()) {
      if ($ratingID == NULL) {
        $item->xownRatingList = array();
      } else {
        unset($item->xownRatingList[$ratingID]);
      }
      R::store($item);
    }
  }

  /**
   * Alias to remove all ratings
   *
   * @param    string  $linkedTable Target table
   * @param    integer $id          Row ID
   *
   * @return    void
   */
  public function removeAllRatings($linkedTable, $id) {
    $this->removeRating($linkedTable, $id);
  }


  //////////////////////////////////////////////////////
  // UTILITY METHODS
  // True array / recursive in_array search
  //////////////////////////////////////////////////////
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
   * Utility method to check recursively if a value exists in an array
   *
   * @param    string $needle   Needle
   * @param    array  $haystack Haystack
   *
   * @return    boolean
   */
  private function inArrayRec($needle, $haystack) {
    foreach ($haystack as $item) {
      if ($item == $needle || (is_array($item) && $this->inArrayRec($needle, $item))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Set database gallery table name
   *
   * @param   string $langsTable Name of the database config table
   *
   * @return  void
   */
  public function setLangsTable($langsTable) {
    $this->_dbLangsTable = $langsTable;
  }
}
