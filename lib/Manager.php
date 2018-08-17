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
use Webkul\UVDesk\PackageManager\Composer\ComposerEvent;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;

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
        $packagesCollection = [];

        foreach ($packageOperations as $packageOperation) {
            $package = $packageOperation instanceof UpdateOperation ? $packageOperation->getTargetPackage() : $packageOperation->getPackage();
            $extras = $package->getExtra();
            
            if (!empty($extras['package-handle']) && class_exists($extras['package-handle'])) {
                try {
                    $packageEventHandler = new $extras['package-handle']($package, $packageOperation);
                    
                    if ($packageEventHandler instanceof ComposerEvent) {
                        $packagesCollection[] = $packageEventHandler;
                        // $packagesCollection[] = [
                        //     'package' => $package,
                        //     'installer' => $installer,
                        //     'operation' => $packageOperation,
                        //     // 'operationType' => $packageOperation instanceof UpdateOperation ? 'update' : ($packageOperation instanceof UninstallOperation ? 'remove' : 'install'),
                        // ];
                    }
                } catch (\Exception $e) {
                    // Skip
                }
            }
        }

        return $packagesCollection;
    }

    public function postPackagesInstallEvent(Event $event)
    {
        $this->postPackagesUpdateEvent($event);
    }

    public function postPackagesUpdateEvent(Event $event)
    {
        $packageCollection = $this->loadDependencies($this->packagesOperation);
        $count = count($packageCollection);

        if ($count) {
            $this->io->writeError("\n<info>UVDesk operations: Updating $count configurations</info>");
            $dispatcher = new EventDispatcher();

            foreach ($packageCollection as $package) {
                $dispatcher->addListener('composer.packageUpdated', [$package['installer'], 'onPackageUpdated']);
                $this->io->writeError(sprintf('updating package %s.', $package['name']));
            }

            $dispatcher->dispatch('composer.projectCreated');
        }
    }

    public function postProjectCreationEvent(Event $event)
    {
        // $composerHandlers = self::getPackagesComposerHandler(require getcwd() . "/config/bundles.php");

        // if (!empty($composerHandlers)) {
        //     $dispatcher = new EventDispatcher();
            
        //     foreach ($composerHandlers as $handler) {
        //         if (method_exists($handler, 'onProjectCreated')) {
        //             $dispatcher->addListener('composer.projectCreated', [new $handler(), 'onProjectCreated']);
        //         }
        //     }

        //     $dispatcher->dispatch('composer.projectCreated');
        // }
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
