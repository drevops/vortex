<?php

namespace Drupal\drupal_helpers;

/**
 * Class Menu.
 *
 * @package Drupal\drupal_helpers
 */
class Menu {

  /**
   * Helper to add menu item into specified menu.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $new_item
   *   Array with items keys used in menu_link_save().
   * @param bool $unique
   *   Flag to check such item already exists and do not add the item. Check is
   *   made on both title and path meaning that if both exists - item will be
   *   updated and existing item mlid will be returned.
   *
   * @return int|bool
   *   'mlid' of the created menu item or FALSE.
   *
   * @see menu_link_save()
   */
  public static function addItem($menu_name, array $new_item, $unique = TRUE) {
    if (!isset($new_item['link_title']) || !isset($new_item['link_path'])) {
      return FALSE;
    }

    // If specified, find parent and make sure that it exists.
    if (!empty($new_item['plid'])) {
      if (!self::findItem($menu_name, ['mlid' => $new_item['plid']])) {
        return FALSE;
      }
    }

    $new_item['menu_name'] = $menu_name;
    $new_item['link_path'] = drupal_get_normal_path($new_item['link_path']);

    if ($unique) {
      // Search for item and return mlid if it was found.
      // Prepare a stub item to use for search - we are searching by title and
      // path only.
      $tmp_item = array_intersect_key($new_item, array_flip([
        'link_title',
        'link_path',
      ]));
      $mlid = self::findItem($menu_name, $tmp_item);
      if ($mlid) {
        $mlid = self::updateItem($menu_name, $tmp_item, $new_item);

        return $mlid;
      }
    }

    return menu_link_save($new_item);
  }

  /**
   * Helper function to update existing menu item.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array of menu item fields to search item. Items keys used in
   *   menu_link_save().
   * @param array $updates
   *   Array of menu item fields to be updated. Items keys used in
   *   menu_link_save().
   * @param bool $normalise_path
   *   Flag to normalise new link path. Defaults to TRUE. Useful to set to FALSE
   *   if replacing aliased path with exact path.
   *
   * @return bool
   *   Updated menu link id if update was successful or FALSE otherwise.
   *
   * @see menu_link_save()
   */
  public static function updateItem($menu_name, array $existing_item, array $updates, $normalise_path = TRUE) {
    $mlid = self::findItem($menu_name, $existing_item);
    if (!$mlid) {
      return FALSE;
    }

    $item = menu_link_load($mlid);

    foreach ($updates as $k => $v) {
      // Do not allow to overwrite mlid.
      if ($k == 'mlid') {
        continue;
      }
      if ($k == 'link_path') {
        $v = $normalise_path ? drupal_get_normal_path($v) : $v;
      }
      $item[$k] = $v;
    }

    return menu_link_save($item);
  }

  /**
   * Helper function to delete existing menu item.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array of menu item fields to search item. Items keys used in
   *   menu_link_save().
   *
   * @return bool
   *   Boolean TRUE if deletion was successful or FALSE otherwise.
   *
   * @see menu_link_save()
   */
  public static function deleteItem($menu_name, array $existing_item) {
    $mlid = self::findItem($menu_name, $existing_item);
    if (!$mlid) {
      return FALSE;
    }
    menu_link_delete($mlid);

    return (bool) self::findItem($menu_name, $existing_item) ? FALSE : TRUE;
  }

  /**
   * Helper function to find existing menu item.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array that is used to lookup existing menu item. Only first match will be
   *   used. All specified items must exists to return valid result:
   *   - link_title: String title to lookup. If plid is not specified, lookup
   *   is performed among all items.
   *   - link_path: Path of the menu item to lookup. Aliased path will be looked
   *   up and replaced with a system path. If plid is not specified - lookup is
   *   performed among all items.
   *   - mlid: Menu item link id.
   *   - plid: Menu item parent link id. If this is present lookup is performed
   *     within items with specified plid.
   *
   * @return int|bool
   *   Integer mlid if item was found ot FALSE otherwise.
   */
  public static function findItem($menu_name, array $existing_item) {
    // Init query.
    $query = db_select('menu_links', 'ml')
      ->fields('ml', ['mlid'])
      ->condition('menu_name', $menu_name);

    // Traverse through fields and add to conditions.
    foreach ($existing_item as $field_name => $field_value) {
      $field_value = $field_name == 'link_path' ? drupal_get_normal_path($field_value) : $field_value;
      $query->condition($field_name, $field_value);
    }
    // Execute query and fetch an item.
    $item = $query->execute()->fetchAssoc();

    return (isset($item['mlid']) && $item['mlid']) ? $item['mlid'] : FALSE;
  }

  /**
   * Find item children.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array of menu item fields to search item. Items keys used in
   *   menu_link_save().
   * @param int $depth
   *   Optional children tree depth. Defaults to 1, meaning that only immediate
   *   children will be returned.
   *
   * @return array
   *   Array of children menu items.
   */
  public static function findItemChildren($menu_name, array $existing_item, $depth = 1) {
    $cid = __FUNCTION__ . '_' . $menu_name . '_' . hash('sha256', serialize($existing_item));
    $children = &drupal_static($cid);

    if (is_null($children)) {
      $mlid = isset($existing_item['mlid']) ? $existing_item['mlid'] : self::findItem($menu_name, $existing_item);
      if ($mlid) {
        $current_item = menu_link_load($mlid);
        $parameters = [
          'active_trail' => [$current_item['mlid']],
          'only_active_trail' => FALSE,
          'min_depth' => $current_item['depth'] + 1,
          'conditions' => ['plid' => $current_item['mlid']],
        ];

        if (!is_null($depth)) {
          $parameters['max_depth'] = $current_item['depth'] + $depth;
        }

        $children = menu_build_tree($menu_name, $parameters);
      }
      else {
        // Prevent cache invalidation if invalid mlid is provided.
        $children = [];
      }
    }

    return $children;
  }

  /**
   * Find item siblings.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array of menu item fields to search item. Items keys used in
   *   menu_link_save().
   * @param bool $include_current
   *   Optional flag to include current item into set of returned siblings.
   *   Defaults to FALSE.
   *
   * @return array
   *   Array of sibling menu items.
   */
  public static function findItemSiblings($menu_name, array $existing_item, $include_current = FALSE) {
    $cid = __FUNCTION__ . '_' . $menu_name . '_' . hash('sha256', serialize($existing_item)) . '_' . intval($include_current);
    $siblings = &drupal_static($cid, []);

    if (empty($siblings)) {
      $current_mlid = isset($existing_item['mlid']) ? $existing_item['mlid'] : self::findItem($menu_name, $existing_item);
      if ($current_mlid) {
        $current_item = menu_link_load($current_mlid);

        $plid = isset($current_item['plid']) ? $current_item['plid'] : 0;
        $siblings = self::findItemChildren($menu_name, ['mlid' => $plid]);

        if (!$include_current) {
          foreach ($siblings as $k => $leaf) {
            if (isset($leaf['link']['mlid']) && $leaf['link']['mlid'] == $current_mlid) {
              unset($siblings[$k]);
              break;
            }
          }
        }
      }
    }

    return $siblings;
  }

  /**
   * Import links from the provided tree.
   *
   * @code
   * $tree = [
   *   'Item1' => [
   *     'link_path' => 'path-to-item1',
   *     'children' => [
   *       'Subitem 1' => 'path-to-subitem1',
   *       'Subitem 2' => 'path-to-subitem2',
   *     ],
   *   'Item2' => 'path-to-item2',
   * ];
   * Menu::import('main-menu', $tree);
   * @endcode
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $tree
   *   Array of links with keys as titles and values as paths or full link
   *   item array definitions. 'children' key is used to specify children menu
   *   levels.
   * @param int $plid
   *   Optional parent mlid. Defaults to 0.
   *
   * @return array
   *   Array of created mlids.
   */
  public static function import($menu_name, array $tree, $plid = 0) {
    $created_mlids = [];
    $weight = 0;
    foreach ($tree as $title => $leaf) {
      $leaf = is_array($leaf) ? $leaf : [
        'link_path' => $leaf,
      ];
      $leaf += [
        'link_title' => $title,
        'plid' => $plid,
        'weight' => $weight,
      ];

      $children = isset($leaf['children']) ? $leaf['children'] : [];
      unset($leaf['children']);

      if ($children) {
        $leaf += ['expanded' => TRUE];
      }

      $mlid = self::addItem($menu_name, $leaf, FALSE);
      if (!$mlid) {
        continue;
      }
      $created_mlids[] = $mlid;
      $weight++;
      if ($children) {
        $created_mlids = array_merge($created_mlids, self::import($menu_name, $children, $mlid));
      }
    }

    return $created_mlids;
  }

}
