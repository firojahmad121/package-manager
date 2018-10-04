<?php

namespace Webkul\UVDesk\PackageManager\Composer;

final class ComposerPackage
{
    private $extension;
    private $consoleText;
    private $movableResources = [];

    public function setExtension($extension = null)
    {
        $this->extension = is_string($extension) ? $extension : null;

        return $this;
    }

    public function writeToConsole($packageText = null)
    {
        if (is_string($output)) {
            $this->consoleOutput = $packageText;
        }

        return $this;
    }

    public function movePackageConfig($destination, $source)
    {
        $this->movableResources[$destination] = $source;

        return $this;
    }
    
    public function moveResources()
    {

    }

    public function autoConfigureExtension()
    {
        
    }

    public function outputPackageInstallationMessage()
    {
        
    }
}