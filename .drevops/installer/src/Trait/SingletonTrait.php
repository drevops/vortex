<?php

namespace DrevOps\Installer\Trait;

/**
 * Singleton trait.
 *
 * This class defines the basic blueprint for Singleton classes.
 * Classes extending this abstract class will inherit Singleton behavior.
 *
 * @phpstan-consistent-constructor
 * @package Merlin\Reporting\Utils
 */
trait SingletonTrait {

  /**
   * Array to hold the instances for all Singleton extended classes.
   *
   * @var static
   */
  protected static $instance;

  /**
   * Flag to check if constructor is called internally.
   *
   * @var bool
   */
  protected static $calledInternally = false;

  public function __construct() {
    if (!self::$calledInternally) {
      throw new \Exception('Cannot instantiate Singleton class directly. Use ::getInstance() instead.');
    }
  }

  /**
   * Returns the unique instance of the class.
   */
  final public static function getInstance(): static {
    if (!static::$instance) {
      // Set the flag to allow internal instantiation
      self::$calledInternally = true;
      // new self() will refer to the class that uses the trait
      static::$instance = new static();
      // Reset the flag
      self::$calledInternally = false;
    }

    return static::$instance;
  }

  final public function __clone() {
    throw new \Exception('Cloning of Singleton is disallowed.');
  }

  final public function __wakeup() {
    throw new \Exception('Unserializing instances of Singleton classes is disallowed.');
  }

}
