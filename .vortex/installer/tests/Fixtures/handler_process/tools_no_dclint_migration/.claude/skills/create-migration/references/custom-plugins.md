# Custom source and process plugins

Write a custom plugin only when the shipped ones can't do the job: a source query with joins or conditions the `table` source can't express, or a value transform that no chain of process plugins produces. Both plugin types are discovered from PHP attributes in the module's `src/Plugin/migrate/` directory. After adding or renaming a plugin class, run `ahoy drush cr`.

## Source plugin

Extend `SqlBase` to read the `migrate` connection. Put the class in `web/modules/custom/ys_migrate/src/Plugin/migrate/source/`:

```php
<?php

declare(strict_types=1);

namespace Drupal\ys_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Reads published articles with their keywords from the legacy database.
 */
#[MigrateSource(id: 'ys_migrate_articles')]
class Articles extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('articles', 'a')
      ->fields('a', ['article_id', 'headline', 'body_html', 'category_id', 'published_on'])
      ->condition('a.deleted', 0)
      ->orderBy('a.article_id');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'article_id' => $this->t('Article ID'),
      'headline' => $this->t('Headline'),
      'body_html' => $this->t('Body HTML'),
      'category_id' => $this->t('Category ID'),
      'published_on' => $this->t('Publication date'),
      'keywords' => $this->t('Keyword names'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return ['article_id' => ['type' => 'integer', 'alias' => 'a']];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    $keywords = $this->select('article_keywords', 'k')
      ->fields('k', ['name'])
      ->condition('k.article_id', $row->getSourceProperty('article_id'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('keywords', $keywords);

    return parent::prepareRow($row);
  }

}
```

Then reference it from the definition:

```yaml
source:
  plugin: ys_migrate_articles
  key: migrate
```

- `getIds()` names the columns that identify a source row. The migration's map table keys on them, so changing them later orphans every imported row.
- `fields()` documents the source properties, including those `prepareRow()` adds. `ahoy drush migrate:fields-source <id>` prints this list.
- `prepareRow()` runs once per row. Return `FALSE` to skip the row, and call the parent so the source's hooks and events still run.
- The `MigrateSource` attribute accepts `id`, `requirements_met`, `minimum_version` and `deriver`. It has no `source_module` argument.
- A query per row in `prepareRow()` is fine for thousands of rows. For larger sets, join in `query()` instead.

## Process plugin

Extend `ProcessPluginBase`. Put the class in `web/modules/custom/ys_migrate/src/Plugin/migrate/process/`:

```php
<?php

declare(strict_types=1);

namespace Drupal\ys_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Estimates the reading time of HTML text in minutes.
 *
 * Configuration keys:
 * - words_per_minute: (optional) The reading speed. Defaults to 200.
 */
#[MigrateProcess(id: 'ys_migrate_reading_time')]
class ReadingTime extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): ?int {
    $words = str_word_count(strip_tags((string) $value));

    if ($words === 0) {
      $this->stopPipeline();

      return NULL;
    }

    return (int) ceil($words / ($this->configuration['words_per_minute'] ?? 200));
  }

}
```

Use it like any other plugin:

```yaml
field_reading_time:
  plugin: ys_migrate_reading_time
  source: body_html
  words_per_minute: 220
```

- `transform()` receives 1 value. When the value is a list, the pipeline calls the plugin once per item, unless the attribute sets `handle_multiples: TRUE`, in which case the plugin receives the whole list.
- `$this->stopPipeline()` ends the pipeline for this property and leaves the remaining plugins unrun.
- To drop the whole row, throw `Drupal\migrate\MigrateSkipRowException` with a message. The message appears in `ahoy drush migrate:messages <id>`.
- Throw `Drupal\migrate\MigrateException` for data that should fail the row loudly instead of being skipped.
