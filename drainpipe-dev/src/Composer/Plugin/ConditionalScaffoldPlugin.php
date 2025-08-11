<?php

namespace Lullabot\DrainpipeDev\Composer\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;

class ConditionalScaffoldPlugin implements PluginInterface, EventSubscriberInterface {

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    public function activate(Composer $composer, IOInterface $io) {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io) {
    }

    public function uninstall(Composer $composer, IOInterface $io) {
    }

    public static function getSubscribedEvents() {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'onPreAutoloadDump',
        ];
    }

    public function onPreAutoloadDump(Event $event) {
        $this->conditionallyConfigureScaffolding();
    }

    protected function conditionallyConfigureScaffolding() {
        $rootPackage = $this->composer->getPackage();
        $extra = $rootPackage->getExtra();

        // Get current scaffold configuration
        $scaffoldConfig = $extra['drupal-scaffold'] ?? [];
        $fileMapping = $scaffoldConfig['file-mapping'] ?? [];

        // Define Nightwatch scaffold files
        $nightwatchFiles = ScaffoldHandler::getNightwatchScaffoldFiles();

        if (ScaffoldHandler::isNightwatchEnabled($extra)) {
            // Add Nightwatch files to scaffolding
            foreach ($nightwatchFiles as $dest => $src) {
                $fileMapping[$dest] = $src;
            }
        } else {
            // Remove Nightwatch files from scaffolding or skip them
            foreach ($nightwatchFiles as $dest => $src) {
                $fileMapping[$dest] = ['mode' => 'skip'];
            }
        }

        // Update the scaffold configuration
        $scaffoldConfig['file-mapping'] = $fileMapping;
        $extra['drupal-scaffold'] = $scaffoldConfig;
        $rootPackage->setExtra($extra);
    }
}
