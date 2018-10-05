<?php

namespace Webkul\UVDesk\PackageManager\Composer;

use Webkul\UVDesk\PackageManager\Extensions;

final class ComposerPackage
{
    private $extension;
    private $consoleText;
    private $movableResources = [];

    public function __construct(Extensions\ExtensionInterface $extension = null)
    {
        $this->extension = $extension;
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

    public function autoConfigureExtension($installationPath)
    {
        $projectDirectory = getcwd();

        // Dump package resources
        foreach ($this->movableResources as $destination => $source) {
            $resourceSourcePath = "$installationPath/$source";
            $resourceDestinationPath = "$projectDirectory/$destination";

            if (file_exists($resourceSourcePath) && !file_exists($resourceDestinationPath)) {
                // Create directory if it doesn't exist
                $destinationDirectory = substr($resourceDestinationPath, 0, strrpos($resourceDestinationPath, '/'));
                
                if (!is_dir($destinationDirectory)) {
                    mkdir($destinationDirectory);
                }

                // Move the contents of the source file to destination file
                file_put_contents($resourceDestinationPath, file_get_contents($resourceSourcePath));
            }
        }

        // Register package as an extension
        if (!empty($this->extension)) {
            switch (true) {
                case $this->extension instanceof Extensions\HelpdeskExtension:
                    $pathToExtensions = "$projectDirectory/config/extensions.php";

                    if (!file_exists($pathRegisteredExtensions)) {
                        file_put_contents($resourceDestinationPath, Extensions\HelpdeskExtension::CONFIG_TEMPLATE);
                    }

                    $registeredExtensions = require $pathToExtensions;
                    var_dump($registeredExtensions);
                    
                    break;
                default:
                    break;
            }
        }
    }

    public function outputPackageInstallationMessage()
    {
        
    }
}