<?php

namespace Drupal\anti_knock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Psr\Container\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\anti_knock\Service\DetectorService;

/**
 * The AntiKnockConfigForm class.
 */
class AntiKnockConfigForm extends ConfigFormBase {

  /**
   * The Drupal database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The detector service.
   *
   * @var \Drupal\anti_knock\Service\DetectorService
   */
  public $detectorService;

  /**
   * Config settings.
   *
   * @var string
   */
  const AK_SETTINGS = 'anti_knock.adminsettings';

  /**
   * The getFormId function.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'anti_knock_settings_form';
  }

  /**
   * The getEditibleConfigNames method.
   *
   * @return string[]
   *   An array of config names that will be editable through this form.
   */
  protected function getEditableConfigNames() {
    return [
      static::AK_SETTINGS,
    ];
  }

  /**
   * AntiKnockConfigForm constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Drupal database connection.
   * @param \Drupal\anti_knock\Service\DetectorService $detector_service
   *   The Detector Service.
   */
  public function __construct(
    Connection $connection,
    DetectorService $detector_service,
  ) {
    $this->database = $connection;
    $this->detectorService = $detector_service;
  }

  /**
   * The create function.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return \Drupal\Core\Form\ConfigFormBase|\Drupal\anti_knock\Form\AntiKnockConfigForm|static
   *   An instance of this form.
   *
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('anti_knock.detector')
    );
  }

  /**
   * The buildForm function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::AK_SETTINGS);
    $serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME']);
    $ipArray = $this->database->select('anti_knock_events', 'n')
      ->fields('n', ['ip_address', 'created'])
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchCol();
    $bannedIps = array_unique($ipArray);

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Anti-Knock Settings'),
      '#description' => $this->t('Set the threshold for triggering the ip block.'),
      '#open' => TRUE,
    ];

    $form['settings']['threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Threshold'),
      '#default_value' => $config->get('threshold'),
      '#description' => $this->t('The number of 404 exceptions allowed from a single IP before it gets blocked.'),
      '#required' => TRUE,
    ];

    $form['settings']['server_ip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server IP'),
      '#default_value' => $config->get('server_ip'),
      '#description' => $this->t('The IP address of the server hosting this Drupal site.'),
      '#required' => TRUE,
      '#placeholder' => $serverIp,
    ];

    $form['banned_ips'] = [
      '#type' => 'details',
      '#title' => $this->t('Banned IPs'),
      '#description' => $this->t('Check the banned IPs and submit to unban them.'),
      '#open' => TRUE,
    ];

    foreach ($bannedIps as $ip) {
      $form['banned_ips']['chkbx_' . str_replace('.', '_', $ip)] = [
        '#type' => 'checkbox',
        '#title' => $ip,
        '#default_value' => 0,
        '#required' => FALSE,
        '#attributes' => ['class' => ['ak-class-1']],
      ];
    }
    $form['subnet_whitelist'] = [
      '#type' => 'details',
      '#title' => $this->t('Subnet Whitelist'),
      '#description' => $this->t('Specify subnets that should bypass the Anti-Knock checks.'),
      '#open' => TRUE,
    ];

    $form['subnet_whitelist']['subnet1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subnet 1'),
      '#default_value' => $config->get('subnet1'),
      '#description' => $this->t('Enter subnet in the format xxx.xxx.xxx.*'),
      '#placeholder' => '192.168.2.*',
    ];

    $form['subnet_whitelist']['subnet2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subnet 2'),
      '#default_value' => $config->get('subnet2'),
      '#description' => $this->t('Enter subnet in the format xxx.xxx.xxx.*'),
      '#placeholder' => '192.168.2.*',
    ];

    $form['subnet_whitelist']['subnet3'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subnet 3'),
      '#default_value' => $config->get('subnet3'),
      '#description' => $this->t('Enter subnet in the format xxx.xxx.xxx.*'),
      '#placeholder' => '192.168.2.*',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * The validateForm function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * The submitForm function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::AK_SETTINGS);
    $formValues = $form_state->getValues();
    // Delete and unban IPs that have been checked.
    foreach ($formValues as $key => $value) {
      if (str_contains($key, 'chkbx_') && $value == 1) {
        $cleanKey = substr($key, strlen('chkbx_'));
        $ip = str_replace('_', '.', $cleanKey);
        $this->detectorService->deleteIpEntries($ip);
        $this->detectorService->unbanIpEntry($ip);
      }
    }
    $config
      ->set('threshold', $form_state->getValue('threshold'))
      ->set('server_ip', $form_state->getValue('server_ip'))
      ->set('subnet1', $form_state->getValue('subnet1'))
      ->set('subnet2', $form_state->getValue('subnet2'))
      ->set('subnet3', $form_state->getValue('subnet3'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
