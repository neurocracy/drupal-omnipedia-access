<?php

declare(strict_types=1);

namespace Drupal\omnipedia_access\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for HTTP 4xx responses.
 */
class Http4xxController implements ContainerInjectionInterface {

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy service.
   */
  public function __construct(
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * The 403 content.
   *
   * This overrides the default 403 response content in the following ways:
   *
   * - If the user is anonymous, it provides a log in link.
   *
   * - Changes spelling of "authorized" to "authorised".
   *
   * @return array
   *   A render array containing the message to display for 403 pages.
   *
   * @see \Drupal\system\Controller\Http4xxController::on403()
   *   The original Drupal core controller method this replaces.
   *
   * @see \Drupal\omnipedia_access\EventSubscriber\Routing\ReplaceSystem403ControllerEventSubscriber::alterRoutes()
   *   Replaces the 'system.403' route controller method with this one.
   */
  public function on403(): array {

    // If the user is anonymous, offer a log in link.
    if ($this->currentUser->isAnonymous()) {

      /** @var array Render array to be returned. */
      $renderArray = [];

      /** @var \Drupal\Core\Link */
      $link = Link::createFromRoute(
        $this->t('log in'),
        'user.login'
      );

      /** @var \Drupal\Core\GeneratedLink The generated link object, containing the link and any related cache metadata. */
      $generatedLink = $link->toString();

      // Apply the generated link's cache metadata to our render array so that
      // it's not lost when it's cast to a string.
      $generatedLink->applyTo($renderArray);

      $renderArray['#markup'] = $this->t(
        'You are not authorised to access this page. If you have an account, you can @login.',
        ['@login' => $generatedLink]
      );

      return $renderArray;

    }

    // If the user is authenticated, just provide the access denied message.
    return [
      '#markup' => $this->t('You are not authorised to access this page.'),
    ];

  }

}
