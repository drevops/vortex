<?php

/**
 * @file
 * Global constants.
 *
 * This file contains only site-wide, not module-related, constants.
 * Module-related constants must stay with their modules.
 */

/**
 * @defgroup constants_entity_types Entity types constants
 * @{
 * Entity types constants must be used in every entity type evaluation
 * expression.
 * Not sure how this did not get into Drupal core.
 */

/**
 * Defines node entity type.
 */
define('ENTITY_TYPE_NODE', 'node');

/**
 * Defines taxonomy term entity type.
 */
define('ENTITY_TYPE_TAXONOMY_TERM', 'taxonomy_term');

/**
 * Defines user entity type.
 */
define('ENTITY_TYPE_USER', 'user');

/**
 * Defines comment entity type.
 */
define('ENTITY_TYPE_COMMENT', 'comment');

/**
 * Defines file entity type.
 */
define('ENTITY_TYPE_FILE', 'file');

/**
 * Defines field_collection_item entity type.
 */
define('ENTITY_TYPE_FIELD_COLLECTION_ITEM', 'field_collection_item');

/**
 * Defines paragraphs_item entity type.
 */
define('ENTITY_TYPE_PARAGRAPHS_ITEM', 'paragraphs_item');

/**
 * @} End of "Entity types constants"
 */

/**
 * @defgroup constants_roles User role constants
 * @{
 * User role constants to be used in every user role evaluation expression.
 */

/**
 * Defines Anonymous user role name.
 */
define('USER_ANONYMOUS_ROLE_NAME', 'anonymous user');

/**
 * Defines Authenticated user role name.
 */
define('USER_AUTHENTICATED_ROLE_NAME', 'authenticated user');

/**
 * @} End of "User role constants"
 */

/**
 * @defgroup constants_filter_formats Filter Formats constants
 * @{
 * Filter formats constants to be used in every filter format evaluation
 * expression.
 */

/**
 * Text filter format.
 */
define('FILTER_FORMAT_TEXT', 'plain_text');

/**
 * Full HTML filter format.
 */
define('FILTER_FORMAT_FULL_HTML', 'full_html');

/**
 * Full HTML filter format.
 */
define('FILTER_FORMAT_FILTERED_HTML', 'filtered_html');

/**
 * @} End of "Filter Formats"
 */

/**
 * @defgroup constants_node_type Node type constants
 * @{
 * Content types constants to be used in every node type evaluation
 * expression.
 */

/**
 * Content type Page.
 */
define('NODE_TYPE_PAGE', 'page');

/**
 * @} End of "Node type"
 */

/**
 * @defgroup menu_names Menu names
 * @{
 * Menu names to be used in every menu-related evaluation expression.
 */

/**
 * Defines Main menu.
 */
define('MENU_MAIN', 'main-menu');

/**
 * @} End of Menu names"
 */

/**
 * @defgroup constants_taxonomy Taxonomy constants
 * @{
 * Taxonomy constants to be used in every taxonomy vocabulary or term evaluation
 * expression.
 */

/**
 * Defines Topics vocabulary name.
 */
define('VOCABULARY_TAGS', 'tags');

/**
 * @} End of "Taxonomy"
 */
