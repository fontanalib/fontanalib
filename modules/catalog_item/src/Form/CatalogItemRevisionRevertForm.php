<?php

namespace Drupal\catalog_item\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\catalog_item\CatalogItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a catalog_item revision.
 *
 * @internal
 */
class CatalogItemRevisionRevertForm extends ConfirmFormBase {

  /**
   * The catalog_item revision.
   *
   * @var \Drupal\catalog_item\CatalogItemInterface
   */
  protected $revision;

  /**
   * The catalog_item storage.
   *
   * @var \Drupal\catalog_item\CatalogItemStorageInterface
   */
  protected $catalog_itemStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new CatalogItemRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $catalog_item_storage
   *   The catalog_item storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityStorageInterface $catalog_item_storage, DateFormatterInterface $date_formatter, TimeInterface $time) {
    $this->catalog_itemStorage = $catalog_item_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('catalog_item'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'catalog_item_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
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
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
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
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->setRevisionUserId($this->currentUser()->id());
    $this->revision->setRevisionCreationTime($this->time->getRequestTime());
    $this->revision->setChangedTime($this->time->getRequestTime());
    $this->revision->save();

    $this->logger('catalog')->notice('@type: reverted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()
      ->addStatus($this->t('@type %title has been reverted to the revision from %revision-date.', [
        '@type' => catalog_item_get_type_label($this->revision),
        '%title' => $this->revision->label(),
        '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
      ]));
    $form_state->setRedirect(
      'entity.catalog_item.version_history',
      ['catalog_item' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\catalog_item\CatalogItemInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(CatalogItemInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
