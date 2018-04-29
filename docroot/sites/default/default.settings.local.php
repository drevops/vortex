<?php

/**
 * @file
 * Local settings file.
 *
 * Copy this file to settings.local.php.
 * This file is excluded from the repo and will not be committed.
 */

// Enable error reporting.
error_reporting(E_ALL | E_STRICT);

// Disable caching.
$conf['cache'] = 0;
$conf['preprocess_css'] = FALSE;
$conf['preprocess_js'] = FALSE;

// Enable rebuild of theme registry on each page load.
// $conf['devel_rebuild_theme_registry'] = TRUE;

// Enable theme debugging.
// This will print theme suggestions as HTML comments on the page.
// $conf['theme_debug'] = TRUE;

// Enable Livereload.
// $conf['livereload'] = TRUE;

// Bypass reverting features on local when DB updates are ran.
// This is usually helpful when developing updates locally.
// By default, running DB updates will trigger cache clear and all features
// revert.
// $conf['persistent_update_bypass'] = TRUE;

// Enable context debugging.
// $conf['context_reaction_debug_enable_global'] = TRUE;

// Enable Views debugging.
// $conf['views_ui_show_advanced_column'] = 1;
// $conf['views_ui_show_sql_query'] = 1;
