<?php

declare(strict_types=1);

namespace Drupal\ys_demo\Plugin\GeneratedContent\Taxonomy;

use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;

/**
 * Generate tags taxonomy terms.
 */
#[GeneratedContent(
  id: 'ys_demo_taxonomy_term_tags',
  entity_type: 'taxonomy_term',
  bundle: 'tags',
  weight: 10,
)]
class Tags extends GeneratedContentPluginBase {

  /**
   * Tag names to generate.
   */
  protected const TAGS = [
    'Technology',
    'Science',
    'Health',
    'Business',
    'Environment',
  ];

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    foreach (self::TAGS as $name) {
      $term = $storage->create([
        'vid' => 'tags',
        'name' => $name,
      ]);
      $term->save();

      $this->helper::log('Created "%s" term "%s" [ID: %s]', $term->bundle(), $term->toLink()->toString(), $term->id());

      $entities[] = $term;
    }

    return $entities;
  }

}
