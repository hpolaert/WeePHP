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
 * WeeHooks
 *
 * Utility class to register hooks within the application
 * To be customised according to user discretion
 *
 * @access        Private
 * @version       0.1
 */
class WeeHooks {
  public function loadPreDispatchingHook() {
    // Not need in this example
    // Debug : echo 'predispatchinghook';
  }

  public function loadPostDispatchingHook() {
    // Not need in this example
    // Debug : echo 'postdispatchinghook';
  }

  public function loadPreApplicationHook() {
    // WeeTemplate Settings
    // Template vars (can be alternatively loaded from DB vars)
    define('TEMPLATE_NAME', 'default');
    define('TEMPLATE_WEB_PATH', WEB_ROOT . 'application/front/' . TEMPLATE_NAME . '/');
    define('TEMPLATE_DIR', DIR_A_FRONT . DS . TEMPLATE_NAME);
    define('DIR_A_WIDGETS', TEMPLATE_DIR . DS . 'widgets' . DS);
    // Debug : echo 'precontrollerhook';
    Wee()->view->setWebRoot(WEB_ROOT);
    Wee()->view->setCacheFolder(DIR_A_CACHE_FRONT);
    Wee()->view->setTemplateFolder(TEMPLATE_DIR);
    Wee()->view->setCacheActive(FALSE);
    // Cache and compiled files expiration time in minutes (0 = Rebuild cache/compiled file everytime)
    Wee()->view->setCacheExpirationTime(60);
    Wee()->view->setCompiledFilesExpirationTime(60);
    // Optional setters
    Wee()->view->setLang(Wee()->config->fetch('currLangArray'));
    Wee()->view->setTemplatePath(TEMPLATE_WEB_PATH);
    Wee()->view->setEntitiesPath(DIR_A_ENTITIES);
    Wee()->view->setWidgetsPath(DIR_A_WIDGETS);
    Wee()->view->setRouteDetails(Wee()->config->fetch('routerCfg') != NULL ? Wee()->config->fetch('routerCfg') : NULL);
    // WeeDB Settings
    Wee()->db->setLangsTable(DB_PREFIX . DB_LANGS_TABLE_NAME);
    // WeeAuth Settings
    Wee()->auth->setGroupsTable(DB_PREFIX . DB_GROUPS_TABLE_NAME);
    Wee()->auth->setUsersTable(DB_PREFIX . DB_USERS_TABLE_NAME);
    Wee()->auth->setPermissionsTable(DB_PREFIX . DB_PERMISSIONS_TABLE_NAME);
    Wee()->auth->setEmailsFrom(WEBSITE_EMAIL_FROM);
    // WeeAuth mails
    Wee()->auth->setActivationEmail(array(
      ACTIVATION_EMAIL_OBJECT,
      ACTIVATION_EMAIL_BEFORE_CONTENT,
      ACTIVATION_EMAIL_AFTER_CONTENT
    ));
    Wee()->auth->setPasswordEmail(array(
      PASSWORD_EMAIL_OBJECT,
      PASSWORD_EMAIL_BEFORE_CONTENT,
      PASSWORD_EMAIL_AFTER_CONTENT
    ));
    Wee()->auth->setUpdatePasswordEmail(array(
      PASSWORD_RESET_EMAIL_OBJECT,
      PASSWORD_RESET_BEFORE_CONTENT,
      PASSWORD_RESET_AFTER_CONTENT
    ));
    // WeeGallery Settings
    Wee()->gallery->setGalleryTable(DB_PREFIX . DB_GALLERY_TABLE_NAME);
    Wee()->gallery->setGalleryPicsTable(DB_PREFIX . DB_GALLERY_PICS_TABLE_NAME);
    Wee()->gallery->setGalleryFolder(DIR_A_FRONT_RES . DS . 'gallery');
  }

  public function loadPostApplicationHook() {
    // Not need in this example
    // Debug : echo 'postcontrollerhook';
  }
}