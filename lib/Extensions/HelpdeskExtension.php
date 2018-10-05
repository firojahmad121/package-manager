<?php

namespace Webkul\UVDesk\PackageManager\Extensions;

abstract class HelpdeskExtension implements ExtensionInterface
{
    abstract public function loadDashboardItems();
    abstract public function loadNavigationItems();
}
