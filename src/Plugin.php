<?php

namespace Syntaxx\PhpWasm;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        // Plugin activation logic
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Plugin deactivation logic
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Plugin uninstall logic
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'onPostInstallCmd',
            'post-update-cmd' => 'onPostInstallCmd',
        ];
    }

    public function onPostInstallCmd(Event $event)
    {
        Installer::install($event);
    }
} 