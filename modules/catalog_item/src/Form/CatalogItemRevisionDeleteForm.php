<?php

namespace Drupal\catalog_item\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a catalog_item revision.
 *
 * @internal
 */
class CatalogItemRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The catalog_item revision.
   *
   * @var \Drupal\catalog_item\CatalogItemInterface
   */
  protected $revision;

  /**
   * The catalog_item storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $catalog_itemStorage;

  /**
   * The catalog_item type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $catalog_itemTypeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new CatalogItemRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $catalog_item_storage
   *   The catalog_item storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $catalog_storage
   *   The catalog_item type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $catalog_item_storage, EntityStorageInterface $catalog_storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->catalog_itemStorage = $catalog_item_storage;
    $this->catalog_itemTypeStorage = $catalog_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('catalog_item'),
      $entity_type_manager->getStorage('catalog'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'catalog_item_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.catalog_item.version_history', ['catalog_item' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $catalog_item_revision = NULL) {
    $this->revision = $this->catalog_itemStorage->loadRevision($catalog_item_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->catalog_itemStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('catalog')->notice('@type item: deleted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $catalog = $this->catalog_itemTypeStorage->load($this->revision->bundle())->label();
    $this->messenger()
      ->addStatus($this->t('Revision from %revision-date of @type item %title has been deleted.', [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
        '@type' => $catalog,
        '%title' => $this->revision->label(),
      ]));
    $form_state->setRedirect(
      'entity.catalog_item.canonical',
      ['catalog_item' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {catalog_item_field_revision} WHERE nid = :nid', [':nid' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.catalog_item.version_history',
        ['catalog_item' => $this->revision->id()]
      );
    }
  }

}
