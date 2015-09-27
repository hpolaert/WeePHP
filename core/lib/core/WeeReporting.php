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
 * WeeReporting
 *
 * Utility class to monitor the application performances
 * Static as it could be used punctually from within a class ofr debugging purpose
 *
 * @access        Private
 * @version       0.1
 */
class WeeReporting {
  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $startTime;
  /**
   * Array of error codes
   * @var array $_errors
   */
  protected $_pausedTime;
  /**
   * Array of markers to monitor simultaneously different operations
   * @var array $markers
   */
  protected $markers = array();

  /**
   * Basic constructor
   *
   * Start the timer
   */
  public function initialize() {
    $this->startTimer('GlobalApp', 'Global Application loading time');
    $this->_pausedTime = 0;
    _log('PERF', '____________________________________________________________________');
    _log('PERF', 'WeeReporting class initialized at ' . date('Y/m/d H:i:s', time()));
    $initialRequest = $this->markers['GlobalApp']['startTime'] - $_SERVER["REQUEST_TIME_FLOAT"];
    _log('PERF', 'Server request ' . $initialRequest . ' seconds ago.');
    _log('PERF', '--------------------------------------------------------------------');
  }

  /**
   * Get the current time
   *
   * @return integer
   */
  private function getCurrentTime() {
    return microtime(TRUE);
  }

  /**
   * Pause the timer and record current time
   */
  public function pauseTimer() {
    $this->_pausedTime = 0;
    $this->_pausedTime = $this->getCurrentTime();
  }

  /**
   * Unpause the timer
   */
  public function unpauseTimer() {
    $this->markers['GlobalApp']['startTime'] += ($this->getCurrentTime() - $this->_pausedTime);
  }

  /**
   * Get timer value
   *
   * @param    integer $decimals Number of decimals
   *
   * @return    integer
   */
  public function getGlobalTimer($decimals = 5) {
    return round($this->getCurrentTime() - $this->markers['GlobalApp']['startTime'], $decimals);
  }

  /**
   * Record a series of operations
   *
   * Assign a specific timer to a set of operations
   *
   * @param    string $shortID Short ID
   * @param    string $marker  Name of the marker
   */
  public function startTimer($shortID, $marker) {
    $this->markers[$shortID]['name'] = $marker;
    $this->markers[$shortID]['startTime'] = $this->getCurrentTime();
    if ($shortID !== 'GlobalApp') {
      _log('PERF', strtoupper($marker) . ' operation >> STARTED at ' . date('Y/m/d H:i:s', time()));
    }
  }

  /**
   * Get Timer for marker
   *
   * Returns the current time of a specific timer
   *
   * @param    string $marker Marker short ID
   *
   * @return    string
   */
  public function endTimer($marker) {
    $decimals = 5;
    $timeTaken = round($this->getCurrentTime() - $this->markers[$marker]['startTime'], $decimals);
    _log('PERF', strtoupper($this->markers[$marker]['name']) . ' operation >> FINISHED at ' . date('Y/m/d H:i:s', time()) . ' and took ' . $timeTaken . ' seconds.');
  }

  public function stop() {
    $decimals = 5;
    $globalTimeTaken = round($this->getCurrentTime() - $this->markers['GlobalApp']['startTime'], $decimals);
    _log('PERF', '--------------------------------------------------------------------');
    _log('PERF', 'Page generated in ' . $globalTimeTaken . ' seconds sent at ' . date('Y/m/d H:i:s', time()));
    _log('PERF', '____________________________________________________________________');
  }
}
