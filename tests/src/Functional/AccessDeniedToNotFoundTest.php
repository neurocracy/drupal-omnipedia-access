<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_access\Functional;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests for the Omnipedia access denied to not found response functionality.
 *
 * @group omnipedia
 *
 * @group omnipedia_access
 *
 * @see \Drupal\Tests\system\Functional\System\AccessDeniedTest
 *
 * @see \Drupal\Tests\system\Functional\System\FrontPageTest
 *
 * @see \Drupal\Tests\system\Functional\System\PageNotFoundTest
 */
class AccessDeniedToNotFoundTest extends BrowserTestBase {

  /**
   * The Drupal configuration object factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected readonly ConfigFactoryInterface $configFactory;

  /**
   * The Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected readonly EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'omnipedia_access', 'system', 'user'];

  /**
   * Array of Url objects to check, keyed by their route name.
   *
   * @var \Drupal\Core\Url[]
   */
  protected array $adminUrlsToCheck = [];

  /**
   * Various admin routes to check for 404s or 403s, depending on the user.
   *
   * @var string[]
   */
  protected array $adminRoutesToCheck = [
    // Path: 'admin'
    'system.admin',
    // Path: 'admin/content'
    'system.admin_content',
    // Path: 'admin/structure'
    'system.admin_structure',
    // Path: 'admin/appearance'
    'system.themes_page',
    // Path: 'admin/modules'
    'system.modules_list',
    // Path: 'admin/config/people/accounts'
    'entity.user.admin_form',
    // Path: 'admin/config/system/site-information'
    'system.site_information_settings',
    // Path: 'admin/reports'
    'system.admin_reports',
    // Path: 'admin/reports/status/php'
    'system.php',
  ];

  /**
   * Various admin routes to expose 403s for testing our event.
   *
   * @var string[]
   *
   * @see \Drupal\omnipedia_access_test\EventSubscriber\Omnipedia\AccessDeniedToNotFoundEventSubscriber::$adminRoutesToExpose
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
   * The name of the permission a role must have to see 403s instead of 404s.
   */
  protected const BYPASS_NOT_FOUND_PERMISSION = 'omnipedia_access bypass not found';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->configFactory = $this->container->get('config.factory');

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    foreach ($this->adminRoutesToCheck as $routeName) {

      $this->adminUrlsToCheck[$routeName] = Url::fromRoute($routeName);

    }

  }

  /**
   * Test that anonymous users get 404 not found on various admin routes.
   */
  public function testAnonymousAdminNotFound(): void {

    foreach ($this->adminUrlsToCheck as $path) {

      $this->drupalGet($path);

      $this->assertSession()->statusCodeEquals(404);

    }

  }

  /**
   * Test that authenticated users get 404 not found on various admin routes.
   */
  public function testAuthenticatedAdminNotFound(): void {

    $this->drupalLogin($this->createUser([]));

    foreach ($this->adminUrlsToCheck as $path) {

      $this->drupalGet($path);

      $this->assertSession()->statusCodeEquals(404);

    }

  }

  /**
   * Test that authenticated users with the bypass permission get 403s.
   */
  public function testAuthenticatedAccessDeniedWithBypassPermission(): void {

    /** @var \Drupal\user\RoleInterface */
    $authenticatedRole = $this->entityTypeManager->getStorage(
      'user_role',
    )->load(RoleInterface::AUTHENTICATED_ID);

    $authenticatedRole->grantPermission(self::BYPASS_NOT_FOUND_PERMISSION);

    $authenticatedRole->trustData()->save();

    $this->drupalLogin($this->createUser([]));

    foreach ($this->adminUrlsToCheck as $path) {

      $this->drupalGet($path);

      $this->assertSession()->statusCodeEquals(403);

    }

  }

  /**
   * Test that anonymous users get 403 access denied on the front page.
   */
  public function testAnonymousFrontPageAccessDenied(): void {

    /** @var \Drupal\user\RoleInterface */
    $anonymousRole = $this->entityTypeManager->getStorage(
      'user_role',
    )->load(RoleInterface::ANONYMOUS_ID);

    $anonymousRole->revokePermission('access content');

    $anonymousRole->trustData()->save();

    $this->drupalCreateContentType(['type' => 'page']);

    /** @var \Drupal\node\NodeInterface */
    $node = $this->drupalCreateNode([
      'title'   => $this->randomMachineName(8),
      'status'  => NodeInterface::PUBLISHED,
    ]);

    /** @var \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('system.site');

    $config->set('page.front', $node->toUrl()->toString())->save();

    $this->drupalGet('');

    $this->assertSession()->statusCodeEquals(403);

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
      'status'  => NodeInterface::NOT_PUBLISHED,
    ]);

    $this->drupalGet($node->toUrl()->toString());

    $this->assertSession()->statusCodeEquals(404);

    /** @var \Drupal\user\RoleInterface */
    $anonymousRole = $this->entityTypeManager->getStorage(
      'user_role',
    )->load(RoleInterface::ANONYMOUS_ID);

    $anonymousRole->revokePermission('access content');

    $anonymousRole->trustData()->save();

    /** @var \Drupal\node\NodeInterface */
    $node = $this->drupalCreateNode([
      'title'   => $this->randomMachineName(8),
      'status'  => NodeInterface::PUBLISHED,
    ]);

    $this->drupalGet($node->toUrl()->toString());

    $this->assertSession()->statusCodeEquals(404);

  }

  /**
   * Test that our event correctly allows exposing 403s.
   */
  public function testEvent(): void {

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface */
    $moduleInstaller = $this->container->get('module_installer');

    // Install the test module which provides an event subscriber that exposes
    // a series of admin routes as 403s.
    $moduleInstaller->install([
      'omnipedia_access_test',
    ]);

    foreach ($this->adminRoutesToExpose as $routeName) {

      $this->drupalGet($this->adminUrlsToCheck[$routeName]);

      $this->assertSession()->statusCodeEquals(403);

    }

    // Check the other admin routes to verify that they still result in a 404.
    foreach (\array_diff(
      $this->adminRoutesToCheck, $this->adminRoutesToExpose,
    ) as $routeName) {

      $this->drupalGet($this->adminUrlsToCheck[$routeName]);

      $this->assertSession()->statusCodeEquals(404);

    }

  }

}
