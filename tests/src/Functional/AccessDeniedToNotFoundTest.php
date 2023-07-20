<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_access\Functional;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;

/**
 * Tests for the Omnipedia access denied to not found response functionality.
 *
 * @group omnipedia_access
 */
class AccessDeniedToNotFoundTest extends BrowserTestBase {

  /**
   * The Drupal configuration object factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The Drupal user role entity storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected RoleStorageInterface $roleStorage;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * Note that the Views module is required for the Node module to install the
   * 'frontpage' View, which provides the '/node' path.
   *
   * @todo Implement our own simple route for '/node' which requires the
   *   'access content' permission, and then remove the dependency on Views.
   */
  protected static $modules = [
    'node', 'omnipedia_access', 'system', 'user', 'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->configFactory = $this->container->get('config.factory');

    $this->roleStorage = $this->container->get(
      'entity_type.manager'
    )->getStorage('user_role');

  }

  /**
   * Test that anonymous users get 403 access denied on the front page.
   */
  public function testAnonymousFrontPageAccessDenied(): void {

    /** @var \Drupal\user\RoleInterface */
    $anonymousRole = $this->roleStorage->load(RoleInterface::ANONYMOUS_ID);

    $anonymousRole->revokePermission('access content');

    $anonymousRole->trustData()->save();

    /** @var \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('system.site');

    $config->set('page.front', '/node')->save();

    $this->drupalGet('');

    $this->assertSession()->statusCodeEquals(403);

  }

  /**
   * Test that anonymous users get 404 not found on various admin routes.
   */
  public function testAnonymousAdminNotFound(): void {

    $this->drupalGet('admin');

    $this->assertSession()->statusCodeEquals(404);

  }

  /**
   * Test that anonymous users get 404 not found for content they can't access.
   *
   * This checks that unpublished nodes result in a 404 with the default
   * permissions, i.e. anonymous users have the 'access content' permission, and
   * then revokes that permission to verify that a published node also results
   * in a 404 without the permission.
   */
  public function testAnonymousContentNotFound(): void {

    $this->drupalCreateContentType(['type' => 'page']);

    /** @var \Drupal\node\NodeInterface */
    $node = $this->drupalCreateNode([
      'title'   => $this->randomMachineName(8),
      'status'  => 0,
    ]);

    $this->drupalGet($node->toUrl()->toString());

    $this->assertSession()->statusCodeEquals(404);

    /** @var \Drupal\user\RoleInterface */
    $anonymousRole = $this->roleStorage->load(RoleInterface::ANONYMOUS_ID);

    $anonymousRole->revokePermission('access content');

    $anonymousRole->trustData()->save();

    /** @var \Drupal\node\NodeInterface */
    $node = $this->drupalCreateNode([
      'title'   => $this->randomMachineName(8),
      'status'  => 1,
    ]);

    $this->drupalGet($node->toUrl()->toString());

    $this->assertSession()->statusCodeEquals(404);

  }

}
