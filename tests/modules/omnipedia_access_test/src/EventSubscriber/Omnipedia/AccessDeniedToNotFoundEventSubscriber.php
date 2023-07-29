<?php

declare(strict_types=1);

namespace Drupal\omnipedia_access_test\EventSubscriber\Omnipedia;

use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\omnipedia_access\Event\Omnipedia\AccessDeniedToNotFoundEvent;
use Drupal\omnipedia_access\Event\Omnipedia\AccessDeniedToNotFoundEventsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Event subscriber to expose various admin routes as 403s for testing.
 */
class AccessDeniedToNotFoundEventSubscriber implements EventSubscriberInterface {

  /**
   * Various admin routes to expose 403s for the purposes of testing.
   *
   * @var string[]
   *
   * @see \Drupal\Tests\omnipedia_access\Functional\AccessDeniedToNotFoundTest::$adminRoutesToExpose
   *   Must match this list.
   */
  protected array $adminRoutesToExpose = [
    // Path: 'admin/content'
    'system.admin_content',
    // Path: 'admin/structure'
    'system.admin_structure',
    // Path: 'admin/appearance'
    'system.themes_page',
    // Path: 'admin/modules'
    'system.modules_list',
  ];

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $currentRouteMatch
   *   The Drupal current route match service.
   */
  public function __construct(
    protected readonly StackedRouteMatchInterface $currentRouteMatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AccessDeniedToNotFoundEventsInterface::ACCESS_DENIED_TO_NOT_FOUND => 'onAccessDeniedToNotFound',
    ];
  }

  /**
   * Access denied to not found event handler.
   *
   * @param \Drupal\omnipedia_access\Event\Omnipedia\AccessDeniedToNotFoundEvent $event
   *   The event object.
   */
  public function onAccessDeniedToNotFound(
    AccessDeniedToNotFoundEvent $event,
  ): void {

    if (!\in_array(
      $this->currentRouteMatch->getRouteName(), $this->adminRoutesToExpose,
    )) {
      return;
    }

    $event->setThrowable(new AccessDeniedHttpException());

  }

}
