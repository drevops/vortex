<?php

declare(strict_types=1);

namespace Drupal\the_force_demo\Plugin\GeneratedContent\Node;

use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;

/**
 * Generate article nodes with tags.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'the_force_demo_node_article',
  entity_type: 'node',
  bundle: 'article',
  weight: 20,
)]
class Article extends GeneratedContentPluginBase {

  protected const TOTAL = 20;

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    $storage = $this->entityTypeManager->getStorage('node');

    for ($i = 1; $i <= self::TOTAL; $i++) {
      $node = $storage->create([
        'type' => 'article',
        'title' => sprintf('Demo article %s %s', $i, $this->helper::staticName()),
        'status' => 1,
      ]);

      $node->set('body', [
        'value' => $this->helper::staticRichText(3),
        'format' => 'full_html',
      ]);

      $tags = $this->helper::randomTerms('tags', random_int(1, 3));
      if ($tags !== []) {
        $node->set('field_tags', $tags);
      }

      $node->save();

      $this->helper::log('Created "%s" node "%s" [ID: %s]', $node->bundle(), $node->toLink()->toString(), $node->id());

      $entities[] = $node;
    }

    return $entities;
  }

}
