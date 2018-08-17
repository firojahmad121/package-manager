<?php

namespace Webkul\UVDesk\PackageManager\Composer;

use Composer\Package\PackageInterface;
use Symfony\Component\EventDispatcher\Event;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;

abstract class ComposerPackageListener
{
    private $package;
    private $operation;

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

    abstract public static function onProjectCreated(Event $event);
    abstract public static function onPackageUpdated(Event $event);
    abstract public static function onPackageRemoved(Event $event);
}