<?php

namespace Drupal\omnipedia_access\EventSubscriber\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\permissions_by_entity\Service\AccessCheckerInterface;
use Drupal\permissions_by_term\Cache\AccessResultCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to fix Permissions by Entity bypassing other access checks.
 *
 * This is adapted from the Permissions by Entity module, with one crucial
 * difference: instead of returning AccessResult::allowed(), we instead return
 * a neutral result so that other access restrictions are correctly applied by
 * Drupal, i.e. users that don't have permission to view unpublished content
 * are correctly denied access to unpublished content. By default, Permissions
 * by Entity would allow a user access to unpublished content if they have term
 * permissions to view the content.
 *
 * This overrides the logic in \permissions_by_entity_entity_access() because
 * that function checks if an access result is already cached for the entity,
 * and will return that rather than performing a check. As long as our hook is
 * invoked before that, we can perform our logic and cache the access result for
 * \permissions_by_entity_entity_access() to find.
 *
 * Additionally, this uses dependency injection best practices, which are not
 * possible in a hook function.
 *
 * @see \permissions_by_entity_entity_access()
 */
class PermissionsByEntityAccessCheckEventSubscriber implements EventSubscriberInterface {

  /**
   * The Permissions by Entity access checker service.
   *
   * @var \Drupal\permissions_by_entity\Service\AccessCheckerInterface
   */
  protected $accessChecker;

  /**
   * The Permissions by Term access result cache.
   *
   * @var \Drupal\permissions_by_term\Cache\AccessResultCache
   */
  protected $accessResultCache;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\permissions_by_entity\Service\AccessCheckerInterface $accessChecker
   *   The Permissions by Entity access checker service.
   *
   * @param \Drupal\permissions_by_term\Cache\AccessResultCache $accessResultCache
   *   The Permissions by Term access result cache.
   */
  public function __construct(
    AccessCheckerInterface  $accessChecker,
    AccessResultCache       $accessResultCache
  ) {
    $this->accessChecker      = $accessChecker;
    $this->accessResultCache  = $accessResultCache;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_ACCESS => 'onEntityAccess',
    ];
  }

  /**
   * Entity access event handler.
   *
   * Note that unlike implementing the \hook_entity_access() hook function,
   * we don't return an access result, nor are we required to always set one via
   * EntityAccessEvent::addAccessResult() as the event creates a neutral access
   * result by default on its own, and then combines any additional access
   * result passed to EntityAccessEvent::addAccessResult() with the neutral
   * result via AccessResultInterface::orIf().
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent $event
   *   The event object.
   *
   * @see \Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent::addAccessResult()
   */
  public function onEntityAccess(EntityAccessEvent $event): void {

    /** @var \Drupal\Core\Entity\EntityInterface */
    $entity = $event->getEntity();

    /** @var \Drupal\Core\Access\AccessResultNeutral */
    $accessResult = AccessResult::neutral();

    /** @var \Drupal\Core\Session\AccountInterface */
    $account = $event->getAccount();

    // If this isn't a fieldable entity that's saved to storage which is being
    // viewed, bail as we don't have anything to do.
    if (!(
      $event->getOperation() === 'view' &&
      $entity instanceof FieldableEntityInterface &&
      !$entity->isNew()
    )) {
      return;
    }

    // If a cached access result already exists, add it and return here.
    if ($this->accessResultCache->hasAccessResultsCache(
      $account->id(), $entity->id()
    )) {

      $event->addAccessResult($this->accessResultCache->getAccessResultsCache(
        $account->id(), $entity->id()
      ));

      return;

    }

    // Check if the entity is using term-based access control and create/add an
    // access result if so.
    if ($this->accessChecker->isAccessControlled($entity)) {

      /** @var \Drupal\Core\Access\AccessResultNeutral|\Drupal\Core\Access\AccessResultForbidden */
      $accessResult = AccessResult::forbiddenIf(
        !$this->accessChecker->isAccessAllowed($entity, $account->id()),
        'Access revoked by permissions_by_entity module.'
      );

      $event->addAccessResult($accessResult);

    }

    // Always cache the result even if the entity is not using term-based access
    // control to prevent doing unnecessary work.
    $this->accessResultCache->setAccessResultsCache(
      $account->id(), $entity->id(), $accessResult
    );

  }

}
