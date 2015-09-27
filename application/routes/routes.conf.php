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
 * routes.conf.php
 *
 * User routes, format :
 * $router -> attachUrl('RouteName', 'URL', 'controller', 'method', 'LangAssociation')
 *
 * RouteName => Unique route ID (2 different routes cannot have the same name)
 * URL => can include static or dynamic elements :
 * => Default route = '/'
 * -- Static : any char or number
 * -- Dynamic :
 * ---- @var:varName@       => Either a string or integer, will map user request to args['varName'] in controller
 * ---- @number:varName@    => Restrain input to a number, if type does not match an error will be thrown
 * ---- @string:varName@    => Restrain input to a string, same behaviour as number, var is still mapped to args['varName']
 * ------ @stringOrVarOrNumber:varName:X@    => Restrict the length of input var to a defined number
 * Controller         => Controller to be called (must be defined)
 * Method             => Method to be called (must be defined)
 * LangAssociation    => Associate a lang with the route (can be null)
 *
 */
$router->attachUrl('defaultRoute', '/', 'main', 'index', 'en');