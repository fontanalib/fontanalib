<?php

namespace Drupal\catalog_item\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\catalog_item\Entity\CatalogItem;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current catalog_item as a context on catalog_item routes.
 */
class CatalogItemRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CatalogItemRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = EntityContextDefinition::create('catalog_item')->setRequired(FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['catalog_item'])) {
      if ($catalog_item = $this->routeMatch->getParameter('catalog_item')) {
        $value = $catalog_item;
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'catalog_item.add') {
      $catalog = $this->routeMatch->getParameter('catalog');
      $value = CatalogItem::create(['catalog' => $catalog->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['catalog_item'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('catalog_item', $this->t('Catalog Item from URL'));
    return ['catalog_item' => $context];
  }

}
