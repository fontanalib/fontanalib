<?php

namespace Drupal\catalog_item\ConfigTranslation;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a configuration mapper for catalog_item types.
 */
class CatalogMapper extends ConfigEntityMapper {

  /**
   * {@inheritdoc}
   */
  public function setEntity(ConfigEntityInterface $entity) {
    parent::setEntity($entity);

    // Adds the title label to the translation form.
    $catalog = $entity->id();
    $config = $this->configFactory->get("core.base_field_override.catalog_item.$catalog.title");
    if (!$config->isNew()) {
      $this->addConfigName($config->getName());
    }
  }

}
