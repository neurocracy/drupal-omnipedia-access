<?php

namespace Drupal\omnipedia_access\EventSubscriber\Entity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityViewAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to fix Permissions by Entity caching bypassing access.
 *
 * @see https://www.drupal.org/project/permissions_by_term/issues/3222563
 *   Permissions by Term/Entity issue describing the bug and potential fixes.
 *
 * @todo Can we generalize this so it checks for any taxonomy term field that's
 *   controlled by Permissions by Term so we avoid hard coding a field name?
 */
class PermissionsByEntityAccessCacheEventSubscriber implements EventSubscriberInterface {

  /**
   * The episode tiers field name to check for.
   */
  protected const FIELD_NAME = 'field_episode_tier';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_VIEW_ALTER => 'onEntityViewAlter',
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

    // Bail if this entity isn't a fieldable entity or it doesn't have the
    // episode tiers field.
    if (
      !($entity instanceof FieldableEntityInterface) ||
      !$entity->hasField(self::FIELD_NAME)
    ) {
      return;
    }

    /** @var array The entity view build/render array. */
    $build = $event->getBuild();

    $build['#cache']['tags'][] = 'permissions_by_term:access_result_cache';

  }

}
