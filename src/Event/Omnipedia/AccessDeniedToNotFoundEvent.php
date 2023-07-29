<?php

declare(strict_types=1);

namespace Drupal\omnipedia_access\Event\Omnipedia;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Event triggered when an access denied is about to be sent as a not found.
 */
class AccessDeniedToNotFoundEvent extends Event {

  /**
   * An HTTP exception that should be sent.
   *
   * @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  protected HttpExceptionInterface $throwable;

  /**
   * Constructs this event; saves dependencies.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   */
  public function __construct(
    protected readonly Request $request,
  ) {}

  /**
   * Set the throwable for this request.
   *
   * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $throwable
   */
  public function setThrowable(HttpExceptionInterface $throwable): void {
    $this->throwable = $throwable;
  }

  /**
   * Determine if a throwable has been set for this event.
   *
   * @return boolean
   */
  public function hasThrowable(): bool {
    return isset($this->throwable);
  }

  /**
   * Get the throwable if one has been set.
   *
   * @return \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|null
   */
  public function getThrowable(): ?HttpExceptionInterface {

    if ($this->hasThrowable() === false) {
      return null;
    }

    return $this->throwable;

  }

  /**
   * Get the Symfony Request object associated with this event.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   */
  public function getRequest(): Request {
    return $this->request;
  }

}
