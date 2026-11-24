# Ranking and custom processors

Tune the order of search results with the settings below, from the cheapest change to the most work. Change 1 thing at a time, reindex, and compare the scores before and after with the PHP query in section 7 of [SKILL.md](../SKILL.md).

## Measure first

1. Pick 3 to 5 keywords with a known best result.
2. Record the top 10 results and their scores for each keyword.
3. Make 1 change, reindex with `ahoy drush search-api:reset-tracker <index_id>` and `ahoy drush search-api:index <index_id>`, and record the results again.
4. Keep the change only when the known best results moved up and nothing unrelated moved into the top 3.

## Field boost

Every fulltext field in `field_settings` takes a `boost`. Raise it on the fields that describe an item best, such as the title, and keep the body at the default. Moderate values work best: a title boost of 3 to 5 against a body boost of 1 usually lifts title matches without hiding strong body matches.

```yaml
field_settings:
  title:
    label: Title
    datasource_id: 'entity:node'
    property_path: title
    type: 'solr_text_custom:ngram'
    boost: 5.0
```

## Type-specific boosting

The `type_boost` processor, labeled **Type-specific boosting** in the UI, boosts whole datasources or bundles. Use it when 1 content type should outrank another for the same match, such as guides over news items.

## Partial-word matching

The `solr_text_custom:*` field types match parts of words. The ngram type finds `exam` in `example`, and the edge type matches word beginnings. They make the index larger and can rank weak partial matches high. Index the same property twice, once as plain fulltext and once as an ngram type, with a higher boost on the plain field, when exact matches must win.

## Custom boost processor

Write a processor when the boost depends on data that no setting reads, such as a flag on the entity. Place it in a custom module at `src/Plugin/search_api/processor/`, and run it at the `preprocess_index` stage so the cost is paid once per item instead of once per query:

```php
<?php

declare(strict_types=1);

namespace Drupal\MODULE\Plugin\search_api\processor;

use Drupal\node\NodeInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Ranks sticky nodes above other matches.
 *
 * @SearchApiProcessor(
 *   id = "MODULE_sticky_boost",
 *   label = @Translation("Sticky boost"),
 *   description = @Translation("Ranks nodes marked as sticky above other matches."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   },
 * )
 */
class StickyBoost extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items): void {
    foreach ($items as $item) {
      $entity = $item->getOriginalObject()->getValue();

      if (!$entity instanceof NodeInterface || !$entity->isSticky()) {
        continue;
      }

      $item->setBoost($item->getBoost() * 1.5);
    }
  }

}
```

- Multiply the existing boost instead of replacing it, so the processor combines with other boosts.
- Start with a factor between 1.2 and 2, and raise it only when the measurement shows no effect.
- After adding the file, run `ahoy drush cr`, enable the processor on the index, export with `ahoy drush cex`, and reindex.
- Give 1 field to 1 boosting mechanism. 2 processors that boost on the same field make the result hard to reason about.

## When results look random

- Check that the index uses the fulltext field types you expect with `ahoy drush config:get search_api.index.<index_id> field_settings`.
- Check that the view searches the intended fields in its fulltext filter settings.
- Check that a sort in the view, such as by date, isn't overriding relevance.
