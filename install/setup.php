<?php
/**
 * Created by PhpStorm.
 * User: Hugues
 * Date: 14/12/2014
 * Time: 18:03
 */
// Setup vars
$location = $_POST['location'];
if (preg_match("/https?/", $location) == 0) {
  $location = 'http://' . $location;
}
$location = rtrim($location, "/") . "/";
$encryption = $_POST['encryption'];
$language = $_POST['language'];
$emailfrom = $_POST['emailfrom'];
$passwordcfrm = $_POST['passwordcfrm'];
$login = $_POST['login'];
$password = $_POST['password'];
$dbhost = $_POST['dhost'];
$dbuser = $_POST['dlogin'];
$dbpassword = $_POST['dpassword'];
$dbname = $_POST['dname'];
$dbprefix = 'wee';
// Check db connection
$link = @mysqli_connect($dbhost, $dbuser, $dbpassword);
if (!$link) {
  $message = 'Failed to connect to the server: ' . mysqli_connect_error();
  echo '{ "message": "' . $message . '" }';
  exit;
}
// Check database name
if (!@mysqli_select_db($link, $dbname)) {
  $message = 'Failed to connect to the database: ' . mysqli_error($link);
  echo '{ "message": "' . $message . '" }';
  exit;
}
// Change chmod 
chmod("../core/config", 0777);
// Write DB Config file
$myFile = '../core/config/db.conf.php';
$handle = fopen($myFile, 'w');
fwrite($handle, '<?php' . PHP_EOL . '// Wee PHP Framework' . PHP_EOL . PHP_EOL . '// DB Constants' . PHP_EOL . 'define("DB_HOST", "' . $dbhost . '");' . PHP_EOL . 'define("DB_NAME", "' . $dbname . '");' . PHP_EOL . 'define("DB_USERNAME", "' . $dbuser . '");' . PHP_EOL . 'define("DB_PASSWORD", "' . $dbpassword . '");' . PHP_EOL . PHP_EOL . '// Core user libraries DB tables' . PHP_EOL . 'define("DB_PREFIX", "' . $dbprefix . '");' . PHP_EOL . 'define("DB_CONFIG_TABLE_NAME", "config");' . PHP_EOL . 'define("DB_GALLERY_TABLE_NAME", "gallery");' . PHP_EOL . 'define("DB_GALLERY_PICS_TABLE_NAME", "pics");' . PHP_EOL . 'define("DB_LANGS_TABLE_NAME", "dblanguages");' . PHP_EOL . 'define("DB_USERS_TABLE_NAME", "users");' . PHP_EOL . 'define("DB_GROUPS_TABLE_NAME", "groups");' . PHP_EOL . 'define("DB_PERMISSIONS_TABLE_NAME", "permissions");' . PHP_EOL . 'define("DB_SESSIONS_TABLE_NAME", "sessions");');
fclose($handle);
// Write Framework Config file
$myFile = '../core/config/config.conf.php';
$handle = fopen($myFile, 'w');
fwrite($handle, '<?php' . PHP_EOL . '// Wee PHP Framework' . PHP_EOL . PHP_EOL . '// Config constants' . PHP_EOL . 'define("WEB_ROOT", "' . $location . '");' . PHP_EOL . 'define("DEFAULT_LANGUAGE", "' . $language . '");' . PHP_EOL . PHP_EOL . '// Misc' . PHP_EOL . 'define("ENCRYPTION_KEY", "' . $encryption . '");' . PHP_EOL . 'define("CLIENT_ERROR_PAGE", WEB_ROOT . "error.html");' . PHP_EOL . 'define("LOG_DEVELOPMENT_STATS", FALSE);' . PHP_EOL . 'define("WEBSITE_EMAIL_FROM", "' . $emailfrom . '");');
fclose($handle);
// Reset chmod 
chmod("../core/config", 0755);
// Create Tables and super user
mysqli_query($link, "CREATE TABLE IF NOT EXISTS `weesessions` (
`id` varchar(32) NOT NULL,
  `access` int(10) unsigned DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
mysqli_query($link, "CREATE TABLE IF NOT EXISTS `weeusers` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reminder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activated` tinyint(1) unsigned DEFAULT NULL,
  `blocked` tinyint(1) unsigned DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lastseen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastupdated` datetime DEFAULT NULL,
  `psswdtoken` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `psswdtokenverified` tinyint(1) unsigned DEFAULT NULL,
  `lasupdated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=6 ;");
$salt = '$2y$11$' . substr(md5(uniqid(rand(), TRUE)), 0, 22);
$password = crypt($password, $salt);
mysqli_query($link, "INSERT INTO `weeusers` (`id`, `username`, `password`, `firstname`, `lastname`, `email`, `reminder`, `activated`, `blocked`, `created`, `lastseen`, `token`, `lastupdated`, `psswdtoken`, `psswdtokenverified`, `lasupdated`) VALUES
('', '" . $login . "', '" . $password . "', NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);");
echo '{ "message": "success" }';