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
 * WeeFile
 *
 * Files manipulation
 *
 * @access        Private
 * @version       0.1
 */
class WeeFile {
  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $_errors = array();
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
   * @return    WeeFile
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside WeePHP Framework
    $this->setInternalErrorHandler($internalErrorHandler);
  }

  /**
   * Returns the permissions of a file
   *
   * @param   string  $filepath Target file path
   * @param   boolean $string   Return format (either 0777 or rw-w-w etc.)
   *
   * @return  string
   */
  // Explicit return function taken from http://php.net/manual/fr/function.fileperms.php
  public function getFilePermissions($filepath, $string = FALSE) {
    clearstatcache();
    if ($string) {
      $perms = fileperms(str_replace("\\", "/", $filepath));
      if (($perms & 0xC000) == 0xC000) {
        // Socket
        $info = 's';
      } elseif (($perms & 0xA000) == 0xA000) {
        // Lien symbolique
        $info = 'l';
      } elseif (($perms & 0x8000) == 0x8000) {
        // Régulier
        $info = '-';
      } elseif (($perms & 0x6000) == 0x6000) {
        // Block special
        $info = 'b';
      } elseif (($perms & 0x4000) == 0x4000) {
        // Dossier
        $info = 'd';
      } elseif (($perms & 0x2000) == 0x2000) {
        // Caractère spécial
        $info = 'c';
      } elseif (($perms & 0x1000) == 0x1000) {
        // pipe FIFO
        $info = 'p';
      } else {
        // Inconnu
        $info = 'u';
      }
      // Autres
      $info .= (($perms & 0x0100) ? 'r' : '-');
      $info .= (($perms & 0x0080) ? 'w' : '-');
      $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
      // Groupe
      $info .= (($perms & 0x0020) ? 'r' : '-');
      $info .= (($perms & 0x0010) ? 'w' : '-');
      $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
      // Tout le monde
      $info .= (($perms & 0x0004) ? 'r' : '-');
      $info .= (($perms & 0x0002) ? 'w' : '-');
      $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    } else {
      $info = substr(decoct(fileperms(str_replace("\\", "/", $filepath))), 2);
    }
    return $info;
  }

  /**
   * Set the permissions of a file
   *
   * @param   string $filepath Target file path
   * @param   string $perms    Permissions to be applied (0XXX)
   *
   * @return  string
   */
  public function setCHMOD($filepath, $perms) {
    clearstatcache();
    chmod(str_replace("\\", "/", $filepath), $perms);
  }

  /**
   * Basic upload method
   *
   * @param   mixed $filename     One or multiple filenames
   * @param   mixed $targetFolder Target folder
   * @param   mixed $newFilename  Target file name if needed
   *
   * @return  boolean
   */
  public function uploadFiles($filename, $targetFolder, $newFilename = NULL) {
    if ($this->isTrueArray($filename)) {
      foreach ($filename as $file) {
        // can't be used with new file name
        $success = $this->uploadFiles($file, $targetFolder);
        if (!$success) {
          return FALSE;
        }
      }
      return TRUE;
    } else {
      $folder = $targetFolder;
      if ($newFilename !== NULL && $newFilename !== '') {
        $targetFilename = $newFilename;
      } else {
        $targetFilename = basename($_FILES[$filename]['name']);
      }
      if (move_uploaded_file($_FILES[$filename]['tmp_name'], $folder . $targetFilename)) {
        return TRUE;
      } else {
        return FALSE;
      }
    }
  }

  /**
   * Returns the extension of a given filename
   *
   * @param   string $filename Target directory path
   *
   * @return  string
   */
  public function getExtension($filename) {
    $tmp = explode('.', str_replace("\\", "/", $filename));
    $extension = '';
    if (!empty($filename)) {
      $extension = end($tmp);
    }
    return $extension;
  }

  /**
   * Returns target directory as an array (optionnally recursive)
   *
   * @param   string  $directory Target directory path
   * @param   boolean $recursive Recursive listing
   *
   * @return  Array
   */
  public function listDirectory($directory, $recursive = FALSE) {
    // Output array
    $result = array();
    // Scan root foolder
    $root = scandir(str_replace("\\", "/", $directory));
    if (!$recursive) {
      $result = array_diff($root, array('..', '.'));
    } else {
      foreach ($root as $key => $value) {
        // Avoid special chars treatment
        $value = str_replace("\\", "/", $value);
        if (!in_array($value, array('.', '..'))) {
          if (is_dir($directory . DIRECTORY_SEPARATOR . $value)) {
            $result[][$value] = $this->listDirectory($directory . DIRECTORY_SEPARATOR . $value, TRUE);
          } else {
            $result[] = $value;
          }
        }
      }
    }
    return array_values($result);
  }

  /**
   * Move a file from one directory to another
   *
   * @param   string $filename Name of the file to be moved
   * @param   string $from     Current folder
   * @param   string $to       Target folder
   *
   * @return  boolean
   */
  public function moveFile($filename, $from, $to) {
    // Clean folders
    $from = rtrim(str_replace("\\", "/", $from), '/');
    $to = rtrim(str_replace("\\", "/", $to), '/');
    return rename($from . '/' . $filename, $to . '/' . $filename);
  }

  /**
   * Copy a file from one directory to another
   *
   * @param   string $filename Name of the file to be moved
   * @param   string $from     Current folder
   * @param   string $to       Target folder
   *
   * @return  boolean
   */
  public function copyFile($filename, $from, $to) {
    // Clean folders
    $from = rtrim(str_replace("\\", "/", $from), '/');
    $to = rtrim(str_replace("\\", "/", $to), '/');
    return copy($from . '/' . $filename, $to . '/' . $filename);
  }

  /**
   * Delete a file or array of files
   *
   * @param   mixed $files Name of the file(s) to be removed
   *
   * @return  boolean
   */
  public function deleteFile($files) {
    if ($this->isTrueArray($files)) {
      foreach ($files as $key => $file) {
        $success = $this->deleteFile($file);
        if (!$success) {
          return FALSE;
        }
      }
      return TRUE;
    } else {
      return unlink($files);
    }
  }

  /**
   * Delete a folder and all its content
   *
   * @param   string $dir Dir to be removed
   *
   * @return  boolean
   */
  public function removeDir($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? $this->removeDir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
  }

  /**
   * Write a file on the server
   *
   * @param   string $folder   Target folder
   * @param   string $filename Template location
   * @param   string $content  Content to be put inside the file
   *
   */
  public function writeFile($folder, $filename, $content) {
    // Create target folder if it does not exist
    if (!is_dir($folder)) {
      mkdir($folder, 0755, TRUE);
    }
    $outputFile = $folder . DIRECTORY_SEPARATOR . $filename;
    file_put_contents($outputFile, $content);
  }

  /**
   * Ouput file content
   *
   * @param   string $file Target file location
   *
   * @return  string
   */
  public function readFile($file) {
    if (file_exists($file)) {
      $out = file_get_contents($file);
      return $out;
    }
  }

  /**
   * Force file download
   *
   * @param   string $file Target file location
   *
   * @return  void
   */
  public function download($file) {
    if (file_exists($file)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=' . basename($file));
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($file));
      readfile($file);
      exit;
    }
  }

  // ------------- Utility Methods   -----------------
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
