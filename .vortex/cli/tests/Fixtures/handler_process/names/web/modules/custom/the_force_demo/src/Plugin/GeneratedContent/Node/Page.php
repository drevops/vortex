<?php

declare(strict_types=1);

namespace Drupal\the_force_demo\Plugin\GeneratedContent\Node;

use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;

/**
 * Generate page nodes.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'the_force_demo_node_page',
  entity_type: 'node',
  bundle: 'page',
  weight: 20,
)]
class Page extends GeneratedContentPluginBase {

  protected const TOTAL = 20;

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    $storage = $this->entityTypeManager->getStorage('node');

    for ($i = 1; $i <= self::TOTAL; $i++) {
      $node = $storage->create([
        'type' => 'page',
        'title' => sprintf('Demo page %s %s', $i, $this->helper::staticName()),
        'status' => 1,
      ]);

      $node->set('body', [
        'value' => $this->helper::staticRichText(3),
        'format' => 'full_html',
      ]);

      // When Content Moderation is attached to the bundle, 'status' alone
      // leaves the node as an unpublished draft, so the state must be set
      // explicitly for the node to appear in published listings.
      if ($node->hasField('moderation_state')) {
        $node->set('moderation_state', 'published');
      }

      $node->save();

      $this->helper::log('Created "%s" node "%s" [ID: %s]', $node->bundle(), $node->toLink()->toString(), $node->id());

      $entities[] = $node;
    }

    return $entities;
  }

}
