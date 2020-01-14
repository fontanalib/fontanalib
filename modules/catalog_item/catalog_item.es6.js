/**
 * @file
 * Defines Javascript behaviors for the catalog_item module.
 */

(function($, Drupal, drupalSettings) {
  /**
   * Behaviors for tabs in the catalog_item edit form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the catalog_item edit form.
   */
  Drupal.behaviors.catalog_itemDetailsSummaries = {
    attach(context) {
      const $context = $(context);

      $context.find('.catalog_item-form-curator').drupalSetSummary(context => {
        const $curatorContext = $(context);
        const name = $curatorContext.find('.field--name-uid input').val();
        const date = $curatorContext.find('.field--name-created input').val();

        if (name && date) {
          return Drupal.t('Added By @name on @date', {
            '@name': name,
            '@date': date,
          });
        }
        if (name) {
          return Drupal.t('Added By @name', { '@name': name });
        }
        if (date) {
          return Drupal.t('Added on @date', { '@date': date });
        }
      });

      $context.find('.catalog_item-form-options').drupalSetSummary(context => {
        const $optionsContext = $(context);
        const vals = [];

        if ($optionsContext.find('input').is(':checked')) {
          $optionsContext
            .find('input:checked')
            .next('label')
            .each(function() {
              vals.push(Drupal.checkPlain($.trim($(this).text())));
            });
          return vals.join(', ');
        }

        return Drupal.t('Not promoted');
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
