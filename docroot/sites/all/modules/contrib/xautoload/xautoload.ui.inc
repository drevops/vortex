<?php

/**
 * Implements hook_form_FORM_ID_alter()
 * with FORM_ID = "system_performance_settings"
 */
function xautoload_form_system_performance_settings_alter(&$form, $form_state) {

  $form['xautoload'] = array(
    '#type' => 'fieldset',
    '#title' => t('X Autoload'),
  );

  $cache_status = array(
    'apc' => (extension_loaded('apc') && function_exists('apc_store')),
    'wincache' => (extension_loaded('WinCache') && function_exists('wincache_ucache_get')),
    'xcache' => (extension_loaded('Xcache') && function_exists('xcache_get')),
  );
  $cache_names = array(
    'apc' => 'APC',
    'wincache' => 'WinCache',
    'xcache' => 'XCache',
  );
  $options = array();
  foreach ($cache_names as $key => $title) {
    $options[$key] = t('@cache_name (@status)', array(
      '@cache_name' => $title,
      '@status' => $cache_status[$key] ? t('Running and available') : t('Not currently available'),
    ));
  }

  $form['xautoload']['xautoload_cache_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Cache mode'),
    '#default_value' => variable_get('xautoload_cache_types', array()),
    '#options' => $options,
  );

  $form['xautoload']['xautoload_cache_lazy'] = array(
    '#type' => 'checkbox',
    '#title' => t('Postpone registration of module namespaces until the first cache miss (recommended).'),
    '#default_value' => variable_get('xautoload_cache_lazy', FALSE),
  );
}