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
    private $consoleOutput;

    abstract public function onProjectCreated(Event $event);
    abstract public function onPackageUpdated(Event $event);
    abstract public function onPackageRemoved(Event $event);

    public final function __construct(PackageInterface $package, OperationInterface $operation)
    {
        $this->package = $package;
        $this->operation = $operation;
        $this->consoleOutput = new ConsoleOutput();
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
        $this->onProjectCreated($event);
    }

    public final function handleComposerPackageUpdate(Event $event)
    {
        $this->consoleOutput->write(sprintf("  - Configuring <info>%s</info>", $this->getPackageName()));

        $this->onPackageUpdated($event);
    }
}