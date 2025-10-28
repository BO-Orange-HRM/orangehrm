<?php

use Composer\Autoload\ClassLoader;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\PluginConfigurationInterface;

class PayrollPluginConfiguration implements PluginConfigurationInterface
{
    public function initialize(Request $request): void
    {
        $loader = new ClassLoader();
        $loader->addPsr4('OrangeHRM\\Payroll\\', [realpath(__DIR__ . "/..")]);
        $loader->register();
    }
}