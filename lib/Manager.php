<?php

namespace Webkul\UVDesk\PackageManager;

use Composer\Composer;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use Composer\Script\ScriptEvents;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageListener;

class Manager implements PluginInterface, EventSubscriberInterface
{
    private $io;
    private $composer;
    private $packagesOperation = [];

    public function activate(Composer $composer, IOInterface $io)
    {
        if (!extension_loaded('openssl')) {
            self::$activated = false;
            $io->writeError('<warning>UVDesk dependency resolver has been disabled. You must enable the openssl extension in your "php.ini" file.</warning>');

            return;
        }

        $this->io = $io;
        $this->composer = $composer;
    }

    public function logPackageEvent(PackageEvent $event)
    {
        $this->packagesOperation[] = $event->getOperation();
    }

    private static function getPackageVersion(PackageInterface $package)
    {
        $version = $package->getPrettyVersion();

        if (0 === strpos($version, 'dev-') && isset($package->getExtra()['branch-alias'])) {
            $branchAliases = $package->getExtra()['branch-alias'];

            if (!empty($branchAliases[$version]) || !empty($branchAliases['dev-master'])) {
                return !empty($branchAliases[$version]) ? $branchAliases[$version] : $branchAliases['dev-master'];
            }
        }

        return $version;
    }

    public function loadDependencies(array $packageOperations = [])
    {
        $dependencies = [
            'count' => ['install' => 0, 'update' => 0, 'remove' => 0],
            'listeners' => [],
        ];

        $this->io->writeError("\n<comment>Evaluating dependency configurations (uvdesk packages)</comment>");

        foreach ($packageOperations as $packageOperation) {
            $package = $packageOperation instanceof UpdateOperation ? $packageOperation->getTargetPackage() : $packageOperation->getPackage();
            $extras = $package->getExtra();
            
            if (!empty($extras['uvdesk-handler']) && class_exists($extras['uvdesk-handler'])) {
                try {
                    $packageListener = new $extras['uvdesk-handler']($package, $packageOperation);
                    
                    if ($packageListener instanceof ComposerPackageListener) {
                        $dependencies['listeners'][] = $packageListener;
                        $dependencies['count'][$packageListener->getPackageOperationType()] += 1;
                    }
                } catch (\Exception $e) {
                    $this->io->writeError("\n<error>Failed to evaluate configs for package <error><comment>" . $package->getNames()[0] . "</comment>");
                }
            }
        }

        return $dependencies;
    }

    public function postPackagesInstallEvent(Event $event)
    {
        $this->postPackagesUpdateEvent($event);
    }

    public function postPackagesUpdateEvent(Event $event)
    {
        $packages = $this->loadDependencies($this->packagesOperation);

        if (!empty($packages['listeners'])) {
            $dispatcher = new EventDispatcher();
            $this->io->writeError(sprintf("<info>Configuration operations: %s install, %s updates, %s removals</info>", $packages['count']['install'], $packages['count']['update'], $packages['count']['remove']));

            foreach ($packageCollection as $packageEventHandler) {
                $dispatcher->addListener('uvdesk.composer.packageUpdated', [$packageEventHandler, 'onPackageUpdated']);
            }

            $dispatcher->dispatch('uvdesk.composer.packageUpdated');
        }
    }

    public function postProjectCreationEvent(Event $event)
    {
        $packages = $this->loadDependencies($this->packagesOperation);

        if (!empty($packages['listeners'])) {
            $dispatcher = new EventDispatcher();
            $this->io->writeError(sprintf("<info>Configuration operations: %s install, %s updates, %s removals</info>", $packages['count']['install'], $packages['count']['update'], $packages['count']['remove']));

            foreach ($packageCollection as $packageEventHandler) {
                $dispatcher->addListener('uvdesk.composer.projectCreated', [$packageEventHandler, 'onProjectCreated']);
            }

            $dispatcher->dispatch('uvdesk.composer.projectCreated');
        }
    }
    
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'logPackageEvent',
            PackageEvents::POST_PACKAGE_UPDATE => 'logPackageEvent',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'logPackageEvent',
            ScriptEvents::POST_INSTALL_CMD => 'postPackagesInstallEvent',
            ScriptEvents::POST_UPDATE_CMD => 'postPackagesUpdateEvent',
            ScriptEvents::POST_CREATE_PROJECT_CMD => 'postProjectCreationEvent',
        ];
    }
}
