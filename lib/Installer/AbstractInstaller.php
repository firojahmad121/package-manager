<?php

namespace Webkul\UVDesk\PackageManager\Installer;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractInstaller
{
    abstract public static function onPackageUpdated(Event $event);
    abstract public static function onProjectCreated(Event $event);
}