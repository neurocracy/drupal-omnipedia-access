<?php

declare(strict_types=1);

namespace Drupal\omnipedia_access\EventSubscriber\Response;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Event subscriber to output a 404 not found instead of a 403 access denied.
 *
 * @see https://drupal.stackexchange.com/a/231263
 *   Loosely based on this Drupal Answers answer.
 */
class AccessDeniedToNotFoundEventSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The current user proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs this event subscriber; saves dependencies.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy service.
   */
  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handles a 403 error for HTML; returns a 404 to hide content.
   *
   * Additionally, by always returning a 404, this hides admin paths that may
   * leak the presence or lack thereof of a module or configuration.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event): void {

    if ($this->currentUser->hasPermission('bypass node access')) {

      $event->setException(new AccessDeniedHttpException());

      return;

    }

    /** @var \Symfony\Component\HttpFoundation\Request */
    $request = $event->getRequest();

    // Allow the 403 to be output if viewing the base path, since that obviously
    // exists. We can't use
    // \Drupal\Core\Path\PathMatcherInterface::isFrontPage() since that will
    // match both when the base path ('/') is accessed and when the default
    // front page path is accessed (e.g. '/node/\d+' or the node's path alias);
    // i.e. it doesn't make a distinction between the actual paths.
    if ($request->getPathInfo() !== '/') {
      $event->setException(new NotFoundHttpException());
    }

  }

}