/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/catalog_item/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.catalog_itemDetailsSummaries = {
    attach: function attach(context) {
      var $context = $(context);

      $context.find('.catalog_item-form-curator').drupalSetSummary(function (context) {
        var $curatorContext = $(context);
        var name = $curatorContext.find('.field--name-uid input').val();
        var date = $curatorContext.find('.field--name-created input').val();

        if (name && date) {
          return Drupal.t('Added by @name on @date', {
            '@name': name,
            '@date': date
          });
        }
        if (name) {
          return Drupal.t('Added by @name', { '@name': name });
        }
        if (date) {
          return Drupal.t('Added on @date', { '@date': date });
        }
      });

      $context.find('.catalog_item-form-options').drupalSetSummary(function (context) {
        var $optionsContext = $(context);
        var vals = [];

        if ($optionsContext.find('input').is(':checked')) {
          $optionsContext.find('input:checked').next('label').each(function () {
            vals.push(Drupal.checkPlain($.trim($(this).text())));
          });
          return vals.join(', ');
        }

        return Drupal.t('Not promoted');
      });
    }
  };
})(jQuery, Drupal, drupalSettings);