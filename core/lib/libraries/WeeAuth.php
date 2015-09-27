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
 * WeeAuth
 *
 * User lib to handle authentication
 *
 * @access        Private
 * @version       0.1
 */
class WeeAuth {
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
   * Database users table
   * @var string $_dbUsersTable
   */
  protected $_dbUsersTable;
  /**
   * Database groups table
   * @var string $_dbGroupsTable
   */
  protected $_dbGroupsTable;
  /**
   * Database permissions table
   * @var string $_dbPermissionsTable
   */
  protected $_dbPermissionsTable;
  /**
   * Sender address for auth system mails
   * @var string $_emailsFrom
   */
  protected $_emailsFrom;
  /**
   * WeeMail instance shortcut
   * @var object $mail
   */
  protected $wmail;
  /**
   * WeeFilter instance shortcut
   * @var object $filter
   */
  protected $filter;
  /**
   * Activation email (send to validate an account for instance) :
   * 0 - Subject
   * 1 - Content before activation link
   * 2 - Content after activation link
   *
   * @var array $_activationEmail
   */
  protected $_activationEmail;
  /**
   * Password email (when this one is system generated) :
   * 0 - Subject
   * 1 - Content before activation link
   * 2 - Content after activation link
   *
   * @var array $_passwordEmail
   */
  protected $_passwordEmail;
  /**
   * Update password email (when this one is system generated) :
   * 0 - Subject
   * 1 - Content before token link
   * 2 - Content after token link
   *
   * @var array $_updatePasswordEmail
   */
  protected $_updatePasswordEmail;
  /**
   * Temporary session msg string used in sessions
   * @const TEMP_SESS_MSG
   */
  const TEMP_SESS_MSG = "flashmsg";
  /**
   * Logged in string used in sessions
   * @const LOGGED_IN
   */
  const LOGGED_IN = "loggedin";
  /**
   * User id string used in sessions
   * @const USER_ID
   */
  const USER_ID = "userid";
  /**
   * Lifetime string used in sessions
   * @const LIFETIME
   */
  const LIFETIME = "lifetime";

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeeAuth
   */
  public function __construct($internalErrorHandler = FALSE) {
    // Shortcut if used inside PXeli Framework
    $this->setInternalErrorHandler($internalErrorHandler);
    // Set error codes
    $this->setErrorCodes();
    // Get WeeMail object
    $this->wmail = Wee()->wmail;
    // Get WeeFilter object
    $this->filter = Wee()->filter;
  }

  /**
   * Cookie/session authentication
   * @return  boolean
   */
  public function authenticateFromCookie() {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($_SESSION[self::USER_ID]));
    if ($user !== NULL && $_SESSION[self::LOGGED_IN] == TRUE) {
      $user->lastseen = R::isoDateTime();
      $id = R::store($user);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * User authentication
   *
   * @param   string  $username         Username
   * @param   string  $password         Password
   * @param   boolean $cookieRememberMe Remember me function extension session cookie
   * @param   integer $cookieDuration   Cookie duration in days
   *
   * @return  boolean
   */
  public function authenticate($username, $password, $cookieRememberMe = FALSE, $cookieDuration = 30) {
    if (!isset($username) || $username === '' || !isset($password) || $password === '') {
      $this->throwError(11);
    } else {
      // Check if the user already exists
      $user = R::findOne($this->_dbUsersTable, 'username = ?', array($username));
      if ($user === NULL) {
        // Username could not be found
        $this->throwError(12);
      } else {
        // Begin controls
        if (crypt($password, $user->password) == $user->password) {          
          // Check if user is not blocked
          if(!$this->isBlocked($username)){
              // Logged in !       
              $params = session_get_cookie_params();
              $user->lastseen = R::isoDateTime();
              $_SESSION[self::LOGGED_IN] = TRUE;
              $_SESSION[self::USER_ID] = $user->id;
              $_SESSION[self::LIFETIME] = $params['lifetime'];
              $_SESSION[self::TEMP_SESS_MSG] = NULL;
              if ($cookieRememberMe) {
                $life = $cookieDuration;
                setcookie(session_name(), $_COOKIE[session_name()], time() + 60 * 60 * 24 * $cookieDuration, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
              }
              $id = R::store($user);
              return TRUE;
          } else {
              // User is currently blocked
              $this->throwError(13);
          }
        } else {
          // Incorrect password
          $this->throwError(13);
        }
      }
    }
  }

  /**
   * Check if a user is not blocked
   * 
   * @param string $username Username to be checked
   * 
   * @return boolean
   */
  private function isBlocked($username){
    $user = R::findOne($this->_dbUsersTable, 'username = ?', array($username));
    if ($user != NULL) {
        if($user->blocked == TRUE || $user->activated == FALSE){
            return true;
        } else {
            $sharedWeegroups = 'shared' . ucfirst($this->_dbGroupsTable);
            $groups = $user->{$sharedWeegroups};
            if (count($groups) < 1) {
                return false;
            } else {
                // If at least one group is not blocked, authorize authentication
                foreach ($groups as $row) {
                   if($row->blocked == FALSE){
                       return false;
                   } 
                }
                // Each group seems to be blocked
                return true;
            }
        }
    } else {
        return false;
    }
  }

  /**
   * User Logout
   * @return boolean
   */
  public function logout() {
    // Destroy the session
    if (isset($_SESSION[self::LOGGED_IN]) || isset($_SESSION[self::USER_ID])) {
      session_destroy();
      session_write_close();
      // Destroy the session cookie
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 5000, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * User insertion
   *
   * @param   string  $username  Username
   * @param   string  $password  Password (system-generated when not defined)
   * @param   string  $firstname Optional first name
   * @param   string  $lastname  Optional last name
   * @param   string  $email     Optional email
   * @param   string  $reminder  Optional password reminder
   * @param   boolean $activated Activation requirement (by default activated)
   *
   * @return  integer
   */
  public function createUser($username, $password = NULL, $firstname = NULL, $lastname = NULL, $email = NULL, $reminder = NULL, $activated = TRUE, $needtokenactivation = FALSE, $sendpasswordbyemail = FALSE) {
    // Overall username check
    if (!isset($username) || $username === '') {
      // If not defined, throw error
      $this->throwError(1);
    } else {
      // Check if the user already exists
      $user = R::findOne($this->_dbUsersTable, 'username = ?', array($username));
      if ($user !== NULL) {
        // User already exists
        $this->throwError(0);
      } else {
        // External communications
        $sendActivationMail = $needtokenactivation;
        $sendPasswordByMail = $sendpasswordbyemail;
        // User does not exist, create a new one
        $user = R::dispense($this->_dbUsersTable);
        $user->username = $username;
        // Password activation
        if ($password === NULL && $email === NULL) {
          // Can't send the generated password, throw error
          $this->throwError(2);
        } else {
          if ($password === NULL && $email !== NULL) {
            if ($this->filter->validateEmail($email)) {
              // Generate unique password
              $passwordUncrypted = substr(hash('md5', openssl_random_pseudo_bytes(32)), 0, mt_rand(8, 12));
              $passwordCrypted = $this->encode($passwordUncrypted);
              // In that case overrides function argument (user must know his password if this mode is used)
              $sendPasswordByMail = TRUE;
              // Set password
              $user->password = $passwordCrypted;
            } else {
              // Invalid email, throw error
              $this->throwError(3);
            }
          } else {
            if ($password !== NULL) {
              $passwordUncrypted = $password;
              $passwordCrypted = $this->encode($passwordUncrypted);
              // Set password
              $user->password = $passwordCrypted;
            } else {
              // do nothing
            }
          }
        }
        // Finish mapping values
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        if (isset($email) && $email !== '') {
          if ($this->filter->validateEmail($email)) {
            $user->email = $email;
          } else {
            // Invalid email, throw error
            $this->throwError(3);
          }
        }
        $user->reminder = $reminder;
        $user->activated = $activated;
        // Later use, in case of multiple attempts can block the account for a given time
        $user->blocked = FALSE;
        $user->created = R::isoDateTime();
        $user->password = R::isoDateTime();
        $user->lastseen = NULL;
        $user->lastupdated = R::isoDateTime();
        $user->token = NULL;
        $user->psswdtoken = NULL;
        $user->psswdtokenverified = NULL;
        // Password and activation mails
        if ($sendActivationMail) {
          if ($activated === TRUE) {
            // Cannot send activation email if user already activated
            $this->throwError(6);
          }
          if ($user->email !== NULL) {
            $user->token = sha1(mt_rand(10000, 99999) . time() . $email . $username);
            $this->sendMail($user->email, $user->token, NULL, NULL);
          } else {
            // Cannot send email if undefined, throw error
            $this->throwError(2);
          }
        }
        if ($sendPasswordByMail) {
          if ($user->email !== NULL) {
            $this->sendMail($user->email, NULL, $passwordUncrypted, NULL);
          } else {
            // Cannot send email if undefined, throw error
            $this->throwError(2);
          }
        }
        // Finally, store the user and returns the id
        $id = R::store($user);
        return $id;
      }
    }
  }

  /**
   * Password database encoding
   *
   * @param   string $password Password to be stored
   *
   * @return  string
   */
  private function encode($password) {
    if (defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH) {
      $salt = '$2y$11$' . substr(md5(uniqid(rand(), TRUE)), 0, 22);
      return crypt($password, $salt);
    } else {
      // server is not secured
      $this->throwError(4);
    }
  }

  /**
   * Send various authentication emails
   *
   * @param   string $email          User email
   * @param   string $token          Token to activate an account
   * @param   string $password       Password to be sent
   * @param   string $updatePassword Change password mail
   *
   * @return boolean
   */
  private function sendMail($email, $token, $password, $updatePassword) {
    $this->wmail->addFrom($this->_emailsFrom);
    $this->wmail->addTo($email);
    // Token activation password
    if ($token !== NULL) {
      $htmlC = $this->_activationEmail[1] . $token . $this->_activationEmail[2];
      $subject = $this->_activationEmail[0];
    } // Password information mail
    else {
      if ($password !== NULL) {
        $htmlC = $this->_passwordEmail[1] . $password . $this->_passwordEmail[2];
        $subject = $this->_passwordEmail[0];
      } // Password update token
      else {
        $htmlC = $this->_updatePasswordEmail[1] . $updatePassword . $this->_updatePasswordEmail[2];
        $subject = $this->_updatePasswordEmail[0];
      }
    }
    // Build email content
    $this->wmail->addHtmlContent($htmlC);
    $this->wmail->addSubject($subject);
    if (!$this->wmail->send()) {
      $this->throwError(5);
    } else {
      return TRUE;
    }
  }

  /**
   * Token generation
   *
   * @param   string $email    Email
   * @param   string $username Username
   *
   * @return  string
   */
  private function generateToken($email, $username) {
    return sha1(mt_rand(10000, 99999) . time() . $email . $username);
  }

  /**
   * Token validation
   *
   * @param   string $token Token to be validated
   *
   * @return  boolean
   */
  public function validateToken($token) {
    if (!isset($token) || $token === '') {
      // Technical error, token is empty or null
      $this->throwError(7);
    } else {
      // Check if the user already exists
      $user = R::findOne($this->_dbUsersTable, 'token = ?', array($token));
      if ($user !== NULL) {
        if (!$user->activated == TRUE) {
          // activate user and set token to null
          return $this->activateAccount($user->id, TRUE);
        } else {
          // Fonctionnal error, user already activated
          $this->throwError(8);
        }
      } else {
        // Fonctionnal error, no user found with this token
        $this->throwError(9);
      }
    }
  }

  /**
   * Account activation
   *
   * @param   string  $userID      User id
   * @param   boolean $removeToken Remove the token when activating the account
   *
   * @return  boolean
   */
  public function activateAccount($userID, $removeToken = FALSE) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      if ($user->activated == TRUE) {
        // Fonctionnal error, user already activated
        $this->throwError(8);
      } else {
        $user->activated = TRUE;
        if ($removeToken) {
          $user->token = NULL;
        }
        $user->lastupdated = R::isoDateTime();
        $id = R::store($user);
        return TRUE;
      }
    } else {
      $this->throwError(10);
    }
  }

  /**
   * Account deactivateion
   *
   * @param   string $userID Token to be validated
   *
   * @return  boolean
   */
  public function deactivateAccount($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      if ($user->activated == FALSE) {
        // Fonctionnal error, user already deactivated
        $this->throwError(8);
      } else {
        $user->lastupdated = R::isoDateTime();
        $user->activated = FALSE;
        $id = R::store($user);
        return TRUE;
      }
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * Block an account (after x failed login attemps for instance)
   *
   * @param   string $userID Used id to block
   *
   * @return  boolean
   */
  public function blockAccount($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      if ($user->blocked == TRUE) {
        // Fonctionnal error, user already blocked
        $this->throwError(8);
      } else {
        $user->lastupdated = R::isoDateTime();
        $user->blocked = TRUE;
        $id = R::store($user);
        return TRUE;
      }
    } else {
      $this->throwError(10);
    }
  }

  /**
   * Unblock a given account
   *
   * @param   string $userID User account to be unblocked
   *
   * @return  boolean
   */
  public function unblockAccount($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      if ($user->blocked == FALSE) {
        // Fonctionnal error, user already unblocked
        $this->throwError(8);
      } else {
        $user->lastupdated = R::isoDateTime();
        $user->blocked = FALSE;
        $id = R::store($user);
        return TRUE;
      }
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * User update
   *
   * @param   integer $userID    User ID
   * @param   string  $username  Username
   * @param   string  $firstname Optional first name (if null not updated)
   * @param   string  $lastname  Optional last name (if null not updated)
   * @param   string  $email     Optional email (if null not updated)
   * @param   string  $reminder  Optional password reminder (if null not updated)
   *
   * @return  boolean
   */
  public function updateUserDetails($userID, $username = NULL, $firstname = NULL, $lastname = NULL, $email = NULL, $reminder = NULL) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      $user->username = $username !== NULL ? $username : $user->username;
      $user->firstname = $firstname !== NULL ? $firstname : $user->firstname;
      $user->lastname = $lastname !== NULL ? $lastname : $user->lastname;
      if (isset($email) && $email !== '') {
        if ($this->filter->validateEmail($email)) {
          $user->email = $email;
        } else {
          // Invalid email, throw error
          $this->throwError(3);
        }
      }
      $user->reminder = $reminder !== NULL ? $reminder : $user->reminder;
      $user->lastupdated = R::isoDateTime();
      $id = R::store($user);
      return TRUE;
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * User delete
   *
   * @param   integer $userID User ID
   *
   * @return  boolean
   */
  public function deleteUser($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      R::trash($user);
      return TRUE;
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * Returns a password reminder
   *
   * @param   integer $userID User ID
   *
   * @return  boolean
   */
  public function getUserPasswordReminder($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      if ($user->reminder !== NULL && $user->reminder !== '') {
        return $user->reminder;
      } else {
        return NULL;
      }
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * Returns current user (if logged in)
   *
   * @return  array
   */
  public function getCurrentUser() {
    if (!isset($_SESSION[self::LOGGED_IN]) || !isset($_SESSION[self::USER_ID])) {
      return NULL;
    } else {
      $user = R::findOne($this->_dbUsersTable, 'id = ?', array($_SESSION[self::USER_ID]));
      if ($user !== NULL) {
        // convert bean to array
        $exportUser = $user->export();
        return $exportUser;
      } else {
        // User does not exist
        return NULL;
      }
    }
  }

  /**
   * Group insertion
   *
   * @param   string  $groupName        Group name
   * @param   string  $groupDescription Group description
   * @param   string  $groupEmail       Group email
   * @param   boolean $blocked          Activated / deactivated
   * @param   string  $otherDetails     Misc. information
   *
   * @return  integer
   */
  public function createGroup($groupName, $groupDescription = NULL, $groupEmail = NULL, $blocked = FALSE, $otherDetails = NULL) {
    // Overall group name check
    if (!isset($groupName) || $groupName === '') {
      $this->throwError(15);
    } else {
      // Check if the group already exists
      $group = R::findOne($this->_dbGroupsTable, 'name = ?', array($groupName));
      if ($group !== NULL) {
        // Group already exists
        $this->throwError(14);
      } else {
        // Group can be created
        $group = R::dispense($this->_dbGroupsTable);
        $group->name = $groupName;
        $group->description = $groupDescription;
        if (isset($groupEmail) && $groupEmail !== '') {
          if ($this->filter->validateEmail($groupEmail)) {
            $group->email = $groupEmail;
          } else {
            // Invalid email, throw error
            $this->throwError(3);
          }
        }
        // Continue insert
        $group->blocked = $blocked;
        $group->details = $otherDetails;
        $group->created = R::isoDateTime();
        $group->lastupdated = R::isoDateTime();
        $group->lastinsertion = NULL;
        $id = R::store($group);
        return $id;
      }
    }
  }

  /**
   * Block a group (temp deactivation)
   *
   * @param   string $groupID Group id to block
   *
   * @return  boolean
   */
  public function blockGroup($groupID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    if ($group !== NULL) {
      $group->lastupdated = R::isoDateTime();
      $group->blocked = TRUE;
      $id = R::store($group);
      return TRUE;
    } else {
      // Group does not exist
      $this->throwError(16);
    }
  }

  /**
   * Unblock a given group
   *
   * @param   string $groupID Group id to unblock
   *
   * @return  boolean
   */
  public function unblockGroup($groupID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    if ($group !== NULL) {
      $group->lastupdated = R::isoDateTime();
      $group->blocked = FALSE;
      $id = R::store($group);
      return TRUE;
    } else {
      // Group does not exist
      $this->throwError(16);
    }
  }

  /**
   * Group update
   *
   * @param   integer $groupID          Group ID
   * @param   string  $groupName        Group name
   * @param   string  $groupDescription Group description
   * @param   string  $groupEmail       Group email
   * @param   string  $otherDetails     Misc. information
   *
   * @return  integer
   */
  public function updateGroup($groupID, $groupName = NULL, $groupDescription = NULL, $groupEmail = NULL, $otherDetails = NULL) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    if ($group !== NULL) {
      $group->lastupdated = R::isoDateTime();
      $group->name = $groupName !== NULL ? $groupName : $group->name;
      $group->description = $groupDescription !== NULL ? $groupDescription : $group->description;
      if (isset($groupEmail) && $groupEmail !== '') {
        if ($this->filter->validateEmail($groupEmail)) {
          $group->email = $groupEmail;
        } else {
          // Invalid email, throw error
          $this->throwError(3);
        }
      }
      $group->details = $otherDetails !== NULL ? $otherDetails : $group->details;
      $id = R::store($group);
      return TRUE;
    } else {
      // Group does not exist
      $this->throwError(16);
    }
  }

  /**
   * Group delete
   *
   * @param   integer $groupID group ID
   *
   * @return  boolean
   */
  public function deleteGroup($groupID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    if ($group !== NULL) {
      R::trash($group);
      return TRUE;
    } else {
      // Group does not exist
      $this->throwError(16);
    }
  }

  /**
   * Permission insertion
   *
   * @param   string $permName        Permission name
   * @param   string $permDescription Permission description
   *
   * @return  integer
   */
  public function createPermission($permName, $permDescription) {
    // Overall permission name check
    if (!isset($permName) || $permName === '') {
      $this->throwError(18);
    } else {
      // Check if the permission already exists
      $perm = R::findOne($this->_dbPermissionsTable, 'name = ?', array($permName));
      if ($perm !== NULL) {
        // Permission already exists
        $this->throwError(17);
      } else {
        // Permission can be created
        $perm = R::dispense($this->_dbPermissionsTable);
        $perm->name = $permName;
        $perm->description = $permDescription;
        $perm->created = R::isoDateTime();
        $perm->lastupdated = R::isoDateTime();
        $id = R::store($perm);
        return $id;
      }
    }
  }

  /**
   * Update permission
   *
   * @param   string $permID          Permission ID
   * @param   string $permName        Permission name
   * @param   string $permDescription Permission description
   *
   * @return  integer
   */
  public function updatePermission($permID, $permName = NULL, $permDescription = NULL) {
    $perm = R::findOne($this->_dbPermissionsTable, 'id = ?', array($permID));
    if ($perm !== NULL) {
      // If perm has been found, update it
      $perm->lastupdated = R::isoDateTime();
      $perm->name = $permName !== NULL ? $permName : $perm->name;
      $perm->description = $permDescription !== NULL ? $permDescription : $perm->description;
      $id = R::store($perm);
      return TRUE;
    } else {
      // Permission does not exist
      $this->throwError(19);
    }
  }

  /**
   * Permission delete
   *
   * @param   integer $permID Permission ID
   *
   * @return  boolean
   */
  public function deletePermission($permID) {
    $perm = R::findOne($this->_dbPermissionsTable, 'id = ?', array($permID));
    if ($perm !== NULL) {
      R::trash($perm);
      return TRUE;
    } else {
      // Permission does not exist
      $this->throwError(19);
    }
  }

  /**
   * Attach permission to a user
   *
   * @param   integer $userID User ID
   * @param   integer $permID Permission ID
   *
   * @return  boolean
   */
  public function userAttachPermission($userID, $permID) {
    $perm = R::findOne($this->_dbPermissionsTable, 'id = ?', array($permID));
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($perm !== NULL && $user !== NULL) {
      $sharedPermsList = 'shared' . ucfirst($this->_dbPermissionsTable) . 'List';
      // dynamic var definition
      $user->{$sharedPermsList}[] = $perm;
      $id = R::store($user);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Remove permission from a user
   *
   * @param   integer $userID User ID
   * @param   integer $permID Permission ID
   *
   * @return  boolean
   */
  public function userRemovePermission($userID, $permID) {
    $perm = R::findOne($this->_dbPermissionsTable, 'id = ?', array($permID));
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($perm !== NULL && $user !== NULL) {
      $sharedPermsList = 'shared' . ucfirst($this->_dbPermissionsTable) . 'List';
      // dynamic var definition
      unset($user->{$sharedPermsList}[$permID]);
      R::store($user);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Attach permission to a group
   *
   * @param   integer $groupID Group ID
   * @param   integer $permID  Permission ID
   *
   * @return  boolean
   */
  public function groupAttachPermission($groupID, $permID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    $perm = R::findOne($this->_dbPermissionsTable, 'id = ?', array($permID));
    if ($perm !== NULL && $group !== NULL) {
      $sharedPermsList = 'shared' . ucfirst($this->_dbPermissionsTable) . 'List';
      // dynamic var definition
      $group->{$sharedPermsList}[] = $perm;
      $id = R::store($group);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Remove permission from a group
   *
   * @param   integer $groupID Group ID
   * @param   integer $permID  Permission ID
   *
   * @return  boolean
   */
  public function groupRemovePermission($groupID, $permID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    $perm = R::findOne($this->_dbPermissionsTable, 'id = ?', array($permID));
    if ($perm !== NULL && $group !== NULL) {
      $sharedPermsList = 'shared' . ucfirst($this->_dbPermissionsTable) . 'List';
      // dynamic var definition
      unset($group->{$sharedPermsList}[$permID]);
      R::store($group);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Attach a user to a group
   *
   * @param   integer $userID  User ID
   * @param   integer $groupID Group ID
   *
   * @return  boolean
   */
  public function attachUserToGroup($userID, $groupID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL && $group !== NULL) {
      $sharedUsersList = 'shared' . ucfirst($this->_dbUsersTable) . 'List';
      $group->lastinsertion = R::isoDateTime();
      // dynamic var definition
      $group->{$sharedUsersList}[] = $user;
      $id = R::store($group);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Remove a user to a group
   *
   * @param   integer $userID  User ID
   * @param   integer $groupID Group ID
   *
   * @return  boolean
   */
  public function removeUserFromGroup($userID, $groupID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL && $group !== NULL) {
      $sharedUsersList = 'shared' . ucfirst($this->_dbUsersTable) . 'List';
      // dynamic var definition
      unset($group->{$sharedUsersList}[$userID]);
      R::store($group);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Get group related users
   *
   * @param   integer $groupID Group ID
   *
   * @return  mixed
   */
  public function getUsersFromGroup($groupID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    if ($group !== NULL) {
      $sharedUsersList = 'shared' . ucfirst($this->_dbUsersTable) . 'List';
      $users = $group->{$sharedUsersList};
      if (count($users) < 1) {
        // No users found, return null
        return NULL;
      } else {
        $exportArray = array();
        foreach ($users as $row) {
          // Convert bean to array
          $exportArray[] = $row->export();
        }
        return $exportArray;
      }
    } else {
      // No group
      return NULL;
    }
  }

  /**
   * Get group related permissions
   *
   * @param   integer $groupID Group ID
   *
   * @return  mixed
   */
  public function getGroupPermissions($groupID) {
    $group = R::findOne($this->_dbGroupsTable, 'id = ?', array($groupID));
    if ($group !== NULL) {
      $sharedPermsList = 'shared' . ucfirst($this->_dbPermissionsTable) . 'List';
      $perms = $group->{$sharedPermsList};
      if (count($perms) < 1) {
        // No perms
        return NULL;
      } else {
        $exportArray = array();
        foreach ($perms as $row) {
          // convert bean to array
          $exportArray[] = $row->export();
        }
        return $exportArray;
      }
    } else {
      return NULL;
    }
  }

  /**
   * Get user related permissions
   *
   * @param   integer $userID User ID
   *
   * @return  mixed
   */
  public function getUserPermissions($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      $sharedPermsList = 'shared' . ucfirst($this->_dbPermissionsTable) . 'List';
      $perms = $user->{$sharedPermsList};
      if (count($perms) < 1) {
        return NULL;
      } else {
        $exportArray = array();
        foreach ($perms as $row) {
          // convert bean to array
          $exportArray[] = $row->export();
        }
        return $exportArray;
      }
    } else {
      return NULL;
    }
  }

  /**
   * Get user groups
   *
   * @param   integer $userID User ID
   *
   * @return  mixed
   */
  public function getUserGroups($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      $sharedWeegroups = 'shared' . ucfirst($this->_dbGroupsTable);
      $groups = $user->{$sharedWeegroups};
      if (count($groups) < 1) {
        return NULL;
      } else {
        $exportArray = array();
        foreach ($groups as $row) {
          // convert bean to array
          $exportArray[] = $row->export();
        }
        return $exportArray;
      }
    } else {
      return NULL;
    }
  }

  /**
   * Get user group related permissions
   *
   * @param   integer $userID User ID
   *
   * @return  mixed
   */
  public function getUserGroupPermissions($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      $groups = $user->sharedWeegroups;
      $exportArray = array();
      // Retrieve all permissions from group
      foreach ($groups as $group) {
        foreach ($group->sharedWeepermissionsList as $row) {
          $exportArray[] = $row->export();
        }
      }
      // Remove duplicate entries (if user is registered to different groups)
      return array_intersect_key($exportArray, array_unique(array_map('serialize', $exportArray)));
    } else {
      return NULL;
    }
  }

  /**
   * Get user all permissions
   *
   * @param   integer $userID User ID
   *
   * @return  mixed
   */
  public function getUserAllPermissions($userID) {
    // Get user permissions + group permission concatenation
    $userPerms = $this->getUserPermissions($userID);
    $groupPerms = $this->getUserGroupPermissions($userID);
    $merge = array_merge_recursive($userPerms, $groupPerms);
    // Remove duplicate entries
    return array_intersect_key($merge, array_unique(array_map('serialize', $merge)));
  }

  /**
   * Get all registered users
   *
   * @return  array
   */
  public function getAllUsers() {
    $users = R::findAll($this->_dbUsersTable);
    if (count($users) < 1) {
      return NULL;
    } else {
      $exportArray = array();
      foreach ($users as $row) {
        // Convert bean to array (avoid extracting the whole bean branch)
        $exportArray[] = $row->export();
      }
      return $exportArray;
    }
  }

  /**
   * Get all groups
   *
   * @return  array
   */
  public function getAllGroups() {
    $groups = R::findAll($this->_dbGroupsTable);
    if (count($groups) < 1) {
      return NULL;
    } else {
      $exportArray = array();
      foreach ($groups as $row) {
        $exportArray[] = $row->export();
      }
      return $exportArray;
    }
  }

  /**
   * Get all permissions
   *
   * @return  array
   */
  public function getAllPermissions() {
    $perms = R::findAll($this->_dbPermissionsTable);
    if (count($perms) < 1) {
      return NULL;
    } else {
      $exportArray = array();
      foreach ($perms as $row) {
        $exportArray[] = $row->export();
      }
      return $exportArray;
    }
  }

  /**
   * Get current user id from session
   *
   * @return  array
   */
  public function getCurrentUserID() {
    if (isset($_SESSION[self::USER_ID])) {
      return $_SESSION[self::USER_ID];
    } else {
      return NULL;
    }
  }

  /**
   * Get current user group
   * @return  array
   */
  public function getCurrentUserGroups() {
    if (isset($_SESSION[self::USER_ID])) {
      return $this->getUserGroups($_SESSION[self::USER_ID]);
    } else {
      return NULL;
    }
  }

  /**
   * Get current user permissions
   * @return  array
   */
  public function getCurrentUserPermissions() {
    if (isset($_SESSION[self::USER_ID])) {
      return $this->getUserAllPermissions($_SESSION[self::USER_ID]);
    } else {
      return NULL;
    }
  }

  /**
   * Check if current user is part of a given group
   *
   * @param   string $group Group id or name
   *
   * @return  array
   */
  public function currentUserCheckGroup($group) {
    $groups = $this->getCurrentUserGroups();
    if ($groups !== NULL && count($groups) > 0) {
      // Is numeric => input is passed as an id
      if (is_numeric($group)) {
        foreach ($groups as $row) {
          if (isset($row['id']) && $row['id'] == $group) {
            return TRUE;
          }
        }
      } else {
        // Else input is a group name
        foreach ($groups as $row) {
          if (isset($row['name']) && $row['name'] == $group) {
            return TRUE;
          }
        }
      }
    } else {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Check if current has a certain permission
   *
   * @param   string $perm Permission id or name
   *
   * @return  array
   */
  public function currentUserCheckPermission($perm) {
    $perms = $this->getCurrentUserPermissions();
    if ($perms !== NULL && count($perms) > 0) {
      // Same system as the group method
      if (is_numeric($perm)) {
        foreach ($perms as $row) {
          if (isset($row['id']) && $row['id'] == $perm) {
            return TRUE;
          }
        }
      } else {
        foreach ($perms as $row) {
          if (isset($row['name']) && $row['name'] == $perm) {
            return TRUE;
          }
        }
      }
    } else {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Add password token to the user (to be verified by email)
   *
   * @param   string $userID User id
   *
   * @return  void
   */
  public function addUpdatePaswordToken($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      if ($user->email != NULL && $user->email != '') {
        // Associate a token to the account, which will be tested when changing the password
        $user->psswdtoken = $this->generateToken($user->email, $user->username);
        $user->psswdtokenverified = FALSE;
        $user->lasupdated = R::isoDateTime();
        R::store($user);
        return TRUE;
      } else {
        // Invalid email address
        $this->throwError(3);
      }
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * Send update password token
   *
   * @param   string $userID User id
   *
   * @return  boolean
   */
  public function sendUpdatePasswordToken($userID) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      if ($user->email != NULL && $user->email != '') {
        // Send the generated token to the user
        return $this->sendMail($user->email, NULL, NULL, $user->psswdtoken);
      } else {
        // Invalid email address
        $this->throwError(3);
      }
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * Send update password token
   *
   * @param   string $userID User id
   *
   * @return  boolean
   */
  public function editPassword($userID, $newpassword, $oldpassword, $tokenInput = NULL) {
    $user = R::findOne($this->_dbUsersTable, 'id = ?', array($userID));
    if ($user !== NULL) {
      // 1 - Token complex mode => new password + token + old password
      if (isset($oldpassword) && $oldpassword !== '' && isset($newpassword) && $newpassword !== '' && isset($tokenInput) && $tokenInput !== '') {
        // Check if old password match :
        if (crypt($oldpassword, $user->password) == $user->password) {
          if ($user->psswdtoken === $tokenInput) {
            $user->psswdtoken = NULL;
            $user->psswdtokenverified = 1;
            $user->lastupdated = R::isoDateTime();
            $user->password = $this->encode($newpassword);
            R::store($user);
            return TRUE;
          } else {
            // Token does not match
            $this->throwError(21);
          }
        } else {
          // Old password does not match
          $this->throwError(20);
        }
      } // 2 - Token mode => new password + token
      elseif (isset($newpassword) && $newpassword !== '' && isset($tokenInput) && $tokenInput !== '') {
        if ($user->psswdtoken === $tokenInput) {
          $user->psswdtoken = NULL;
          $user->psswdtokenverified = 1;
          $user->lastupdated = R::isoDateTime();
          $user->password = $this->encode($newpassword);
          R::store($user);
          return TRUE;
        } else {
          // Token does not match
          $this->throwError(21);
        }
      } // 3 - Simple mode => old password / new password
      elseif (isset($oldpassword) && $oldpassword !== '' && isset($newpassword) && $newpassword !== '') {
        // Check if old password match :
        if (crypt($oldpassword, $user->password) == $user->password) {
          $user->psswdtoken = NULL;
          $user->psswdtokenverified = NULL;
          $user->lastupdated = R::isoDateTime();
          $user->password = $this->encode($newpassword);
          R::store($user);
          return TRUE;
        } else {
          // Old password does not match
          $this->throwError(20);
        }
      } // No fitting mode
      else {
        return FALSE;
      }
    } else {
      // User does not exist
      $this->throwError(10);
    }
  }

  /**
   * Check if current user is loggedin
   * @return  array
   */
  public function isLoggedin() {
    if (isset($_SESSION[self::USER_ID]) && isset($_SESSION[self::LOGGED_IN]) && $_SESSION[self::LOGGED_IN]) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Store temporary msg
   *
   * @param   string $msg Temporary msg
   *
   * @return  void
   */
  public function setTempMsg($msg) {
    $_SESSION[self::TEMP_SESS_MSG] = $msg;
  }

  /**
   * Get temporary msg
   * @return  string
   */
  public function getTempMsg() {
    $output = $_SESSION[self::TEMP_SESS_MSG];
    $this->clearTempMsg();
    return $output;
  }

  /**
   * Clear temporary msg
   * @return  void
   */
  private function clearTempMsg() {
    $_SESSION[self::TEMP_SESS_MSG] = NULL;
  }

  /**
   * Search for users according to a given param
   *
   * @param   string  $param      Search parameter
   * @param   strin   $value      Parameter value
   * @param   boolean $exactMatch Look for an exact match
   *
   * @return  array
   */
  public function searchUsers($param, $value, $exactMatch = TRUE) {
    if ($exactMatch) {
      $searchStr = $param . ' = ?';
    } else {
      $searchStr = $param . ' LIKE ?';
      $value = '%' . $value . '%';
    }
    $users = R::findAll($this->_dbUsersTable, $searchStr, array($value));
    if ($users != NULL) {
      $exportArray = array();
      foreach ($users as $row) {
        $exportArray[] = $row->export();
      }
      return $exportArray;
    } else {
      return NULL;
    }
  }

  /**
   * Set errors codes
   * @return    void
   */
  private function setErrorCodes() {
    $this->_errors = array(
      0 => array('F', 'Error - User already exists.'),
      1 => array(
        'F',
        'Error - Username not defined when registering the user.'
      ),
      2 => array(
        'T',
        'Error - Email must be defined to send the generated password or the activation token.'
      ),
      3 => array('F', 'Error - Invalid email address.'),
      4 => array(
        'T',
        'Error - Passwords encryption cannot be handled by the current hosting server.'
      ),
      5 => array(
        'T',
        'Error - Activation or password email could not be sent, terminating.'
      ),
      6 => array(
        'T',
        'Error - Incoherent operation, cannot send activation email if user is already defined as activated.'
      ),
      7 => array('T', 'Error - Token is null or empty.'),
      8 => array('F', 'Error - User is already activated/deactivated.'),
      9 => array('F', 'Error - Token could not be recognised.'),
      10 => array('F', 'Error - Account operation error, user not found.'),
      11 => array(
        'F',
        'Error - authentication, username or password is empty.'
      ),
      12 => array(
        'F',
        'Error - authentication, nsername could not be found.'
      ),
      13 => array('F', 'Error - Autentification, password is invalid.'),
      14 => array(
        'F',
        'Error - Auth group already exists and could not be created.'
      ),
      15 => array(
        'T',
        'Error - Auth group could not be created, missing mandatory field.'
      ),
      16 => array('F', 'Error - Auth group does not exist.'),
      17 => array(
        'F',
        'Error - Permission already exists and could not be created.'
      ),
      18 => array(
        'T',
        'Error - Permission could not be created, missing mandatory field.'
      ),
      19 => array('F', 'Error - Permission does not exist.'),
      20 => array('F', 'Error - Old password does not match.'),
      21 => array('F', 'Error - Update password token does not match.'),
      22 => array('F', 'Error - User account or group is currently blocked.')
    );
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

  /**
   * Errors handler
   * Treat errors according to settings
   *
   * @param    integer $errorCode Error code to be handled
   *
   * @return    Exception/Error Message
   * @throws    AuthException
   */
  private function throwError($errorCode) {
    if ($this->_internalErrorHandler) {
      die($this->_errors[$errorCode][1]);
    } else {
      $errorMsg = $this->_errors[$errorCode][1];
      $errorCategory = $this->_errors[$errorCode][0];
      throw new AuthException($errorCategory, $errorMsg, $errorCode);
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

  /**
   * Set database users table name
   *
   * @param   string $usersTable Name of the database users table
   *
   * @return  void
   */
  public function setUsersTable($usersTable) {
    $this->_dbUsersTable = $usersTable;
  }

  /**
   * Set database groups table name
   *
   * @param   string $groupsTable Name of the database groups table
   *
   * @return  void
   */
  public function setGroupsTable($groupsTable) {
    $this->_dbGroupsTable = $groupsTable;
  }

  /**
   * Set database permissions table name
   *
   * @param   string $permsTable Name of the database permissions table
   *
   * @return  void
   */
  public function setPermissionsTable($permsTable) {
    $this->_dbPermissionsTable = $permsTable;
  }

  /**
   * Define the content of the account activation mail
   *
   * @param   array $activationMail See details below
   *                                0 - Subject
   *                                1 - Content before activation link
   *                                2 - Content after activation link
   *
   * @return  void
   */
  public function setActivationEmail($activationMail) {
    $this->_activationEmail = $activationMail;
  }

  /**
   * Define the content of the password mail
   *
   * @param   array $passwordMail See details below
   *                              0 - Subject
   *                              1 - Content before activation link
   *                              2 - Content after activation link
   *
   * @return  void
   */
  public function setPasswordEmail($passwordMail) {
    $this->_passwordEmail = $passwordMail;
  }

  /**
   * Define the content of the update password mail
   *
   * @param   array $passwordMail See details below
   *                              0 - Subject
   *                              1 - Content before activation link
   *                              2 - Content after activation link
   *
   * @return  void
   */
  public function setUpdatePasswordEmail($passwordMail) {
    $this->_updatePasswordEmail = $passwordMail;
  }

  /**
   * Sender address for auth system mails
   *
   * @param   string $senderAddress Sender address
   *
   * @return  void
   */
  public function setEmailsFrom($senderAddress) {
    $this->_emailsFrom = $senderAddress;
  }
}
