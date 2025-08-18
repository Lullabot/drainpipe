<?php

namespace Lullabot\DrainpipeDev;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Package\PackageInterface;

class NightwatchScaffoldPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
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
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'onPreAutoloadDump',
        ];
    }

    public function onPreAutoloadDump(Event $event)
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

        // Get drainpipe-dev Composer package
        $drainpipeDevPackage = $this->findDrainpipeDevPackage();
        if (!$drainpipeDevPackage) {
            $this->io->warning('🪠 [Drainpipe] Could not find drainpipe-dev package');
            return;
        }

        // Get current scaffold configuration from drainpipe-dev
        $drainpipeDevExtra = $drainpipeDevPackage->getExtra();
        $scaffoldConfig = $drainpipeDevExtra['drupal-scaffold'] ?? [];
        $fileMapping = $scaffoldConfig['file-mapping'] ?? [];

        // Define Nightwatch scaffold files
        $nightwatchFiles = $this->getNightwatchScaffoldFiles();

        foreach ($nightwatchFiles as $dest => $src) {
            $fileMapping[$dest] = $src;
        }

        // Add Nightwatch scaffold files to drainpipe-dev settings
        $scaffoldConfig['file-mapping'] = $fileMapping;
        $drainpipeDevExtra['drupal-scaffold'] = $scaffoldConfig;
        $drainpipeDevPackage->setExtra($drainpipeDevExtra);
    }

    protected function findDrainpipeDevPackage(): ?PackageInterface
    {
        $repositoryManager = $this->composer->getRepositoryManager();
        $localRepository = $repositoryManager->getLocalRepository();

        // Look for drainpipe-dev package in installed packages
        foreach ($localRepository->getPackages() as $package) {
            if ($package->getName() === 'lullabot/drainpipe-dev') {
                return $package;
            }
        }

        return null;
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
