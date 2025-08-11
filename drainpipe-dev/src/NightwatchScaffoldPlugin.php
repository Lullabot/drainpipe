<?php

namespace Lullabot\DrainpipeDev;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

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
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstallCmd',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCmd',
        ];
    }

    public function onPostInstallCmd(Event $event)
    {
        $this->installNightwatch();
    }

    public function onPostUpdateCmd(Event $event)
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

        // Identify some relevant paths
        $fs = new Filesystem();
        $vendor = $this->config->get('vendor-dir');
        $placeholders = [
            '[project-root]' => dirname(Factory::getComposerFile()),
            '[web-root]' => sprintf('%s/web', dirname(Factory::getComposerFile())),
        ];

        // Define Nightwatch scaffold files
        $nightwatchFiles = $this->getNightwatchScaffoldFiles();

        // Copy Nightwatch scaffold files their location
        foreach ($nightwatchFiles as $dest => $source) {
            $target = str_replace(array_keys($placeholders), array_values($placeholders), $dest);
            $fs->ensureDirectoryExists(dirname($target));
            $fs->copy("$vendor/lullabot/drainpipe-dev/$source", $target);
        }

        $this->io->write('<info>🪠 [Drainpipe] Nightwatch files scaffolded</info>');
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
            '[project-root]/.ddev/docker-compose.selenium.yaml' => 'scaffold/nightwatch/docker-compose.selenium.yaml',
            '[project-root]/test/nightwatch/example.nightwatch.js' => 'scaffold/nightwatch/example.nightwatch.js',
            '[project-root]/test/nightwatch/vrt/.gitignore' => 'scaffold/nightwatch/vrt.gitignore',
            '[web-root]/sites/chrome/settings.php' => 'scaffold/nightwatch/chrome.settings.php',
            '[web-root]/sites/firefox/settings.php' => 'scaffold/nightwatch/firefox.settings.php',
            '[web-root]/sites/sites.php' => 'scaffold/nightwatch/sites.php',
        ];
    }
}
