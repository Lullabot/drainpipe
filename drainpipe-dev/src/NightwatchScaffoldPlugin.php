<?php

namespace Lullabot\DrainpipeDev;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class NightwatchScaffoldPlugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * Composer instance configuration.
     *
     * @var Config
     */
    protected $config;

    /**
     * @var IOInterface
     */
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->config = $composer->getConfig();
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => 'onPreInstallCmd',
            ScriptEvents::PRE_UPDATE_CMD => 'onPreUpdateCmd',
        ];
    }

    public function onPreInstallCmd(Event $event)
    {
        $this->installNightwatch();
    }

    public function onPreUpdateCmd(Event $event)
    {
        $this->installNightwatch();
    }

    protected function installNightwatch()
    {
        // Get the root package to read configuration
        $rootPackage = $this->composer->getPackage();
        $rootExtra = $rootPackage->getExtra();

        // Check if Nightwatch is enabled
        if (!$this->isNightwatchEnabled($rootExtra)) {
            $this->io->write('🪠 [Drainpipe] Nightwatch is disabled');
            return;
        }

        // Get current scaffold configuration from root package
        $scaffoldConfig = $rootExtra['drupal-scaffold'] ?? [];
        $fileMapping = $scaffoldConfig['file-mapping'] ?? [];

        // Define Nightwatch scaffold files
        $nightwatchFiles = $this->getNightwatchScaffoldFiles();

        // Add Nightwatch scaffold files to root package's file mapping
        $vendor = $this->config->get('vendor-dir');
        foreach ($nightwatchFiles as $dest => $source) {
            $fileMapping[$dest] = sprintf('%s/lullabot/drainpipe-dev/%s', $vendor, $source);
        }

        // Update the root package's scaffold configuration
        $scaffoldConfig['file-mapping'] = $fileMapping;
        $rootExtra['drupal-scaffold'] = $scaffoldConfig;
        $rootPackage->setExtra($rootExtra);

        $this->io->write('🪠 [Drainpipe] Nightwatch files scaffolded');

        $output = print_r($scaffoldConfig['file-mapping'], true);
        $this->io->write('🪠 [Drainpipe] ' . $output);
    }

    /**
     * Check if Nightwatch testing is enabled in project configuration.
     *
     * @param array $projectExtra
     * @return bool
     */
    protected function isNightwatchEnabled(array $projectExtra): bool {
        $drainpipeConfig = $projectExtra['drainpipe'] ?? [];
        $testingConfig = $drainpipeConfig['testing'] ?? [];
        return is_array($testingConfig) && in_array('Nightwatch', $testingConfig);
    }

    /**
     * Get all Nightwatch-related scaffold files.
     *
     * @return array
     */
    protected function getNightwatchScaffoldFiles(): array {
        return [
            '[project-root]/nightwatch.conf.js' => 'scaffold/nightwatch/nightwatch.conf.js',
            '[project-root]/.ddev/docker-compose.selenium.yaml' => 'scaffold/nightwatch/.ddev/docker-compose.selenium.yaml',
            '[project-root]/test/nightwatch/example.nightwatch.js' => 'scaffold/nightwatch/test/nightwatch/example.nightwatch.js',
            '[project-root]/test/nightwatch/vrt/.gitignore' => 'scaffold/nightwatch/vrt.gitignore',
            '[web-root]/sites/chrome/settings.php' => 'scaffold/nightwatch/chrome.settings.php',
            '[web-root]/sites/firefox/settings.php' => 'scaffold/nightwatch/firefox.settings.php',
            '[web-root]/sites/sites.php' => 'scaffold/nightwatch/sites.php',
        ];
    }
}
