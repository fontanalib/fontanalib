<?php

namespace Drupal\catalog_item;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * View builder handler for catalog_items.
 */
class CatalogItemViewBuilder extends EntityViewBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\catalog_item\CatalogItemInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('links')) {
        $build[$id]['links'] = [
          '#lazy_builder' => [
            get_called_class() . '::renderLinks', [
              $entity->id(),
              $view_mode,
              $entity->language()->getId(),
              !empty($entity->in_preview),
              $entity->isDefaultRevision() ? NULL : $entity->getLoadedRevisionId(),
            ],
          ],
        ];
      }

      // Add Language field text element to catalog_item render array.
      if ($display->getComponent('langcode')) {
        $build[$id]['langcode'] = [
          '#type' => 'item',
          '#title' => t('Language'),
          '#markup' => $entity->language()->getName(),
          '#prefix' => '<div id="field-language-display">',
          '#suffix' => '</div>',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);

    // Don't cache catalog_items that are in 'preview' mode.
    if (isset($defaults['#cache']) && isset($entity->in_preview)) {
      unset($defaults['#cache']);
    }

    return $defaults;
  }

  /**
   * #lazy_builder callback; builds a catalog_item's links.
   *
   * @param string $catalog_item_entity_id
   *   The catalog_item entity ID.
   * @param string $view_mode
   *   The view mode in which the catalog_item entity is being viewed.
   * @param string $langcode
   *   The language in which the catalog_item entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the catalog_item is currently being previewed.
   * @param $revision_id
   *   (optional) The identifier of the catalog_item revision to be loaded. If none
   *   is provided, the default revision will be loaded.
   *
   * @return array
   *   A renderable array representing the catalog_item links.
   */
  public static function renderLinks($catalog_item_entity_id, $view_mode, $langcode, $is_in_preview, $revision_id = NULL) {
    $links = [
      '#theme' => 'links__catalog_item',
      '#pre_render' => [[Link::class, 'preRenderLinks']],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      $storage = \Drupal::entityTypeManager()->getStorage('catalog_item');
      /** @var \Drupal\catalog_item\CatalogItemInterface $revision */
      $revision = !isset($revision_id) ? $storage->load($catalog_item_entity_id) : $storage->loadRevision($revision_id);
      $entity = $revision->getTranslation($langcode);
      $links['catalog_item'] = static::buildLinks($entity, $view_mode);

      // Allow other modules to alter the catalog_item links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
      ];
      \Drupal::moduleHandler()->alter('catalog_item_links', $links, $entity, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default links (Read more) for a catalog_item.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $entity
   *   The catalog_item object.
   * @param string $view_mode
   *   A view mode identifier.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(CatalogItemInterface $entity, $view_mode) {
    $links = [];

    // Always display a read more link on teasers because we have no way
    // to know when a teaser view is different than a full view.
    if ($view_mode == 'teaser') {
      $catalog_item_title_stripped = strip_tags($entity->label());
      $links['catalog_item-readmore'] = [
        'title' => t('Read more<span class="visually-hidden"> about @title</span>', [
          '@title' => $catalog_item_title_stripped,
        ]),
        'url' => $entity->toUrl(),
        'language' => $entity->language(),
        'attributes' => [
          'rel' => 'tag',
          'title' => $catalog_item_title_stripped,
        ],
      ];
    }

    return [
      '#theme' => 'links__catalog_item__catalog_item',
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    $callbacks = parent::trustedCallbacks();
    $callbacks[] = 'renderLinks';
    return $callbacks;
  }

}
