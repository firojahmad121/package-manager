<?php

namespace Webkul\UVDesk\PackageManager\Composer;

use Composer\Package\PackageInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Console\Output\ConsoleOutput;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;

abstract class ComposerPackageListener
{
    private $package;
    private $operation;

    abstract public function loadPackageConfiguration();

    public final function __construct(PackageInterface $package, OperationInterface $operation)
    {
        $this->package = $package;
        $this->operation = $operation;
    }

    public final function getPackage()
    {
        return $this->package;
    }

    public final function getPackageName()
    {
        return $this->package->getNames()[0];
    }
    
    public final function getPackageOperation()
    {
        return $this->operation;
    }

    public final function getPackageOperationType()
    {
        return $this->operation instanceof UpdateOperation ? 'update' : ($this->operation instanceof UninstallOperation ? 'remove' : 'install');
    }

    public final function handleComposerProjectCreate(Event $event)
    {
        $packageConfig = $this->loadPackageConfiguration();

        $this->onProjectCreated($event);
    }

    public final function handleComposerPackageUpdate(Event $event)
    {
        $packageConfig = $this->loadPackageConfiguration();
        dump($this->getPackage());
        die;

        // $packageConfig->moveResources();
        // $packageConfig->autoConfigureExtension();
        // $packageConfig->outputPackageInstallationMessage();

        $consoleOutput = new ConsoleOutput();
        $consoleOutput->write(sprintf("  - Configuring <info>%s</info>\n", $this->getPackageName()));

        $this->onPackageUpdated($event);
    }
}