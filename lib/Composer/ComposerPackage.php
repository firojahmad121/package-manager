<?php

namespace Webkul\UVDesk\PackageManager\Composer;

use Symfony\Component\Yaml\Yaml;
use Webkul\UVDesk\PackageManager\Extensions;
use Symfony\Component\Console\Output\ConsoleOutput;

final class ComposerPackage
{
    private $extension;
    private $consoleText;
    private $movableResources = [];
    private $combineResources = [];

    public function __construct(Extensions\ExtensionInterface $extension = null)
    {
        $this->extension = $extension;
    }

    public function writeToConsole($packageText = null)
    {
        $this->consoleText = !empty($packageText) && is_string($packageText) ? $packageText : null;

        return $this;
    }

    public function movePackageConfig($destination, $source)
    {
        $this->movableResources[$destination] = $source;

        return $this;
    }

    public function combineProjectConfig($destination, $source)
    {
        $this->combineResources[$destination] = $source;

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

        // Perform security updates
        if (!empty($this->combineResources)) {
            foreach ($this->combineResources as $sourcePath => $destinationPath) {
                if (file_exists("$installationPath/$destinationPath")) {
                    $config = file_exists("$projectDirectory/$sourcePath") ? Yaml::parseFile("$projectDirectory/$sourcePath") : [];
                    $extensionConfig = Yaml::parseFile("$installationPath/$destinationPath");

                    $config = array_merge_recursive($config, $extensionConfig);

                    file_put_contents("$projectDirectory/$sourcePath", Yaml::dump($config, 6));
                }
            }
        }
        // if (!empty($this->securityUpdateConfig) && file_exists("$installationPath/$this->securityUpdateConfig")) {
        //     $securityConfig = Yaml::parseFile("$projectDirectory/config/packages/security.yaml");
        //     $extensionConfig = Yaml::parseFile("$installationPath/Templates/security-configs.yaml");

        //     if (!empty($extensionConfig['security'])) {
        //         foreach ($extensionConfig['security'] as $type => $configuration) {
        //             switch ($type) {
        //                 case 'encoders':
        //                 case 'providers':
        //                 case 'access_control':
        //                 case 'role_hierarchy':
        //                     $config = !empty($securityConfig['security'][$type]) ? $securityConfig['security'][$type] : [];
        //                     $config = array_merge_recursive($config, $configuration);

        //                     $securityConfig['security'][$type] = $config;
        //                     break;
        //                 case 'firewalls':
        //                     foreach ($configuration as $firewall => $firewallConfig) {
        //                         if (empty($securityConfig['security']['firewalls'][$firewall])) {
        //                             $securityConfig['security']['firewalls'][$firewall] = $firewallConfig;
        //                         }
        //                     }
        //                     break;
        //                 default:
        //                     break;
        //             }
        //         }
        //     }
            
        //     file_put_contents("$projectDirectory/config/packages/security.yaml", Yaml::dump($securityConfig, 5));
        // }

        // Register package as an extension
        if (!empty($this->extension)) {
            switch (true) {
                case $this->extension instanceof Extensions\HelpdeskExtension:
                    $extensionClassPath = get_class($this->extension);
                    $pathRegisteredExtensions = "$projectDirectory/config/extensions.php";

                    $registeredExtensions = file_exists($pathRegisteredExtensions) ? require $pathRegisteredExtensions : [];

                    if (!in_array($extensionClassPath, $registeredExtensions)) {
                        array_push($registeredExtensions, $extensionClassPath);
                    }

                    $registeredExtensions = array_map(function($classPath) {
                        return "\t$classPath::class,\n";
                    }, $registeredExtensions);

                    file_put_contents($pathRegisteredExtensions, str_replace("{REGISTERED_EXTENSIONS}", implode("", $registeredExtensions), Extensions\HelpdeskExtension::CONFIG_TEMPLATE));
                    break;
                default:
                    break;
            }
        }

        return $this;
    }

    public function outputPackageInstallationMessage()
    {
        $console = new ConsoleOutput();
        $console->writeln($this->consoleText);
    }
}