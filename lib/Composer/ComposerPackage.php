<?php

namespace Webkul\UVDesk\PackageManager\Composer;

final class ComposerPackage
{
    private $extension;
    private $consoleText;
    private $movableResources = [];

    public function setExtension($extension = null)
    {
        $this->extension = !empty($extension) && is_string($extension) ? $extension : null;

        return $this;
    }

    public function writeToConsole($packageText = null)
    {
        $this->consoleOutput = !empty($packageText) && is_string($packageText) ? $packageText : null;

        return $this;
    }

    public function movePackageConfig($destination, $source)
    {
        $this->movableResources[$destination] = $source;

        return $this;
    }

    public function moveResources($installationPath)
    {
        $projectDirectory = getcwd();

        foreach ($this->movableResources as $destination => $source) {
            $resourceSourcePath = "$projectDirectory/$source";
            $resourceDestinationPath = "$projectDirectory/$destination";

            if (file_exists($resourceSourcePath) && !file_exists($resourceDestinationPath)) {
                array_pop($destinationDirectory = explode('/', $resourceDestinationPath));
                $destinationDirectory = implode('/', $destinationDirectory);

                if (!is_dir($destinationDirectory)) {
                    mkdir($destinationDirectory);
                }

                file_put_contents($resourceDestinationPath, file_get_contents($resourceSourcePath));
            }
        }
    }

    public function autoConfigureExtension()
    {
        
    }

    public function outputPackageInstallationMessage()
    {
        
    }
}