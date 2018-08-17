<?php

namespace Webkul\UVDesk\PackageManager\Composer;

use Composer\Package\PackageInterface;
use Symfony\Component\EventDispatcher\Event;
use Composer\DependencyResolver\Operation\OperationInterface;

abstract class ComposerEvent
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

    public final function getPackageOperation()
    {
        return $this->operation;
    }

    abstract public static function onProjectCreated(Event $event);
    abstract public static function onPackageUpdated(Event $event);
    abstract public static function onPackageRemoved(Event $event);
}