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
 * WeeMail
 *
 * Create and send mails
 *
 * @access        Private
 * @version       0.1
 */
class WeeMail {
  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $_errors = array();
  /**
   * Array of recipients
   * @var array $_recipients
   */
  protected $_recipients = array();
  /**
   * Array of carbon copies
   * @var array $_ccRecipients
   */
  protected $_ccRecipients = array();
  /**
   * Array of blind carbon copies
   * @var array $_bccRecipients
   */
  protected $_bccRecipients = array();
  /**
   * Array of attachments
   * @var array $_attachments
   */
  protected $_attachments = array();
  /**
   * Internal error handler
   * @var boolean $_internalErrorHandler
   */
  protected $_internalErrorHandler;
  /**
   * Email sender
   * @var string $_sender
   */
  protected $_sender;
  /**
   * Email reply to address
   * @var string $_replyTo
   */
  protected $_replyTo;
  /**
   * Email return path address
   * @var string $_returnPath
   */
  protected $_returnPath;
  /**
   * Displayable error
   * @var string $_err
   */
  protected $_err;
  /**
   * Mail alternate content
   * @var string $_bodyContent
   */
  protected $_bodyContent;
  /**
   * Mail alternate content
   * @var string $_alternateContent
   */
  protected $_alternateContent;
  /**
   * Mail subject
   * @var string $_subject
   */
  protected $_subject;
  /**
   * Send mail with UTF-8 encoding
   * @var boolean $_utf8
   */
  protected $_utf8;

  /**
   * Class constructor
   * Optional argument to use the class externally (internal error handler)
   *
   * @param    boolean $internalErrorHandler True when used outside the framework
   *
   * @return    WeeMail
   */
  public function __construct($internalErrorHandler = FALSE) {
    if (!defined('SERVER_ROOT') && !$internalErrorHandler) {
      exit('Direct access to this file is not allowed.');
    }
    // Shortcut if used inside PXeli Framework
    $this->setInternalErrorHandler($internalErrorHandler);
    // Set error codes
    $this->setErrorCodes();
  }

  /**
   * Send the mail !
   *
   * @param   boolean $throw Optional parameter to return an exception instead of an error
   *
   * @return  mixed
   * @throws MailException
   */
  public function send($throw = FALSE) {
    // Returns first encountered error
    if ($this->_err) {
      return FALSE;
    }
    // CC & BCC recipients
    $recipients = implode(',', $this->_recipients);
    $ccRecipients = implode(',', $this->_ccRecipients);
    $bccRecipients = implode(',', $this->_bccRecipients);
    // Must have at least one recipient
    if ($recipients === '' && $ccRecipients === '' && $bccRecipients === '') {
      // otherwise throw error
      $this->throwError(1);
    }
    // Set the sender of the email
    if ($this->_sender !== NULL && $this->_sender !== "") {
      ini_set('sendmail_from', $this->_sender);
    }
    // Return path and reply-to
    $returnPath = $this->_returnPath != NULL ? $this->_returnPath : $this->_sender;
    $replyTo = $this->_replyTo != NULL ? $this->_returnPath : $this->_sender;
    $returnPathDebug = '-f' . $this->_returnPath != NULL ? $this->_returnPath : $this->_sender;
    // Content => by default HTML content, if otherwise specified strip html tags for text content (with a basic BR to NL conversion)
    // As it's possible to send an email without content, the field is not mandatory
    $htmlContent = $this->_bodyContent != NULL ? $this->_bodyContent : ' ';
    $textContent = $this->_alternateContent != NULL ? $this->_alternateContent : strip_tags(preg_replace('#<br\s*?/?>#i', PHP_EOL, $this->_bodyContent));
    // Defines encoding & email-subject
    $encoding = ($this->_utf8 != NULL && $this->_utf8 != FALSE) ? 'utf-8' : 'iso-8859-1';
    $subject = $this->_subject != NULL ? $this->_subject : ' ';
    if ($encoding === 'utf-8') {
      $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }
    // Boundary to enable different content-type
    $boundary = md5(time());
    //################# HEADERS
    $headers = (string) NULL;
    $headers .= 'From: ' . $this->_sender . PHP_EOL;
    $headers .= 'Reply-To: ' . $replyTo . PHP_EOL;
    $headers .= 'Return-Path: ' . $returnPath . PHP_EOL;
    $headers .= $ccRecipients !== '' ? 'Cc: ' . $ccRecipients . PHP_EOL : '';
    $headers .= $bccRecipients !== '' ? 'Bcc: ' . $bccRecipients . PHP_EOL : '';
    $headers .= 'Message-ID:<' . time() . '-' . md5($this->_sender) . '@' . $_SERVER['SERVER_NAME'] . '>' . PHP_EOL;
    $headers .= 'X-Mailer: PHP v' . phpversion() . PHP_EOL;
    $headers .= 'MIME-Version: 1.0' . PHP_EOL;
    $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . PHP_EOL;
    //################# BODY
    $body = (string) NULL;
    //################# ATTACHMENTS
    if (!empty($this->_attachments)) {
      foreach ($this->_attachments as $attachment) {
        // Get attachment content and basename
        $attachmentBasename = preg_replace('/^.+[\\\\\\/]/', '', $attachment);
        $data = chunk_split(base64_encode(file_get_contents($attachment)));
        // Add attachment
        $body .= '--' . $boundary . PHP_EOL;
        $body .= 'Content-Type: ' . $this->getMimeType($attachment) . '; name="' . $attachmentBasename . '"' . PHP_EOL;
        $body .= 'Content-Transfer-Encoding: base64' . PHP_EOL;
        $body .= 'Content-Disposition: attachment; filename="' . $attachmentBasename . '"' . PHP_EOL . PHP_EOL;
        $body .= $data . PHP_EOL . PHP_EOL;
      }
    }
    //################# MAIL CONTENT
    $body .= 'Content-Type: multipart/alternative' . PHP_EOL;
    // Text content
    $body .= '--' . $boundary . PHP_EOL;
    $body .= 'Content-Type: text/plain; charset=' . $encoding . PHP_EOL;
    $body .= 'Content-Transfer-Encoding: 8bit' . PHP_EOL;
    $body .= $textContent . PHP_EOL . PHP_EOL;
    // HTML content
    $body .= '--' . $boundary . PHP_EOL;
    $body .= 'Content-Type: text/html; charset=' . $encoding . PHP_EOL;
    $body .= 'Content-Transfer-Encoding: 8bit' . PHP_EOL;
    $body .= $htmlContent . PHP_EOL . PHP_EOL;
    //################# CLOSING BODY
    $body .= '--' . $boundary . '--' . PHP_EOL . PHP_EOL;
    // Send and optionally returns technical error
    if (!mail($recipients, $subject, $body, $headers, $returnPathDebug)) {
      // Throw exception if mail cannot be sent
      if ($throw && !$this->_internalErrorHandler) {
        $errorMsg = $this->_errors[3][1];
        $errorCategory = $this->_errors[3][0];
        throw new MailException($errorCategory, $errorMsg, 3);
      }
      return FALSE;
    } else {
      // Reset INI sender
      ini_restore('sendmail_from');
      $this->clearObject();
      return TRUE;
    }
  }

  // ------------- Utility Methods   -----------------
  /**
   * Errors handler
   * Treat errors according to settings
   *
   * @param    integer $errorCode Error code to be handled
   *
   * @return    Exception/Error Message
   */
  private function throwError($errorCode) {
    if ($this->_internalErrorHandler) {
      die($this->_errors[$errorCode][1]);
    } else {
      // Unique assignment (FIFO => first error in first error out)
      if ($this->_err == NULL) {
        $this->_err = $this->_errors[$errorCode][1];
      }
    }
  }

  /**
   * Reset vars to send multiple emails with the same instance
   *
   * @return  void
   */
  private function clearObject() {
    $this->_alternateContent = NULL;
    $this->_attachments = array();
    $this->_bccRecipients = array();
    $this->_bodyContent = NULL;
    $this->_ccRecipients = array();
    $this->_err = NULL;
    $this->_recipients = array();
    $this->_replyTo = NULL;
    $this->_returnPath = NULL;
    $this->_sender = NULL;
    $this->_subject = NULL;
    $this->_utf8 = NULL;
  }

  /**
   * Utility method to check the validity of a recipient / sender
   *
   * @param    string $address Address to be parsed
   *
   * @return    string
   */
  private function checkRecipient($address) {
    // Check for abc@domain.com or Mister Smither <abc@domain.com>
    $regex = "/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}$|^[ a-zA-Z.+_]+<[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}>$/";
    $address = trim($address);
    if (preg_match($regex, $address)) {
      return $address;
    } else {
      $this->throwError(0);
    }
  }

  /**
   * Utility method to get the mime type of a file (file info really sucks)
   *
   * @param    string $filename File name to be parsed
   *
   * @return    string
   */
  private function getMimeType($filename) {
    // MIME types array
    $mimeTypes = array(
      '323' => 'text/h323',
      'acx' => 'application/internet-property-stream',
      'ai' => 'application/postscript',
      'aif' => 'audio/x-aiff',
      'aifc' => 'audio/x-aiff',
      'aiff' => 'audio/x-aiff',
      'asf' => 'video/x-ms-asf',
      'asr' => 'video/x-ms-asf',
      'asx' => 'video/x-ms-asf',
      'au' => 'audio/basic',
      'avi' => 'video/x-msvideo',
      'axs' => 'application/olescript',
      'bas' => 'text/plain',
      'bcpio' => 'application/x-bcpio',
      'bin' => 'application/octet-stream',
      'bmp' => 'image/bmp',
      'c' => 'text/plain',
      'cat' => 'application/vnd.ms-pkiseccat',
      'cdf' => 'application/x-cdf',
      'cer' => 'application/x-x509-ca-cert',
      'class' => 'application/octet-stream',
      'clp' => 'application/x-msclip',
      'cmx' => 'image/x-cmx',
      'cod' => 'image/cis-cod',
      'cpio' => 'application/x-cpio',
      'crd' => 'application/x-mscardfile',
      'crl' => 'application/pkix-crl',
      'crt' => 'application/x-x509-ca-cert',
      'csh' => 'application/x-csh',
      'css' => 'text/css',
      'dcr' => 'application/x-director',
      'der' => 'application/x-x509-ca-cert',
      'dir' => 'application/x-director',
      'dll' => 'application/x-msdownload',
      'dms' => 'application/octet-stream',
      'doc' => 'application/msword',
      'docx' => 'application/msword',
      'dot' => 'application/msword',
      'dvi' => 'application/x-dvi',
      'dxr' => 'application/x-director',
      'eps' => 'application/postscript',
      'etx' => 'text/x-setext',
      'evy' => 'application/envoy',
      'exe' => 'application/octet-stream',
      'fif' => 'application/fractals',
      'flr' => 'x-world/x-vrml',
      'gif' => 'image/gif',
      'gtar' => 'application/x-gtar',
      'gz' => 'application/x-gzip',
      'h' => 'text/plain',
      'hdf' => 'application/x-hdf',
      'hlp' => 'application/winhlp',
      'hqx' => 'application/mac-binhex40',
      'hta' => 'application/hta',
      'htc' => 'text/x-component',
      'htm' => 'text/html',
      'html' => 'text/html',
      'htt' => 'text/webviewhtml',
      'ico' => 'image/x-icon',
      'ief' => 'image/ief',
      'iii' => 'application/x-iphone',
      'ins' => 'application/x-internet-signup',
      'isp' => 'application/x-internet-signup',
      'jfif' => 'image/pipeg',
      'jpe' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'jpg' => 'image/jpeg',
      'js' => 'application/x-javascript',
      'latex' => 'application/x-latex',
      'lha' => 'application/octet-stream',
      'lsf' => 'video/x-la-asf',
      'lsx' => 'video/x-la-asf',
      'lzh' => 'application/octet-stream',
      'm13' => 'application/x-msmediaview',
      'm14' => 'application/x-msmediaview',
      'm3u' => 'audio/x-mpegurl',
      'man' => 'application/x-troff-man',
      'mdb' => 'application/x-msaccess',
      'me' => 'application/x-troff-me',
      'mht' => 'message/rfc822',
      'mhtml' => 'message/rfc822',
      'mid' => 'audio/mid',
      'mny' => 'application/x-msmoney',
      'mov' => 'video/quicktime',
      'movie' => 'video/x-sgi-movie',
      'mp2' => 'video/mpeg',
      'mp3' => 'audio/mpeg',
      'mpa' => 'video/mpeg',
      'mpe' => 'video/mpeg',
      'mpeg' => 'video/mpeg',
      'mpg' => 'video/mpeg',
      'mpp' => 'application/vnd.ms-project',
      'mpv2' => 'video/mpeg',
      'ms' => 'application/x-troff-ms',
      'mvb' => 'application/x-msmediaview',
      'nws' => 'message/rfc822',
      'oda' => 'application/oda',
      'p10' => 'application/pkcs10',
      'p12' => 'application/x-pkcs12',
      'p7b' => 'application/x-pkcs7-certificates',
      'p7c' => 'application/x-pkcs7-mime',
      'p7m' => 'application/x-pkcs7-mime',
      'p7r' => 'application/x-pkcs7-certreqresp',
      'p7s' => 'application/x-pkcs7-signature',
      'pbm' => 'image/x-portable-bitmap',
      'pdf' => 'application/pdf',
      'pfx' => 'application/x-pkcs12',
      'pgm' => 'image/x-portable-graymap',
      'pko' => 'application/ynd.ms-pkipko',
      'pma' => 'application/x-perfmon',
      'pmc' => 'application/x-perfmon',
      'pml' => 'application/x-perfmon',
      'pmr' => 'application/x-perfmon',
      'pmw' => 'application/x-perfmon',
      'pnm' => 'image/x-portable-anymap',
      'pot' => 'application/vnd.ms-powerpoint',
      'ppm' => 'image/x-portable-pixmap',
      'pps' => 'application/vnd.ms-powerpoint',
      'ppt' => 'application/vnd.ms-powerpoint',
      'prf' => 'application/pics-rules',
      'ps' => 'application/postscript',
      'pub' => 'application/x-mspublisher',
      'qt' => 'video/quicktime',
      'ra' => 'audio/x-pn-realaudio',
      'ram' => 'audio/x-pn-realaudio',
      'ras' => 'image/x-cmu-raster',
      'rgb' => 'image/x-rgb',
      'rmi' => 'audio/mid',
      'roff' => 'application/x-troff',
      'rtf' => 'application/rtf',
      'rtx' => 'text/richtext',
      'scd' => 'application/x-msschedule',
      'sct' => 'text/scriptlet',
      'setpay' => 'application/set-payment-initiation',
      'setreg' => 'application/set-registration-initiation',
      'sh' => 'application/x-sh',
      'shar' => 'application/x-shar',
      'sit' => 'application/x-stuffit',
      'snd' => 'audio/basic',
      'spc' => 'application/x-pkcs7-certificates',
      'spl' => 'application/futuresplash',
      'src' => 'application/x-wais-source',
      'sst' => 'application/vnd.ms-pkicertstore',
      'stl' => 'application/vnd.ms-pkistl',
      'stm' => 'text/html',
      'svg' => 'image/svg+xml',
      'sv4cpio' => 'application/x-sv4cpio',
      'sv4crc' => 'application/x-sv4crc',
      't' => 'application/x-troff',
      'tar' => 'application/x-tar',
      'tcl' => 'application/x-tcl',
      'tex' => 'application/x-tex',
      'texi' => 'application/x-texinfo',
      'texinfo' => 'application/x-texinfo',
      'tgz' => 'application/x-compressed',
      'tif' => 'image/tiff',
      'tiff' => 'image/tiff',
      'tr' => 'application/x-troff',
      'trm' => 'application/x-msterminal',
      'tsv' => 'text/tab-separated-values',
      'txt' => 'text/plain',
      'uls' => 'text/iuls',
      'ustar' => 'application/x-ustar',
      'vcf' => 'text/x-vcard',
      'vrml' => 'x-world/x-vrml',
      'wav' => 'audio/x-wav',
      'wcm' => 'application/vnd.ms-works',
      'wdb' => 'application/vnd.ms-works',
      'wks' => 'application/vnd.ms-works',
      'wmf' => 'application/x-msmetafile',
      'wps' => 'application/vnd.ms-works',
      'wri' => 'application/x-mswrite',
      'wrl' => 'x-world/x-vrml',
      'wrz' => 'x-world/x-vrml',
      'xaf' => 'x-world/x-vrml',
      'xbm' => 'image/x-xbitmap',
      'xla' => 'application/vnd.ms-excel',
      'xlc' => 'application/vnd.ms-excel',
      'xlm' => 'application/vnd.ms-excel',
      'xls' => 'application/vnd.ms-excel',
      'xlsx' => 'vnd.ms-excel',
      'xlt' => 'application/vnd.ms-excel',
      'xlw' => 'application/vnd.ms-excel',
      'xof' => 'x-world/x-vrml',
      'xpm' => 'image/x-xpixmap',
      'xwd' => 'image/x-xwindowdump',
      'z' => 'application/x-compress',
      'zip' => 'application/zip'
    );
    $tmp = explode('.', $filename);
    $extension = end($tmp);
    if (array_key_exists($extension, $mimeTypes)) {
      return $mimeTypes[$extension];
    } else {
      return 'application/octet-stream';
    }
  }

  // ------------- Setters / Getters -----------------
  /**
   * Add sender to email (mandatory)
   *
   * @param    string  $address  Sender address
   * @param    boolean $validate Defines wether the email address should be checked (by default False)
   */
  public function addFrom($address, $validate = FALSE) {
    $this->_sender = $validate ? $this->checkRecipient($address) : $address;
  }

  /**
   * Add a reply to email address
   *
   * @param    string  $address  Reply to recipient
   * @param    boolean $validate Defines wether the email address should be checked (by default False)
   */
  public function addReplyTo($address, $validate = FALSE) {
    $this->_replyTo = $validate ? $this->checkRecipient($address) : $address;
  }

  /**
   * Add a return path email address
   *
   * @param    string  $address  Bouncing address recipient
   * @param    boolean $validate Defines wether the email address should be checked (by default False)
   */
  public function addReturnPath($address, $validate = FALSE) {
    $this->_returnPath = $validate ? $this->checkRecipient($address) : $address;
  }

  /**
   * Add a recipient to the mail
   *
   * @param    string  $address  Recipient address
   * @param    boolean $validate Defines wether the email address should be checked (by default False)
   */
  public function addTo($address, $validate = FALSE) {
    if (!in_array($address, $this->_recipients)) {
      $this->_recipients[] = $validate ? $this->checkRecipient($address) : $address;
    }
  }

  /**
   * Add a carbon copy
   *
   * @param    string  $address  Recipient address
   * @param    boolean $validate Defines wether the email address should be checked (by default False)
   */
  public function addCc($address, $validate = FALSE) {
    if (!in_array($address, $this->_recipients)) {
      $this->_ccRecipients[] = $validate ? $this->checkRecipient($address) : $address;
    }
  }

  /**
   * Add a blind carbon copy
   *
   * @param    string  $address  Recipient address
   * @param    boolean $validate Defines wether the email address should be checked (by default False)
   */
  public function addBcc($address, $validate = FALSE) {
    if (!in_array($address, $this->_recipients)) {
      $this->_bccRecipients[] = $validate ? $this->checkRecipient($address) : $address;
    }
  }

  /**
   * Add an attachment (relative or absolute path)
   *
   * @param    mixed $attachments Either single var or array of attachments
   */
  public function addAttachment($attachments) {
    if (isset($attachments) && is_array($attachments) && count($attachments)) {
      foreach ($attachments as $attachment) {
        $this->addAttachment($attachment);
      }
    } else {
      if (!in_array($attachments, $this->_attachments)) {
        $this->_attachments[] = str_replace("\\", "/", $attachments);
      }
    }
  }

  /**
   * Add subject to the mail
   *
   * @param    string $subject Email subject
   */
  public function addSubject($subject) {
    $this->_subject = $subject;
  }

  /**
   * Add HTML content to the mail
   *
   * @param    string $content Add html content
   */
  public function addHtmlContent($content) {
    $this->_bodyContent = $content;
  }

  /**
   * Add text content to the mail
   *
   * @param    string $content Add text content
   */
  public function addTextContent($content) {
    $this->_alternateContent = $content;
  }

  /**
   * Send email with UTF8 subject and content
   *
   * @param    boolean $active Optionally active UTF8 encoding
   */
  public function setUTF8Encoding($active = TRUE) {
    $this->_utf8 = $active;
  }

  /**
   * Set errors codes
   *
   * @return    void
   */
  private function setErrorCodes() {
    $this->_errors = array(
      0 => array(
        'F',
        'Error - One email address is not properly formatted.'
      ),
      1 => array('F', 'Error - No recipient has been defined.'),
      2 => array('F', 'Error - No sent email from has been defined.'),
      3 => array('F', 'Error - Email could not be sent.')
    );
  }

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
   * Getter current error
   * @return boolean
   */
  public function getError() {
    if (isset($this->_err)) {
      return $this->_err;
    }
    return NULL;
  }
}