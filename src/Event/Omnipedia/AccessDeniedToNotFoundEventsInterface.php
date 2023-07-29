<?php

declare(strict_types=1);

namespace Drupal\omnipedia_access\Event\Omnipedia;

/**
 * Interface defining events for altering access denied to not found responses.
 */
interface AccessDeniedToNotFoundEventsInterface {

  /**
   * Event triggered when an access denied is about to be sent as a not found.
   *
   * @Event
   *
   * @var string
   */
  public const ACCESS_DENIED_TO_NOT_FOUND = 'omnipedia_access.access_denied_to_not_found';

}
