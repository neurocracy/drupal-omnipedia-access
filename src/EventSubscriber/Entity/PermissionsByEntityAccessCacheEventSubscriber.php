<?php

namespace Drupal\omnipedia_access\EventSubscriber\Entity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityViewAlterEvent;
use Drupal\permissions_by_entity\Service\AccessCheckerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to fix Permissions by Entity caching bypassing access.
 *
 * This works by adding the 'permissions_by_term:access_result_cache' cache tag
 * to the entity's build/render array if it's a fieldable entity that's using
 * term-based access control.
 *
 * @see https://www.drupal.org/project/permissions_by_term/issues/3222563
 *   Permissions by Term/Entity issue describing the bug and potential fixes.
 */
class PermissionsByEntityAccessCacheEventSubscriber implements EventSubscriberInterface {

  /**
   * The Permissions by Entity access checker service.
   *
   * @var \Drupal\permissions_by_entity\Service\AccessCheckerInterface
   */
  protected $accessChecker;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\permissions_by_entity\Service\AccessCheckerInterface $accessChecker
   *   The Permissions by Entity access checker service.
   */
  public function __construct(AccessCheckerInterface $accessChecker) {
    $this->accessChecker = $accessChecker;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityHookEvents::ENTITY_VIEW_ALTER => 'onEntityViewAlter',
    ];
  }

  /**
   * Entity view alter event handler.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityViewAlterEvent $event
   *   The event object.
   */
  public function onEntityViewAlter(EntityViewAlterEvent $event): void {

    /** @var \Drupal\Core\Entity\EntityInterface */
    $entity = $event->getEntity();

    // Bail if this entity isn't a fieldable entity or it isn't using term-based
    // access control.
    if (
      !($entity instanceof FieldableEntityInterface) ||
      !$this->accessChecker->isAccessControlled($entity)
    ) {
      return;
    }

    /** @var array The entity view build/render array. */
    $build = $event->getBuild();

    $build['#cache']['tags'][] = 'permissions_by_term:access_result_cache';

  }

}
