<?php

/**
 * @file
 * Contains derhasi\Composer\Plugin.
 */

namespace derhasi\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * Class Plugin.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var \derhasi\Composer\PluginWrapper
     */
    protected $wrapper;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->wrapper = new PluginWrapper($composer, $io);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
        ScriptEvents::PRE_PACKAGE_INSTALL => 'prePackage',
        ScriptEvents::POST_PACKAGE_INSTALL => 'postPackage',
        ScriptEvents::PRE_PACKAGE_UPDATE => 'prePackage',
        ScriptEvents::POST_PACKAGE_UPDATE => 'postPackage',
        ScriptEvents::PRE_PACKAGE_UNINSTALL => 'prePackage',
        ScriptEvents::POST_PACKAGE_UNINSTALL => 'postPackage',
        );
    }

    /**
     * Pre Package event behaviour for backing up preserved paths.
     *
     * @param \Composer\Installer\PackageEvent $event
     */
    public function prePackage(PackageEvent $event)
    {

        $this->wrapper->prePackage($event);
    }

    /**
     * Pre Package event behaviour for backing up preserved paths.
     *
     * @param \Composer\Installer\PackageEvent $event
     */
    public function postPackage(PackageEvent $event)
    {
        $this->wrapper->postPackage($event);
    }
}
