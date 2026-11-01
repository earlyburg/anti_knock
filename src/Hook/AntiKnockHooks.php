<?php

namespace Drupal\anti_knock\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for anti_knock.
 */
class AntiKnockHooks {

  use StringTranslationTrait;
  /**
   * Implements hook_help().
   */

  #[Hook('help')]

  /**
   * The help function.
   *
   * @param string $route_name
   *   The route name for the current page.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object for the current page.
   *
   * @return string
   *   The help text to be displayed for the current page.
   */
  public function help($route_name, RouteMatchInterface $route_match) {
    $path = '/modules/custom/anti_knock/images/';
    switch ($route_name) {
      case 'help.page.anti_knock':
        $output = '';
        $output .= '<h3>' . $this->t('Setup') . '</h3>';
        $output .= '<div>' . $this->t('<strong>Important:</strong> Go to the configuration page <a href="/admin/config/anti_knock">/admin/config/anti_knock</a> and input the IP address of your webserver.') . '</div>';
        $output .= '<div>' . $this->t('Input the number of 404 exceptions allowed from a single IP before it gets blocked.') . '</div>';
        $output .= '<div>' . $this->t('<strong>Optional:</strong> add some subnets that should not be blocked in the format ###.###.###.*.') . '</div>';
        $output .= '<div>' . $this->t('Click submit to save the configuration.') . '</div>';
        $output .= '<h3>' . $this->t('Functionality') . '</h3>';
        $output .= '<div>' . $this->t('This module monitors 404 requests that reach Drupal and blocks IP addresses that exceed the configured threshold.') . '</div>';
        $output .= '<div>' . $this->t('This module only blocks 404 requests from users who are not logged in. Logged-in users requests are not evaluated.') . '</div>';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<div>' . $this->t('Anti-Knock automatically blocks requests intent on Directory Busting / Brute-Forcing attacks using the functionality of the Drupal <a href="https://www.drupal.org/project/ban" target="_blank">Ban</a> module.') . '</div>';
        $output .= '<div>' . $this->t('Drupal\'s .htaccess file provides one layer of protection against 404 requests containing malicious patterns, but this does not usually stop more sophisticated probing.') . '</div>';
        $output .= '<div>' . $this->t('In general, an attacker may probe a site using this method looking for paths that might exist to help them profile an application.') . '</div>';
        $output .= '<div>' . $this->t('Besides being a nuisance and filling up the logs, getting accurate information about an applications structure can assist attackers in later stage DDOS attacks.') . '</div>';
        $output .= '<div>' . $this->t('Poorly constructed or malicious Bots or webspiders can also cause this problem.') . '</div>';
        $output .= '<div>' . $this->t('Requests to non-existent or expired paths to content can trigger this module to block an IP, so we recommend redirecting such paths appropriately.') . '</div>';
        $output .= '<div>' . $this->t('This module is perfect if you are seeing repeated 404 not found log entries like this:') . '</div><br>';
        $output .= '<a href="' . $path . 'probe_example2.png" target="_blank">';
        $output .= '<img src="' . $path . 'probe_example2.png" width="150" style="max-width: 300px; height: auto;">';
        $output .= '</a>';
        $output .= '<br>';
        $output .= '<div>' . $this->t('Or webserver request log entries like this:') . '</div><br>';
        $output .= '<a href="' . $path . 'probe_example.png" target="_blank">';
        $output .= '<img src="' . $path . 'probe_example.png" width="150" style="max-width: 300px; height: auto;">';
        $output .= '</a>';
        return $output;
    }
  }

}
