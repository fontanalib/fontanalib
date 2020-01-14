<?php

namespace Drupal\catalog_item\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\catalog_item\CatalogItemStorageInterface;
use Drupal\catalog_item\CatalogInterface;
use Drupal\catalog_item\CatalogItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for CatalogItem routes.
 */
class CatalogItemController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a CatalogItemController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    if (!$entity_repository) {
      @trigger_error('The entity.repository service must be passed to CatalogItemController::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_repository = \Drupal::service('entity.repository');
    }
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * Displays add catalog item links for available catalog types.
   *
   * Redirects to catalog-item/add/[catalog] if only one catalog type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the catalog types that can be added; however,
   *   if there is only one catalog type defined for the site, the function
   *   will return a RedirectResponse to the catalog item add page for that one catalog
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'catalog_item_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('catalog')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use catalogs the user has access to.
    foreach ($this->entityTypeManager()->getStorage('catalog')->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler('catalog_item')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the catalog_item/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('catalog_item.add', ['catalog' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }



  /**
   * Displays a catalog_item revision.
   *
   * @param int $catalog_item_revision
   *   The catalog_item revision ID.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionShow($catalog_item_revision) {
    $catalog_item = $this->entityTypeManager()->getStorage('catalog_item')->loadRevision($catalog_item_revision);
    $catalog_item = $this->entityRepository->getTranslationFromContext($catalog_item);
    $catalog_item_view_controller = new CatalogItemViewController($this->entityTypeManager(), $this->renderer, $this->currentUser(), $this->entityRepository);
    $page = $catalog_item_view_controller->view($catalog_item);
    unset($page['catalog_items'][$catalog_item->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for a catalog_item revision.
   *
   * @param int $catalog_item_revision
   *   The catalog_item revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($catalog_item_revision) {
    $catalog_item = $this->entityTypeManager()->getStorage('catalog_item')->loadRevision($catalog_item_revision);
    return $this->t('Revision of %title from %date', ['%title' => $catalog_item->label(), '%date' => $this->dateFormatter->format($catalog_item->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a catalog_item.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   A catalog_item object.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview(CatalogItemInterface $catalog_item) {
    $account = $this->currentUser();
    $langcode = $catalog_item->language()->getId();
    $langname = $catalog_item->language()->getName();
    $languages = $catalog_item->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $catalog_item_storage = $this->entityTypeManager()->getStorage('catalog_item');
    $type = $catalog_item->getCatalog();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $catalog_item->label()]) : $this->t('Revisions for %title', ['%title' => $catalog_item->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert $type catalog revisions") || $account->hasPermission('revert all catalog revisions') || $account->hasPermission('administer catalog items')) && $catalog_item->access('update'));
    $delete_permission = (($account->hasPermission("delete $type catalog revisions") || $account->hasPermission('delete all catalog revisionss') || $account->hasPermission('administer catalog items')) && $catalog_item->access('delete'));

    $rows = [];
    $default_revision = $catalog_item->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($catalog_item, $catalog_item_storage) as $vid) {
      /** @var \Drupal\catalog_item\CatalogItemInterface $revision */
      $revision = $catalog_item_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl($date, new Url('entity.catalog_item.revision', ['catalog_item' => $catalog_item->id(), 'catalog_item_revision' => $vid]))->toString();
        }
        else {
          $link = $catalog_item->toLink($date)->toString();
          $current_revision_displayed = TRUE;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($is_current_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $vid < $catalog_item->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => $has_translations ?
                Url::fromRoute('catalog_item.revision_revert_translation_confirm', ['catalog_item' => $catalog_item->id(), 'catalog_item_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('catalog_item.revision_revert_confirm', ['catalog_item' => $catalog_item->id(), 'catalog_item_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('catalog_item.revision_delete_confirm', ['catalog_item' => $catalog_item->id(), 'catalog_item_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['catalog_item_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['catalog_item/drupal.catalog_item.admin'],
      ],
      '#attributes' => ['class' => 'catalog_item-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * The _title_callback for the catalog_item.add route.
   *
   * @param \Drupal\catalog_item\CatalogInterface $catalog
   *   The current catalog_item.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CatalogInterface $catalog) {
    return $this->t('Create @name catalog item', ['@name' => $catalog->label()]);
  }

  /**
   * Gets a list of catalog_item revision IDs for a specific catalog_item.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   The catalog_item entity.
   * @param \Drupal\catalog_item\CatalogItemStorageInterface $catalog_item_storage
   *   The catalog_item storage handler.
   *
   * @return int[]
   *   CatalogItem revision IDs (in descending order).
   */
  protected function getRevisionIds(CatalogItemInterface $catalog_item, CatalogItemStorageInterface $catalog_item_storage) {
    $result = $catalog_item_storage->getQuery()
      ->allRevisions()
      ->condition($catalog_item->getEntityType()->getKey('id'), $catalog_item->id())
      ->sort($catalog_item->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}
