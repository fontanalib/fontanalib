<?php

namespace Drupal\fontanalib_graphql\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\fontanalib_graphql\Wrappers\QueryConnection;

/**
 * @Schema(
 *   id = "fontanalib",
 *   name = "Fontanalib schema"
 * )
 */
class FontanalibSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $builder = new ResolverBuilder();
    $registry = new ResolverRegistry();

    $this->addQueryFields($registry, $builder);
    $this->addTypeFields($registry, $builder);

    // Re-usable connection type fields.
    $this->addConnectionFields('ArticleConnection', $registry, $builder);
    $this->addConnectionFields('PageConnection', $registry, $builder);

    return $registry;
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addTypeFields(ResolverRegistry $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Author', 'name',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:user'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('display_name'))
    );
    $registry->addFieldResolver('Author', 'mail',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:user'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('mail'))
    );
    $registry->addFieldResolver('FeaturedImage', 'thumbnail',
    $builder->compose(
      $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('thumbnail'))
      )
    );
    $registry->addFieldResolver('FeaturedImage', 'medium',
    $builder->compose(
      $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('medium'))
      )
    );
    $registry->addFieldResolver('FeaturedImage', 'large',
    $builder->compose(
      $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('large'))
      )
    );
    $registry->addFieldResolver('Author', 'picture',
    $builder->compose(
      $builder->produce('property_path')
      ->map('type', $builder->fromValue('entity:file'))
      ->map('value', $builder->fromParent())
      ->map('path', $builder->fromValue('user_picture.entity')),
      $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('thumbnail'))
      )
    );
    $registry->addFieldResolver('Article', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Article', 'title',
      $builder->compose(
        $builder->produce('entity_label')
          ->map('entity', $builder->fromParent()),
        $builder->produce('uppercase')
          ->map('string', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Article', 'author',
      $builder->compose(
        $builder->produce('entity_owner')
          ->map('entity', $builder->fromParent()),
        $builder->produce('entity_load')
          ->map('type', $builder->fromValue('user'))
          ->map('id', $builder->fromParent())
      )
    );
    $registry->addFieldResolver('Article', 'featured_image',
      $builder->compose(
        $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:file'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_image.entity'))
      )
    );
    $registry->addFieldResolver('Article', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Article', 'body',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('body.value'))
    );
    $registry->addFieldResolver('Article', 'summary',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('body.summary'))
    );
    $registry->addFieldResolver('Page', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Page', 'title',
      $builder->compose(
        $builder->produce('entity_label')
          ->map('entity', $builder->fromParent()),
        $builder->produce('uppercase')
          ->map('string', $builder->fromParent())
      )
    );

  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addQueryFields(ResolverRegistry $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Query', 'article',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['article']))
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'articles',
      $builder->produce('query_articles')
        ->map('offset', $builder->fromArgument('offset'))
        ->map('limit', $builder->fromArgument('limit'))
    );
    $registry->addFieldResolver('Query', 'page',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['page']))
        ->map('id', $builder->fromArgument('id'))
    );
    $registry->addFieldResolver('Query', 'pages',
      $builder->produce('query_pages')
        ->map('offset', $builder->fromArgument('offset'))
        ->map('limit', $builder->fromArgument('limit'))
    );
  }

  /**
   * @param string $type
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addConnectionFields($type, ResolverRegistry $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver($type, 'total',
      $builder->callback(function (QueryConnection $connection) {
        return $connection->total();
      })
    );

    $registry->addFieldResolver($type, 'items',
      $builder->callback(function (QueryConnection $connection) {
        return $connection->items();
      })
    );
  }
}
