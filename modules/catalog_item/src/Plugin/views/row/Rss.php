<?php

namespace Drupal\catalog_item\Plugin\views\row;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\row\RssPluginBase;

/**
 * Plugin which performs a catalog_item_view on the resulting object
 * and formats it as an RSS item.
 *
 * @ViewsRow(
 *   id = "catalog_item_rss",
 *   title = @Translation("Content"),
 *   help = @Translation("Display the content with standard catalog_item view."),
 *   theme = "views_view_row_rss",
 *   register_theme = FALSE,
 *   base = {"catalog_item_field_data"},
 *   display_types = {"feed"}
 * )
 */
class Rss extends RssPluginBase {

  // Basic properties that let the row style follow relationships.
  public $base_table = 'catalog_item_field_data';

  public $base_field = 'nid';

  // Stores the catalog_items loaded with preRender.
  public $catalog_items = [];

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'catalog_item';

  /**
   * The catalog_item storage
   *
   * @var \Drupal\catalog_item\CatalogItemStorageInterface
   */
  protected $catalog_itemStorage;

  /**
   * Constructs the Rss object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_display_repository);
    $this->catalog_itemStorage = $entity_type_manager->getStorage('catalog_item');
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm_summary_options() {
    $options = parent::buildOptionsForm_summary_options();
    $options['title'] = $this->t('Title only');
    $options['default'] = $this->t('Use site default RSS settings');
    return $options;
  }

  public function summaryTitle() {
    $options = $this->buildOptionsForm_summary_options();
    return $options[$this->options['view_mode']];
  }

  public function preRender($values) {
    $nids = [];
    foreach ($values as $row) {
      $nids[] = $row->{$this->field_alias};
    }
    if (!empty($nids)) {
      $this->catalog_items = $this->catalog_itemStorage->loadMultiple($nids);
    }
  }

  public function render($row) {
    global $base_url;

    $nid = $row->{$this->field_alias};
    if (!is_numeric($nid)) {
      return;
    }

    $display_mode = $this->options['view_mode'];
    if ($display_mode == 'default') {
      $display_mode = \Drupal::config('system.rss')->get('items.view_mode');
    }

    // Load the specified catalog_item:
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item = $this->catalog_items[$nid];
    if (empty($catalog_item)) {
      return;
    }

    $catalog_item->link = $catalog_item->toUrl('canonical', ['absolute' => TRUE])->toString();
    $catalog_item->rss_namespaces = [];
    $catalog_item->rss_elements = [
      [
        'key' => 'pubDate',
        'value' => gmdate('r', $catalog_item->getCreatedTime()),
      ],
      [
        'key' => 'dc:creator',
        'value' => $catalog_item->getOwner()->getDisplayName(),
      ],
      [
        'key' => 'guid',
        'value' => $catalog_item->id() . ' at ' . $base_url,
        'attributes' => ['isPermaLink' => 'false'],
      ],
    ];

    // The catalog_item gets built and modules add to or modify $catalog_item->rss_elements
    // and $catalog_item->rss_namespaces.

    $build_mode = $display_mode;

    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('catalog_item')
      ->view($catalog_item, $build_mode);
    unset($build['#theme']);

    if (!empty($catalog_item->rss_namespaces)) {
      $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $catalog_item->rss_namespaces);
    }
    elseif (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = [];
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
      $this->view->style_plugin->namespaces += $xml_rdf_namespaces;
    }

    $item = new \stdClass();
    if ($display_mode != 'title') {
      // We render catalog_item contents.
      $item->description = $build;
    }
    $item->title = $catalog_item->label();
    $item->link = $catalog_item->link;
    // Provide a reference so that the render call in
    // template_preprocess_views_view_row_rss() can still access it.
    $item->elements = &$catalog_item->rss_elements;
    $item->nid = $catalog_item->id();
    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    ];

    return $build;
  }

}
