<?php
/**
 * WeePHP Framework
 *
 * Light Framework, quick development !
 *
 * @package     WeePHP\UserLibraries
 * @author      Hugues Polaert <hugues.polaert@gmail.com>
 * @link        http://www.weephp.net
 * @version     0.1
 */
// --------------------------------------------
if (!defined('SERVER_ROOT')) {
  exit('Direct access to this file is not allowed.');
}
/**
 * WeeGal
 *
 * Gallery helper class for weephp framework
 *
 * @access      Private
 * @version     0.1
 */
class WeeGal {
  /**
   * Internal error handler
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;
  /**
   * WeeImg class
   * @var object $WeeImg
   */
  protected $WeeImg;
  /**
   * WeeFile class
   * @var object $WeeFile
   */
  protected $WeeFile;
  /**
   * Database gallery table
   * @var string $_dbGalleryTable
   */
  protected $_dbGalleryTable;
  /**
   * Database gallery pics table
   * @var string $_dbGalleryPicsTable
   */
  protected $_dbGalleryPicsTable;
  /**
   * Gallery folder location
   * @var string $_galleryFolder
   */
  protected $_galleryFolder;

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param   boolean $internalErrorHandler True when used outside the framework
   *
   * @return  WeeGal
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside PXeli Framework
    $this->setInternalErrorHandler($internalErrorHandler);
    $this->WeeImg = Wee()->image;
    $this->WeeFile = Wee()->filehandler;
  }

  /**
   * Create a new album
   *
   * @param   string $name        Name of the album
   * @param   string $description Description of the album
   *
   * @return  integer
   */
  public function newAlbum($name, $description = NULL) {
    if ($name !== NULL && $name !== '') {
      $album = R::findOne($this->_dbGalleryTable, 'name = ?', array($name));
      if ($album !== NULL) {
        // Update method, name & description
        $album->name = $name;
        if ($description !== NULL) {
          $album->description = $description;
        }
        $album->lastupdated = R::isoDateTime();
        $id = R::store($album);
        return $id;
      } else {
        $folderName = $this->cleanUpString($name) . '-' . md5(time());
        if (mkdir($this->_galleryFolder . DS . $folderName, 0777, TRUE)) {
          // Else create new album
          $album = R::dispense($this->_dbGalleryTable);
          $album->name = $name;
          $album->description = $description;
          $album->created = R::isoDateTime();
          $album->lastupdated = R::isoDateTime();
          $album->lastinsertion = NULL;
          $album->location = $folderName;
          $id = R::store($album);
          return $id;
        } else {
          return NULL;
        }
      }
    }
  }

  /**
   * Retrieve all albums
   *
   * @return  array
   */
  public function fetchAlbums() {
    $albums = R::findAll($this->_dbGalleryTable);
    $albums = R::exportAll($albums);
    return $albums;
  }

  /**
   * Add a picture to an album
   *
   * @param   string  $picture     Picture name ($_Files[$name]) or location (/absolute server path)
   * @param   integer $album       Target album ID
   * @param   string  $description Picture description
   * @param   array   $params      Optional parameters
   *
   * @return  boolean
   */
  public function addPicture($picture, $album, $description, $params = NULL) {
    // Check if album exists
    $dbAlbum = R::load($this->_dbGalleryTable, $album);
    if ($dbAlbum->id !== 0) {
      if (strpos($picture, '/') !== FALSE || strpos($picture, '\\') !== FALSE) {
        $pictureName = basename($picture);
      } else {
        $pictureName = basename($_FILES[$picture]['name']);
      }
      // Generate unique name
      $targetFilename = md5(time()) . '-' . $pictureName;
      $to = $this->_galleryFolder . DS . $dbAlbum->location;
      if (strpos($picture, '/') !== FALSE || strpos($picture, '\\') !== FALSE) {
        $result = rename($picture, $to . '/' . $targetFilename);
      } else {
        $result = $this->WeeFile->uploadFiles($picture, $to, $targetFilename);
      }
      $fullsizePicLocation = $to . '/' . $targetFilename;
      $thumbName = 'th-' . $targetFilename;
      $thumbLocation = $to . '/thumbs/' . $thumbName;
      if (!is_dir($to . '/thumbs')) {
        mkdir($to . '/thumbs', 0777, TRUE);
      }
      // If height and width are both defined crop to keep ratio, helse resize with given attribute, by default crop
      if (isset($params['thumbW']) && $params['thumbW'] !== '' && isset($params['thumbH']) && $params['thumbH'] !== '') {
        $this->WeeImg->thumbCrop($fullsizePicLocation, $thumbLocation, $params['thumbW'], $params['thumbH']);
      } else {
        if (isset($params['thumbW']) && $params['thumbW'] !== '') {
          $this->WeeImg->resize($fullsizePicLocation, $thumbLocation, $params['thumbW'], 0);
        } else {
          if (isset($params['thumbH']) && $params['thumbH'] !== '') {
            $this->WeeImg->resize($fullsizePicLocation, $thumbLocation, 0, $params['thumbW']);
          } else {
            $this->WeeImg->thumbCrop($fullsizePicLocation, $thumbLocation, 150, 150);
          }
        }
      }
      if ($result) {
        // Album update and db insertion
        $picture = R::dispense($this->_dbGalleryPicsTable);
        $picture->name = $pictureName;
        $picture->description = $description;
        $picture->added = R::isoDateTime();
        $picture->location = $dbAlbum->location . '/' . $targetFilename;
        $picture->filename = $targetFilename;
        $picture->lastupdated = R::isoDateTime();
        $picture->thumblocation = $dbAlbum->location . '/thumbs/' . $thumbName;
        $picture->thumbname = $thumbName;
        // Exclusive link one-to-many
        $ownedChildrenList = 'xown' . ucfirst($this->_dbGalleryPicsTable) . 'List';
        $dbAlbum->lastinsertion = R::isoDateTime();
        $dbAlbum->{$ownedChildrenList}[] = $picture;
        $id = R::store($dbAlbum);
        return TRUE;
      } else {
        return FALSE;
      }
    } else {
      return TRUE;
    }
  }

  /**
   * Move an existing picture from one album to another
   *
   * @param   integer $pictureID Picture unique ID
   * @param   integer $albumID   Target album ID
   *
   * @return  boolean
   */
  public function movePictureToAlbum($pictureID, $albumID) {
    // Check if album exists
    $dbAlbum = R::load($this->_dbGalleryTable, $albumID);
    // Check if picture exists
    $dbPic = R::load($this->_dbGalleryPicsTable, $pictureID);
    // if both exist
    if ($dbAlbum->id !== 0 && $dbPic->id !== 0) {
      // Change picture location
      $result = rename($this->_galleryFolder . DS . $dbPic->location, $this->_galleryFolder . DS . $dbAlbum->location . '/' . $dbPic->filename);
      if (!is_dir($this->_galleryFolder . DS . $dbAlbum->location . '/thumbs')) {
        mkdir($this->_galleryFolder . DS . $dbAlbum->location . '/thumbs', 0777, TRUE);
      }
      $resultTh = rename($this->_galleryFolder . DS . $dbPic->thumblocation, $this->_galleryFolder . DS . $dbAlbum->location . '/thumbs/' . $dbPic->thumbname);
      if ($result && $resultTh) {
        // Change picture attributes
        $dbPic->location = $dbAlbum->location . DS . $dbPic->filename;
        $dbPic->thumblocation = $dbAlbum->location . DS . $dbPic->thumbname;
        // Change gallery ownership
        $ownedChildrenList = 'xown' . ucfirst($this->_dbGalleryPicsTable) . 'List';
        $dbAlbum->{$ownedChildrenList}[] = $dbPic;
        $dbAlbum->lastinsertion = R::isoDateTime();
        $id = R::store($dbAlbum);
        return TRUE;
      }
    } else {
      return FALSE;
    }
  }

  /**
   * Update a new album
   *
   * @param   string $name        Name of the album
   * @param   string $newName     New name of the album
   * @param   string $description Description of the album
   *
   * @return  integer
   */
  public function updateAlbum($name, $newName, $description = NULL) {
    $album = R::findOne($this->_dbGalleryTable, 'name = ?', array($name));
      if ($album !== NULL) {
        // Update method, name & description
        $album->name = $newName;
        if ($description !== NULL) {
          $album->description = $description;
        }
        $album->lastupdated = R::isoDateTime();
        $id = R::store($album);
        return $id;
      } else {
        return null; 
      }
  }

  /**
   * Delete an album
   *
   * @param   string $albumID ID of the album
   *
   * @return  boolean
   */
  public function deleteAlbum($albumID) {
    $dbAlbum = R::load($this->_dbGalleryTable, $albumID);
    if ($dbAlbum->id !== 0) {
      $this->WeeFile->removeDir($this->_galleryFolder . DS . $dbAlbum->location);
      R::trash($dbAlbum);
    } else {
      return FALSE;
    }
  }

  /**
   * Update the description of a given picture
   *
   * @param   integer $pictureID   Picture unique ID
   * @param   integer $description New description
   *
   * @return  boolean
   */
  public function updatePic($pictureID, $description) {
    // Check if picture exists
    $dbPic = R::load($this->_dbGalleryPicsTable, $pictureID);
    if ($dbPic->id !== 0) {
      $dbPic->description = $description;
      R::store($dbPic);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Delete a picture
   *
   * @param   integer $pictureID Picture unique ID
   *
   * @return  boolean
   */
  public function deletePic($pictureID) {
    // Check if picture exists
    $dbPic = R::load($this->_dbGalleryPicsTable, $pictureID);
    if ($dbPic->id !== 0) {
      $this->WeeFile->deleteFile($this->_galleryFolder . DS . $dbPic->thumblocation);
      $this->WeeFile->deleteFile($this->_galleryFolder . DS . $dbPic->location);
      R::trash($dbPic);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Get album total number of pictures
   *
   * @param   integer $albumID Album unique ID
   *
   * @return  integer
   */
  public function getAlbumContent($albumID) {
    // Check if picture exists
    $dbAlbum = R::load($this->_dbGalleryTable, $albumID);
    if ($dbAlbum->id !== 0) {
      // Change gallery ownership
      $ownedChildrenList = 'xown' . ucfirst($this->_dbGalleryPicsTable) . 'List';
      return R::exportAll($dbAlbum->{$ownedChildrenList});
    } else {
      return NULL;
    }
  }
  
    /**
   * Get album content
   *
   * @param   string $albumID Album unique ID
   *
   * @return  array
   */
  public function getAlbumCount($albumID) {
    // Check if album exists
    $dbAlbum = R::load($this->_dbGalleryTable, $albumID);
    if ($dbAlbum->id !== 0) {
      // Change gallery ownership
      $ownedChildrenList = 'xown' . ucfirst($this->_dbGalleryPicsTable) . 'List';
      return count($dbAlbum->{$ownedChildrenList});
    } else {
      return NULL;
    }
  }

  /**
   * Clean up a string to safely use it when naming a folder or a file
   *
   * @param   string $string Input value
   *
   * @return  boolean
   */
  private function cleanUpString($string) {
    $string = trim($string);
    $string = strtr($string, "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ", "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");
    $string = strtr($string, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz");
    $string = preg_replace('#([^.a-z0-9]+)#i', '-', $string);
    $string = preg_replace('#-{2,}#', '-', $string);
    $string = preg_replace('#-$#', '', $string);
    $string = preg_replace('#^-#', '', $string);
    return $string;
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
   * Set database gallery table name
   *
   * @param   string $galleryTable Name of the database gallery table
   *
   * @return  void
   */
  public function setGalleryTable($galleryTable) {
    $this->_dbGalleryTable = $galleryTable;
  }

  /**
   * Set database gallery pics table name
   *
   * @param   string $galleryPicsTable Name of the database gallery pics table
   *
   * @return  void
   */
  public function setGalleryPicsTable($galleryPicsTable) {
    $this->_dbGalleryPicsTable = $galleryPicsTable;
  }

  /**
   * Set gallery folder location
   *
   * @param   string $galleryFolder Location of the gallery folder
   *
   * @return  void
   */
  public function setGalleryFolder($galleryFolder) {
    $this->_galleryFolder = $galleryFolder;
    if (!file_exists($galleryFolder)) {
      mkdir($galleryFolder, 0777, TRUE);
    }
  }
}
