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
 * Languages handling methods (related to PTranslate)
 */
/**
 * Utility function to list available languages within a folder
 * Look for files in langs folder, extracts 2-letter lang name in array
 *
 * @param    string $langdirectory Langs directory
 *
 * @return    array
 */
function listLanguages($langdirectory) {
  if ($handle = opendir($langdirectory)) {
    // Populates languages Array
    while (FALSE !== ($entry = readdir($handle))) {
      if (substr($entry, 0, 1) == '.') {
        continue;
      }
      // Get the lang name without the .php extension
      $lang = substr($entry, 0, -4);
      // Populates lang array
      $languages[] = $lang;
    }
    closedir($handle);
  }
  // If array is empty returns null
  if (empty($languages)) {
    return NULL;
  } else {
    return $languages;
  }
}

/**
 * Utility function to define application language
 * Works with the following order:
 * 1 - If a lang is associated with a route, select this one if language file exists
 * 2 - Otherwise, look if a lang has already been defined in a session var
 * 3 - At this point, if a lang could not be defined, set to default language (config)
 *
 * @param    string $routerLang          Lang from route
 * @param    array  $availableLanguages  Array of available languages (see method above)
 * @param    string $defaultLangFilePath Path of default lang file path
 *
 * @return    array
 */
function retrieveLang($routerLang, $availableLanguages, $defaultLangFilePath) {
  $langFound = FALSE;
  // 1st try to get lang associated with current route (if defined)
  if (!empty($routerLang)) {
    if (in_array($routerLang, $availableLanguages)) {
      $tmpFile = DIR_A_LANGS . $routerLang . '.php';
      if (file_exists($tmpFile)) {
        $_SESSION['lang'] = $routerLang;
        require $tmpFile;
        $langFound = TRUE;
      }
    }
  }
  // Then check if lang is already defined
  if ($_SESSION['lang'] != 'default' && $langFound != TRUE) {
    if (in_array($_SESSION['lang'], $availableLanguages)) {
      $tmpFile = DIR_A_LANGS . $_SESSION['lang'] . '.php';
      if (file_exists($tmpFile)) {
        require $tmpFile;
        $langFound = TRUE;
      }
    }
  }
  // Finally, try to load default language path
  if ($langFound != TRUE) {
    if (file_exists($defaultLangFilePath)) {
      require $defaultLangFilePath;
      $_SESSION['lang'] = DEFAULT_LANGUAGE;
    }
  }
  // $Lang = array within included files (application langs folder)
  return $lang;
}

/**
 * Utility function to return translated value
 *
 * @param    string $value Value to be translated
 *
 * @return    string
 */
function _t($value) {
  return Wee()->translator->translate($value);
}

/**
 * Utility function to echo translated value (shortcut)
 *
 * @param    string $value Value to be translated
 *
 * @return    void
 */
function _e($value) {
  echo _t($value);
}