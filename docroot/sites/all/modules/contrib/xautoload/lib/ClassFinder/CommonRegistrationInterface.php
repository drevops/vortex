<?php

namespace Drupal\xautoload\ClassFinder;

/**
 * Class finder interface with additional registration methods.
 */
interface CommonRegistrationInterface {

  //                                                      Composer compatibility
  // ---------------------------------------------------------------------------

  /**
   * @param array $classMap
   *   Class to filename map. E.g. $classMap['Acme\Foo'] = 'lib/Acme/Foo.php'
   */
  function addClassMap(array $classMap);

  /**
   * Add PSR-0 style prefixes. Alias for ->addPsr0().
   *
   * @param string $prefix
   * @param string[]|string $paths
   */
  function add($prefix, $paths);

  /**
   * Add PSR-0 style prefixes. Alias for ->add().
   *
   * @param string $prefix
   * @param string[]|string $paths
   */
  function addPsr0($prefix, $paths);

  /**
   * @param string $prefix
   * @param string[]|string $paths
   */
  function addPsr4($prefix, $paths);

  //                                                      More convenience stuff
  // ---------------------------------------------------------------------------

  /**
   * Add PSR-0 style namespace.
   * This will assume that we are really dealing with a namespace, even if it
   * has no '\\' included.
   *
   * @param string $prefix
   * @param string[]|string $paths
   */
  function addNamespacePsr0($prefix, $paths);

  /**
   * Add PEAR-like prefix.
   * This will assume with no further checks that $prefix contains no namespace
   * separator.
   *
   * @param $prefix
   * @param $paths
   */
  function addPear($prefix, $paths);

}
