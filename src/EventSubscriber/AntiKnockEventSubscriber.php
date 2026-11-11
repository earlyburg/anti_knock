<?php

namespace Drupal\anti_knock\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\anti_knock\Service\DetectorService;

/**
 * The AntiKnockEventSubscriber Service class.
 *
 * @package Drupal\anti_knock\EventSubscriber
 */
class AntiKnockEventSubscriber implements EventSubscriberInterface {

  /**
   * The detector service.
   *
   * @var \Drupal\anti_knock\Service\DetectorService
   */
  public $detectorService;

  /**
   * The AntiKnockEventSubscriber constructor.
   *
   * @param \Drupal\anti_knock\Service\DetectorService $detector_service
   *   The Detector Service.
   */
  public function __construct(
    DetectorService $detector_service,
  ) {
    $this->detectorService = $detector_service;
  }

  /**
   * Ensures anti_knock output returned upon NotFoundHttpException.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The response for exception event.
   */
  public function onNotFoundException(ExceptionEvent $event) {

    if ($event->getThrowable() instanceof NotFoundHttpException) {
      $this->detectorService->evaluateRequest($event->getRequest());
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::EXCEPTION][] = ['onNotFoundException', 0];
    return $events;
  }

}
