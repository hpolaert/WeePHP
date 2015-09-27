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
 * Global miscelanous methods
 * Redirection methods (with/withou content sent)
 */
/**
 * Refresh
 * To use when content has already been sent
 *
 * @param    string  $location Target Location
 * @param    boolean $external External or internal redirection
 *
 * @return    string
 */
function refresh($location, $external = FALSE) {
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
function redirect($location, $external = FALSE) {
  if (!$external) {
    $location = WEB_ROOT . $location;
  }
  header('Location ' . $location . '');
}
