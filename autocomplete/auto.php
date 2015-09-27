<?php

// ! SHOULD NOT BE UPLOADED !
// Utility file for IDE to ease autocompletion
// Note: on PHPStorm this file is not required
// Eclipse/Redbean Autocomplete
/**
 * @property WeeDispatcher           $dispatcher
 * @property WeeEncrypter            $encryter
 * @property WeeLoad                 $load
 * @property WeeConfig               $config
 * @property WeeCookier              $cookie
 * @property WeeDBGenericAccess      $db
 * @property WeeFile                 $filehandler
 * @property WeeTemplate             $view
 * @property WeeGal                  $gallery
 * @property WeeAuth                 $auth
 * @property WeeMail                 $wmail
 * @property WeeFilter               $filter
 * @property WeeImg                  $image
 * @property WeePaginate             $paginate
 */
class WeeController {
  protected $dispatcher;
  protected $encrypter;
  protected $load;
  protected $config;
  protected $cookie;
  protected $db;
  protected $filehandler;
  protected $view;
  protected $gallery;
  protected $auth;
  protected $wmail;
  protected $filter;
  protected $image;
  protected $paginate;
}

// Aptana autocomplete
class _ {
  function _() {
    $this->router = new WeeDispatcher();
    $this->encrypter = new WeeEncrypter();
    $this->load = new Weeloader();
    $this->config = new WeeConfig();
    $this->cookie = new WeeCookie();
    $this->db = new WeeDBGenericAccess();
    $this->filehandler = new WeeFile();
    $this->view = new WeeTemplate();
    $this->gallery = new WeeGal();
    $this->auth = new WeeAuth();
    $this->wmail = new WeeMail();
    $this->filter = new WeeFilter();
    $this->image = new WeeImg();
    $this->paginate = new WeePaginate();
  }
}

// Extending
class WeeController extends _ {
}

class WeeModel extends _ {
}