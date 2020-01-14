<?php

/**
 * @file
 * Builds placeholder replacement tokens for catalog_item-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function catalog_item_token_info() {
  $type = [
    'name' => t('Catalog Items'),
    'description' => t('Tokens related to individual content items, or "catalog_items".'),
    'needs-data' => 'catalog_item',
  ];

  // Core tokens for catalog_items.
  $catalog_item['nid'] = [
    'name' => t("Content ID"),
    'description' => t('The unique ID of the content item, or "catalog_item".'),
  ];
  $catalog_item['vid'] = [
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the catalog_item's latest revision."),
  ];
  $catalog_item['catalog'] = [
    'name' => t("Catalog"),
  ];
  $catalog_item['catalog-name'] = [
    'name' => t("Catalog name"),
    'description' => t("The human-readable name of the catalog."),
  ];
  $catalog_item['title'] = [
    'name' => t("Title"),
  ];
  $catalog_item['description'] = [
    'name' => t("Description"),
    'description' => t("The main description text of the catalog_item."),
  ];
  $catalog_item['summary'] = [
    'name' => t("Summary"),
    'description' => t("The summary of the catalog_item's main description text."),
  ];
  $catalog_item['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the catalog_item is written in.'),
  ];
  $catalog_item['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the catalog_item."),
  ];
  $catalog_item['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the catalog_item's edit page."),
  ];

  // Chained tokens for catalog_items.
  $catalog_item['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $catalog_item['changed'] = [
    'name' => t("Date changed"),
    'description' => t("The date the catalog_item was most recently updated."),
    'type' => 'date',
  ];
  $catalog_item['curator'] = [
    'name' => t("Curator"),
    'type' => 'user',
  ];

  return [
    'types' => ['catalog_item' => $type],
    'tokens' => ['catalog_item' => $catalog_item],
  ];
}

/**
 * Implements hook_tokens().
 */
function catalog_item_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = [];

  if ($type == 'catalog_item' && !empty($data['catalog_item'])) {
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item = $data['catalog_item'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the catalog_item.
        case 'nid':
          $replacements[$original] = $catalog_item->id();
          break;

        case 'vid':
          $replacements[$original] = $catalog_item->getRevisionId();
          break;

        case 'catalog':
          $replacements[$original] = $catalog_item->getCatalog();
          break;

        case 'catalog-name':
          $type_name = catalog_item_get_catalog_label($catalog_item);
          $replacements[$original] = $type_name;
          break;

        case 'title':
          $replacements[$original] = $catalog_item->getTitle();
          break;

        case 'description':
        case 'summary':
          $translation = \Drupal::service('entity.repository')->getTranslationFromContext($catalog_item, $langcode, ['operation' => 'catalog_item_tokens']);
          if ($translation->hasField('description') && ($items = $translation->get('description')) && !$items->isEmpty()) {
            $item = $items[0];
            // If the summary was requested and is not empty, use it.
            if ($name == 'summary' && !empty($item->summary)) {
              $output = $item->summary_processed;
            }
            // Attempt to provide a suitable version of the 'description' field.
            else {
              $output = $item->processed;
              // A summary was requested.
              if ($name == 'summary') {
                // Generate an optionally trimmed summary of the description field.

                // Get the 'trim_length' size used for the 'teaser' mode, if
                // present, or use the default trim_length size.
                $display_options = \Drupal::service('entity_display.repository')
                  ->getViewDisplay('catalog_item', $catalog_item->getCatalog(), 'teaser')
                  ->getComponent('description');
                if (isset($display_options['settings']['trim_length'])) {
                  $length = $display_options['settings']['trim_length'];
                }
                else {
                  $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('text_summary_or_trimmed');
                  $length = $settings['trim_length'];
                }

                $output = text_summary($output, $item->format, $length);
              }
            }
            // "processed" returns a \Drupal\Component\Render\MarkupInterface
            // via check_markup().
            $replacements[$original] = $output;
          }
          break;

        case 'langcode':
          $replacements[$original] = $catalog_item->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $catalog_item->toUrl('canonical', $url_options)->toString();
          break;

        case 'edit-url':
          $replacements[$original] = $catalog_item->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'curator':
          $account = $catalog_item->getOwner() ? $catalog_item->getOwner() : User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($catalog_item->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($catalog_item->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($curator_tokens = $token_service->findWithPrefix($tokens, 'curator')) {
      $replacements += $token_service->generate('user', $curator_tokens, ['user' => $catalog_item->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $catalog_item->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $catalog_item->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
