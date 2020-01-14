<?php

namespace Drupal\catalog_item\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render a single catalog_item in preview.
 */
class CatalogItemPreviewController extends EntityViewController {

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Creates an CatalogItemViewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL) {
    parent::__construct($entity_type_manager, $renderer);
    if (!$entity_repository) {
      @trigger_error('The entity.repository service must be passed to CatalogItemPreviewController::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_repository = \Drupal::service('entity.repository');
    }
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $catalog_item_preview, $view_mode_id = 'full', $langcode = NULL) {
    $catalog_item_preview->preview_view_mode = $view_mode_id;
    $build = parent::view($catalog_item_preview, $view_mode_id);

    $build['#attached']['library'][] = 'catalog_item/drupal.catalog_item.preview';

    // Don't render cache previews.
    unset($build['#cache']);

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single catalog_item in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $catalog_item_preview
   *   The current catalog_item.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $catalog_item_preview) {
    return $this->entityRepository->getTranslationFromContext($catalog_item_preview)->label();
  }

}
