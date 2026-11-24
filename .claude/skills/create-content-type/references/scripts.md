# Scripts for the content model

Copy a script to `.artifacts/tmp/create-content-type/<name>.php`, edit the values at the top, and run it with `ahoy drush php:script /app/.artifacts/tmp/create-content-type/<name>.php`. The scripts that create a content type or a vocabulary check before they create, so running them twice changes nothing. Export the result with the `manage-config` skill.

## List the bundles and their fields

`list-model.php` prints every content type, vocabulary and media type with its configurable fields:

```php
<?php

declare(strict_types=1);

use Drupal\field\FieldConfigInterface;

$entity_type_manager = \Drupal::entityTypeManager();
$bundle_info = \Drupal::service('entity_type.bundle.info');
$field_manager = \Drupal::service('entity_field.manager');

foreach (['node', 'taxonomy_term', 'media', 'block_content'] as $entity_type_id) {
  if (!$entity_type_manager->hasDefinition($entity_type_id)) {
    continue;
  }

  foreach ($bundle_info->getBundleInfo($entity_type_id) as $bundle => $info) {
    $fields = array_filter($field_manager->getFieldDefinitions($entity_type_id, $bundle), fn($definition): bool => $definition instanceof FieldConfigInterface);
    $names = array_map(fn(FieldConfigInterface $field): string => sprintf('%s (%s)', $field->getName(), $field->getType()), $fields);

    printf("%s.%s \"%s\": %s\n", $entity_type_id, $bundle, $info['label'], $names === [] ? 'no fields' : implode(', ', $names));
  }
}
```

## Create a content type

`create-type.php`:

```php
<?php

declare(strict_types=1);

use Drupal\node\Entity\NodeType;

$type = 'event';
$name = 'Event';
$description = 'Use events for activities that happen on a date.';

if (NodeType::load($type) !== NULL) {
  print "The {$type} content type already exists.\n";

  return;
}

NodeType::create([
  'type' => $type,
  'name' => $name,
  'description' => $description,
  'new_revision' => TRUE,
  'preview_mode' => 1,
  'display_submitted' => FALSE,
])->save();

print "Created the {$type} content type.\n";
```

The values match the shipped `page` type: new revisions on, preview optional, author and date hidden. Add the fields with `ahoy drush field:create`, which also creates the default form and view displays. Arrange the teaser display in the admin UI.

## Create a vocabulary

`create-vocabulary.php`:

```php
<?php

declare(strict_types=1);

use Drupal\taxonomy\Entity\Vocabulary;

$vid = 'topics';
$name = 'Topics';

if (Vocabulary::load($vid) !== NULL) {
  print "The {$vid} vocabulary already exists.\n";

  return;
}

Vocabulary::create([
  'vid' => $vid,
  'name' => $name,
  'description' => '',
])->save();

print "Created the {$vid} vocabulary.\n";
```

Terms are content, not configuration, so they don't export. Create the terms a site needs in a deploy hook of a custom module, as the `create-module` skill describes.

## Create a test node

`create-test-node.php` creates a node that the checks in the skill can open, and prints its path:

```php
<?php

declare(strict_types=1);

use Drupal\node\Entity\Node;

$type = 'event';

$node = Node::create([
  'type' => $type,
  'title' => '[TEST] ' . $type,
  'status' => 1,
]);

if ($node->hasField('field_event_date')) {
  $node->set('field_event_date', '2026-01-15T09:00:00');
}

$node->save();

print $node->toUrl()->toString() . "\n";
```

Delete the node after the checks, for example from `/admin/content`.
