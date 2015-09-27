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
 * WeeImage
 *
 * Manipulate img files
 *
 * @access        Private
 * @version       0.1
 */
class WeeImg {
  /**
   * Internal error handler
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeeImg
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside PXeli Framework
    $this->setInternalErrorHandler($internalErrorHandler);
  }

  /**
   * Makes a thumb from a picture and crop if necessary
   *
   * @param   mixed $fileLocation    Location of the file(s) to be resized
   * @param   mixed $fileDestination Thumb final location
   * @param   mixed $targetW         Thumb target width
   * @param   mixed $targetH         Thumb target height
   *
   * @return  boolean
   */
  public function thumbCrop($fileLocation, $fileDestination, $targetW, $targetH) {
    $x = $targetW;
    $y = $targetH;
    $ratio_thumb = $x / $y;
    $type = strtolower(substr(strrchr($fileLocation, "."), 1));
    if ($type == 'jpeg') {
      $type = 'jpg';
    }
    switch ($type) {
    case 'bmp' :
      $img = imagecreatefromwbmp($fileLocation);
      break;
    case 'gif' :
      $img = imagecreatefromgif($fileLocation);
      break;
    case 'jpg' :
      $img = imagecreatefromjpeg($fileLocation);
      break;
    case 'png' :
      $img = imagecreatefrompng($fileLocation);
      break;
    default :
      return FALSE;
    }
    // Original size
    list($xx, $yy) = getimagesize($fileLocation);
    $ratio_original = $xx / $yy;
    // Too short
    if ($xx < $x and $yy < $y) {
      return FALSE;
    }
    if ($ratio_original >= $ratio_thumb) {
      $yo = $yy;
      $xo = ceil(($yo * $x) / $y);
      $xo_ini = ceil(($xx - $xo) / 2);
      $xy_ini = 0;
    } else {
      $xo = $xx;
      $yo = ceil(($xo * $y) / $x);
      $xy_ini = ceil(($yy - $yo) / 2);
      $xo_ini = 0;
    }
    $targetThumb = imagecreatetruecolor($targetW, $targetH);
    // Keep transparency
    if ($type == "gif" or $type == "png") {
      imagecolortransparent($targetThumb, imagecolorallocatealpha($targetThumb, 0, 0, 0, 127));
      imagealphablending($targetThumb, FALSE);
      imagesavealpha($targetThumb, TRUE);
    }
    imagecopyresampled($targetThumb, $img, 0, 0, $xo_ini, $xy_ini, $x, $y, $xo, $yo);
    switch ($type) {
    case 'bmp' :
      imagewbmp($targetThumb, $fileDestination);
      break;
    case 'gif' :
      imagegif($targetThumb, $fileDestination);
      break;
    case 'jpg' :
      imagejpeg($targetThumb, $fileDestination);
      break;
    case 'png' :
      imagepng($targetThumb, $fileDestination);
      break;
    }
    imagedestroy($targetThumb);
    return TRUE;
  }

  /**
   * Add a watermark to a given picture
   *
   * @param   mixed $image        Image to be tagged
   * @param   mixed $watermark    Stamp to be applied
   * @param   mixed $marginRight  Margin between the stamp and the right border
   * @param   mixed $marginBottom Margin between the stamp and the bottom border
   * @param   mixed $alpha        Transparency of the stamp (default = 50%)
   *
   * @return  void
   */
  public function watermark($image, $watermark, $marginRight, $marginBottom, $alpha = 50) {
    // Charge le cachet et la photo afin d'y appliquer le tatouage numérique
    $stamp = imagecreatefrompng($watermark);
    $type = mime_content_type($image);
    $im = '';
    switch (substr($type, 6)) {
    case 'jpeg' :
      $im = imagecreatefromjpeg($image);
      break;
    case 'gif' :
      $im = imagecreatefromgif($image);
      break;
    case 'png' :
      $im = imagecreatefrompng($image);
      break;
    }
    $sx = imagesx($stamp);
    $sy = imagesy($stamp);
    // Merge image and watermark
    imagecopymerge($im, $stamp, imagesx($im) - $sx - $marginRight, imagesy($im) - $sy - $marginBottom, 0, 0, imagesx($stamp), imagesy($stamp), $alpha);
    // Save the image
    switch (substr($type, 6)) {
    case 'jpeg' :
      $im = imagepng($im, $image);
      break;
    case 'gif' :
      $im = imagegif($im, $image);
      break;
    case 'png' :
      $im = imagejpeg($im, $image);
      break;
    }
    imagedestroy($im);
  }

  /**
   * Makes a thumb from a picture and crop if necessary
   *
   * @param   mixed $fileLocation    Location of the file(s) to be resized
   * @param   mixed $fileDestination Thumb final location
   * @param   mixed $targetW         Thumb target width
   * @param   mixed $targetH         Thumb target height
   *
   * @return  boolean
   */
  public function resize($fileLocation, $fileDestination, $targetW = 0, $targetH = 0) {
    $dimensions = getimagesize($fileLocation);
    $ratio = $dimensions[0] / $dimensions[1];
    $image = $dimX = $dimY = '';
    // Calcul des dimensions si 0 passé en paramètre
    if ($targetW == 0 && $targetH == 0) {
      $targetW = $dimensions[0];
      $targetH = $dimensions[1];
    } elseif ($targetH == 0) {
      $targetH = round($targetW / $ratio);
    } elseif ($targetW == 0) {
      $targetW = round($targetH * $ratio);
    }
    if ($dimensions[0] > ($targetW / $targetH) * $dimensions[1]) {
      $dimY = $targetH;
      $dimX = round($targetH * $dimensions[0] / $dimensions[1]);
    }
    if ($dimensions[0] < ($targetW / $targetH) * $dimensions[1]) {
      $dimX = $targetW;
      $dimY = round($targetW * $dimensions[1] / $dimensions[0]);
    }
    if ($dimensions[0] == ($targetW / $targetH) * $dimensions[1]) {
      $dimX = $targetW;
      $dimY = $targetH;
    }
    $pattern = imagecreatetruecolor($targetW, $targetH);
    $type = mime_content_type($fileLocation);
    switch (substr($type, 6)) {
    case 'jpeg' :
      $image = imagecreatefromjpeg($fileLocation);
      break;
    case 'gif' :
      imagecolortransparent($pattern, imagecolorallocatealpha($pattern, 0, 0, 0, 127));
      imagealphablending($pattern, FALSE);
      imagesavealpha($pattern, TRUE);
      $image = imagecreatefromgif($fileLocation);
      break;
    case 'png' :
      imagecolortransparent($pattern, imagecolorallocatealpha($pattern, 0, 0, 0, 127));
      imagealphablending($pattern, FALSE);
      imagesavealpha($pattern, TRUE);
      $image = imagecreatefrompng($fileLocation);
      break;
    }
    imagecopyresampled($pattern, $image, 0, 0, 0, 0, $dimX, $dimY, $dimensions[0], $dimensions[1]);
    imagedestroy($image);
    return imagejpeg($pattern, $fileDestination, 100);
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
