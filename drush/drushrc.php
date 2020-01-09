<?php

/**
 * @file
 * Drush runtime config (drushrc) file.
 */

// Skip data for some tables during sql dumps and syncs
// These tables will be syncronized just as structure and not the data inside
// them, this makes syncing and dumping much faster
// In case you need these tables, call the 'sql-sync' or 'sql-dump' command
// with '--no-structure-tables-list'.
$command_specific['sql-sync'] = ['structure-tables-list' => 'cache,cache_*,history,sessions,watchdog,feeds_log'];
$command_specific['sql-dump'] = ['structure-tables-list' => 'cache,cache_*,history,sessions,watchdog,feeds_log'];
