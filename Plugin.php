<?php

namespace Webkul\UVDesk\Init;

use Composer\Composer;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use Composer\Script\ScriptEvents;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\VarDumper\VarDumper;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private $io;
    private $composer;
    private $packagesOperation = [];
    private static $activated = true;
    private static $evalPackages = [
        'uvdesk/community',
        'uvdesk/community-framework-bundle',
        'uvdesk/support-bundle',
    ];

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

    public function postPackagesInstallEvent(Event $event)
    {
        $this->postPackagesUpdateEvent($event);
    }

    public function postPackagesUpdateEvent(Event $event)
    {
        $packageCollection = $this->loadDependencies($this->packagesOperation);
        $count = count($packageCollection);

        if ($count) {
            $this->io->writeError("\n<info>Community operations: Updating $count configurations</info>");

            foreach ($packageCollection as $package) {
               $this->io->writeError(sprintf('%s package %s.', $package['type'], $package['name'])); 
            }
        }
    }

    public function postProjectCreationEvent(Event $event)
    {
        print("\nProject Created\n");

        $registeredBundles = array_keys(require getcwd() . "/config/bundles.php");
        VarDumper::dump($registeredBundles);

        foreach ($registeredBundles as $bundleName) {
            $composerListenerClassPath = "\\$bundleName\\EventListener\\ComposerEventListener";

            if (class_exists($composerListenerClassPath)) {
                VarDumper::dump($bundleName . ' => Yes');
            } else {
                VarDumper::dump($bundleName . ' => No');
            }
        }
        
        // $dispatcher = new EventDispatcher();

        // $contents = require $this->getProjectDir() . '/config/bundles.php';
        // foreach ($contents as $class => $envs) {
        //     if (isset($envs['all']) || isset($envs[$this->environment])) {
        //         yield new $class();
        //     }
        // }

        // Webkul\UVDesk\CommunityFrameworkBundle\EventListener\ComposerEventListener:
        // tags:
        //     - { name: composer.event_listener, event: composer.projectCreated }

        // $dispatcher->dispatch('composer.projectCreated');
        die;
    }

    public function loadDependencies(array $packageOperations = [])
    {
        $packagesCollection = [];
        return $packagesCollection;

        foreach ($packageOperations as $packageOperation) {
            $package = $packageOperation instanceof UpdateOperation ? $packageOperation->getTargetPackage() : $packageOperation->getPackage();
            $eventType = $packageOperation instanceof UpdateOperation ? 'update' : ($packageOperation instanceof UninstallOperation ? 'remove' : 'install');

            if (in_array($package->getNames()[0], self::$evalPackages)) {
                $packagesCollection[] = [
                    'name' => $package->getNames()[0],
                    'package' => $package,
                    'operation' => $packageOperation,
                    'type' => $eventType,
                ];
            }
        }

        return $packagesCollection;
    }
    
    public static function getSubscribedEvents()
    {
        if (!self::$activated) {
            return [];
        }

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
