<?php

namespace Drupal\omnipedia_access\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_access\Service\PermissionsByTermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action to rebuild node access.
 *
 * @Action(
 *   id       = "omnipedia_access_rebuild_node_access",
 *   label    = @Translation("Rebuild access"),
 *   type     = "node",
 *   category = @Translation("Omnipedia"),
 * )
 */
class RebuildNodeAccess extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The Omnipedia Permissions by Term helper service.
   *
   * @var \Drupal\omnipedia_access\Service\PermissionsByTermInterface
   */
  protected $permissionsByTerm;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_access\Service\PermissionsByTermInterface $permissionsByTerm
   *   The Omnipedia Permissions by Term helper service.
   */
  public function __construct(
    array $configuration, $pluginId, $pluginDefinition,
    PermissionsByTermInterface $permissionsByTerm
  ) {

    parent::__construct(
      $configuration, $pluginId, $pluginDefinition
    );

    $this->permissionsByTerm = $permissionsByTerm;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('omnipedia_access.permissions_by_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(
    $node, AccountInterface $account = null, $returnAsObject = false
  ) {

    /** @var \Drupal\Core\Access\AccessResultInterface */
    $access = $node->access('update', $account, true);

    return $returnAsObject ? $access : $access->isAllowed();

  }

  /**
   * {@inheritdoc}
   */
  public function execute($node = null) {
    $this->permissionsByTerm->rebuildAccessForNode($node->nid->getString());
  }

}
