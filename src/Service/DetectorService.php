<?php

namespace Drupal\anti_knock\Service;

use Drupal\ban\BanIpManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;

/**
 * The anti knock detector service class.
 *
 * \Drupal\anti_knock\Service\DetectorService.
 */
class DetectorService {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Drupal logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Ban Ip Manager Interface.
   *
   * @var \Drupal\ban\BanIpManagerInterface
   */
  protected $banIpManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  private $killSwitch;

  /**
   * The DetectorService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The Drupal logger channel factory service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\ban\BanIpManagerInterface $ban_ip_manager
   *   The Ban Ip Manager Interface.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   The kill switch.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    LoggerChannelFactory $logger_factory,
    Connection $connection,
    BanIpManagerInterface $ban_ip_manager,
    AccountInterface $current_user,
    KillSwitch $kill_switch,
  ) {
    $this->config = $config;
    $this->loggerFactory = $logger_factory;
    $this->database = $connection;
    $this->banIpManager = $ban_ip_manager;
    $this->currentUser = $current_user;
    $this->killSwitch = $kill_switch;
  }

  /**
   * The evaluateRequest method.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   */
  public function evaluateRequest($request) {
    $moduleConfig = $this->config->get('anti_knock.adminsettings')->get();

    if ($this->currentUser->isAnonymous()) {
      $this->killSwitch->trigger();
      $serverIp = $moduleConfig['server_ip'];
      $clientIp = $request->getClientIp();
      $requestedPath = $request->getPathInfo();
      $timeStamp = time();
      if (empty($clientIp)) {
        return;
      }
      if ($this->checkIfPassthrough($clientIp)) {
        return;
      }
      if ($this->checkIfEntry($clientIp)) {
        if ($this->isThreshold($clientIp)) {
          // Don't ban the webserver IP.
          if ($clientIp !== $serverIp) {
            $this->banIpManager->banIp($clientIp);
            $this->loggerFactory->get('anti_knock')->notice('Banned IP: ' . $clientIp);
          }
        }
        else {
          $this->setNew404Entry($clientIp, $requestedPath, $timeStamp);
        }
      }
      else {
        $this->setNew404Entry($clientIp, $requestedPath, $timeStamp);
      }
    }
  }

  /**
   * The isThreshold method.
   *
   * @param string $clientIp
   *   The client IP address to check against the threshold.
   *
   * @return bool
   *   Returns TRUE if the threshold has been reached.
   */
  public function isThreshold($clientIp) {
    $moduleConfig = $this->config->get('anti_knock.adminsettings')->get();
    $threshold = $moduleConfig['threshold'];
    $ipRows = $this->database->select('anti_knock_events', 'n')
      ->fields('n', ['ip_address'])
      ->condition('n.ip_address', $clientIp)
      ->countQuery()
      ->execute()
      ->fetchField();
    ($ipRows >= $threshold) ? $return = TRUE : $return = FALSE;
    return $return;
  }

  /**
   * The checkIfEntry method.
   *
   * @param string $clientIp
   *   The client IP address to check for an existing entry.
   *
   * @return bool
   *   TRUE if an entry exists for the given client IP, otherwise FALSE.
   */
  public function checkIfEntry($clientIp) {
    $ip = $this->database->select('anti_knock_events', 'n')
      ->fields('n', ['ip_address'])
      ->condition('n.ip_address', $clientIp)
      ->execute()
      ->fetchField();
    ($ip) ? $return = TRUE : $return = FALSE;
    return $return;
  }

  /**
   * The setNew404Entry method.
   *
   * @param string $clientIp
   *   The client IP address of the requester.
   * @param string $requestedPath
   *   The requested path that resulted in a 404 event.
   * @param int $timeStamp
   *   The timestamp of the 404 event.
   *
   * @return bool
   *   TRUE if the new 404 entry was successfully set, otherwise FALSE.
   */
  public function setNew404Entry($clientIp, $requestedPath, $timeStamp) {
    $return = FALSE;
    if (!empty($clientIp) && !empty($requestedPath) && !empty($timeStamp)) {
      $this->database->insert('anti_knock_events')
        ->fields([
          'ip_address' => $clientIp,
          'path' => $requestedPath,
          'created' => $timeStamp,
        ])
        ->execute();
      $return = TRUE;
    }
    return $return;
  }

  /**
   * The deleteIpEntries method.
   *
   * @param string $ip
   *   The IP address to delete entries for.
   */
  public function deleteIpEntries($ip) {
    if (!empty($ip)) {
      $this->database->delete('anti_knock_events')
        ->condition('ip_address', $ip)
        ->execute();
    }
  }

  /**
   * The unbanIpEntry method.
   *
   * @param string $ip
   *   The IP address to unban.
   */
  public function unbanIpEntry($ip) {
    if (!empty($ip)) {
      if ($this->banIpManager->isBanned($ip)) {
        $this->banIpManager->unbanIp($ip);
      }
    }
  }

  /**
   * The checkIfPassthrough method.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP matches a passthrough subnet, otherwise FALSE.
   */
  public function checkIfPassthrough($ip) {
    $moduleConfig = $this->config->get('anti_knock.adminsettings')->get();
    $subnets = [
      $moduleConfig['subnet1'] ?? '',
      $moduleConfig['subnet2'] ?? '',
      $moduleConfig['subnet3'] ?? '',
    ];
    foreach ($subnets as $subnet) {
      if (!empty($subnet)) {
        $subnetPattern = str_replace('*', '\d+', $subnet);
        if (preg_match('/^' . $subnetPattern . '$/', $ip)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
