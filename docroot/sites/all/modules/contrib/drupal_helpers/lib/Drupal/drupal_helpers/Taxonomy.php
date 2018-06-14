<?php

namespace Drupal\drupal_helpers;

if (!module_exists('taxonomy')) {
  throw new Exception('Taxonomy module is not present.');
}

/**
 * Class Taxonomy.
 *
 * @package Drupal\drupal_helpers
 */
class Taxonomy {

  /**
   * Create form element options from terms in provided vocabulary.
   *
   * @param string $machine_name
   *   Vocabulary machine name.
   * @param string $depth_prefix
   *   Depth indentation prefix. Defaults to '-'.
   *
   * @return array
   *   Array of options keyed by term id and suitable for use with FAPI elements
   *   that support '#options' property.
   */
  public static function formElementOptions($machine_name, $depth_prefix = '-') {
    $options = [];

    $vocab = taxonomy_vocabulary_machine_name_load($machine_name);
    $terms = taxonomy_get_tree($vocab->vid);

    foreach ($terms as $term) {
      $options[$term->tid] = str_repeat($depth_prefix, $term->depth) . $term->name;
    }

    return $options;
  }

  /**
   * Find term by name.
   *
   * Retrieve the very first occurrence of the term in the search result set.
   *
   * @param string $name
   *   Term name.
   * @param string $machine_name
   *   Vocabulary machine name. Defaults to NULL.
   *
   * @return object|bool
   *   Term object if found, FALSE otherwise.
   */
  public static function termByName($name, $machine_name = NULL) {
    $term = taxonomy_get_term_by_name($name, $machine_name);
    if (!empty($term)) {
      $term = reset($term);

      return $term;
    }

    return FALSE;
  }

  /**
   * Save terms, specified as simplified term tree.
   *
   * @param string $vocabulary_name
   *   Vocabulary machine name.
   * @param array $tree
   *   Array of tree items, where keys with array values are considered parent
   *   terms.
   * @param bool $verbose
   *   Flag to output term creation progress information. Defaults to TRUE.
   * @param bool|int $parent_tid
   *   Internal parameter used for recursive calls.
   *
   * @return array
   *   Array of saved terms, keyed by term id.
   */
  public static function saveTermTree($vocabulary_name, array $tree, $verbose = TRUE, $parent_tid = FALSE) {
    $vocabulary = taxonomy_vocabulary_machine_name_load($vocabulary_name);
    $terms = [];

    $weight = 0;
    foreach ($tree as $parent => $subtree) {
      $term = (object) [
        'name' => is_array($subtree) ? $parent : $subtree,
        'vid' => $vocabulary->vid,
        'vocabulary_machine_name' => $vocabulary->machine_name,
        'weight' => $weight,
        'parent' => $parent_tid !== FALSE ? $parent_tid : 0,
      ];

      taxonomy_term_save($term);
      if ($verbose) {
        General::messageSet(format_string('Created term "@name" (tid: @tid)', [
          '@name' => $term->name,
          '@tid' => $term->tid,
        ]));
      }
      $terms[$term->tid] = $term;

      if (is_array($subtree)) {
        $terms += self::saveTermTree($vocabulary_name, $subtree, $verbose, $term->tid);
      }

      $weight++;
    }

    return $terms;
  }

  /**
   * Remove all terms for a vocabulary.
   *
   * @param string $vocabulary_name
   *   Vocabulary machine name.
   *
   * @throws \DrupalUpdateException
   *   If after removal vocabulary still has terms.
   */
  public static function removeAllTerms($vocabulary_name) {
    $vocabulary = taxonomy_vocabulary_machine_name_load($vocabulary_name);

    if (!$vocabulary) {
      throw new \DrupalUpdateException(format_string('Vocabulary @name does not exist', [
        '@name' => $vocabulary_name,
      ]));
    }

    foreach (taxonomy_get_tree($vocabulary->vid) as $term) {
      taxonomy_term_delete($term->tid);
    }

    if (count(taxonomy_get_tree($vocabulary->vid)) == 0) {
      General::messageSet(format_string('Removed all terms from vocabulary @name', [
        '@name' => $vocabulary_name,
      ]));
    }
    else {
      throw new \DrupalUpdateException(format_string('Unable to remove all terms from vocabulary @name', [
        '@name' => $vocabulary_name,
      ]));
    }
  }

}
