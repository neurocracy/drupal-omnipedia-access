<?php

declare(strict_types=1);

namespace Drupal\omnipedia_access\EventSubscriber\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\omnipedia_access\Controller\Http4xxController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Event subscriber to replace the 'system.403' route controller.
 *
 * @see https://www.drupal.org/docs/8/api/routing-system/altering-existing-routes-and-adding-new-routes-based-on-dynamic-ones
 */
class ReplaceSystem403ControllerEventSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    /** @var \Symfony\Component\Routing\Route|null */
    $route = $collection->get('system.403');

    if ($route === null) {
      return;
    }

    // Replace the existing controller method with our own.
    $route->setDefault(
      '_controller',
      Http4xxController::class . '::on403'
    );

  }

}
