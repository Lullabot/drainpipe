<?php

namespace Lullabot\DrainpipeDev\Composer\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;
use Composer\Package\PackageInterface;

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
        // Get the root package to read configuration
        $rootPackage = $this->composer->getPackage();
        $rootExtra = $rootPackage->getExtra();

        // Check if Nightwatch is enabled in testing configuration
        $nightwatchEnabled = $this->isNightwatchEnabled($rootExtra);

        // Find the drainpipe-dev package in the repository
        $drainpipeDevPackage = $this->findDrainpipeDevPackage();

        if (!$drainpipeDevPackage) {
            $this->io->writeError('<warning>Could not find drainpipe-dev package</warning>');
            return;
        }

        // Get current scaffold configuration from drainpipe-dev
        $drainpipeDevExtra = $drainpipeDevPackage->getExtra();
        $scaffoldConfig = $drainpipeDevExtra['drupal-scaffold'] ?? [];
        $fileMapping = $scaffoldConfig['file-mapping'] ?? [];

        // Define Nightwatch scaffold files
        $nightwatchFiles = $this->getNightwatchScaffoldFiles();

        if ($nightwatchEnabled) {
            // Add Nightwatch files to scaffolding
            foreach ($nightwatchFiles as $dest => $src) {
                $fileMapping[$dest] = $src;
            }
            $this->io->write('<info>Nightwatch scaffolding enabled</info>');
        } else {
            // Remove Nightwatch files from scaffolding or skip them
            foreach ($nightwatchFiles as $dest => $src) {
                $fileMapping[$dest] = ['mode' => 'skip'];
            }
            $this->io->write('<info>Nightwatch scaffolding disabled</info>');
        }

        // Update the drainpipe-dev package's scaffold configuration
        $scaffoldConfig['file-mapping'] = $fileMapping;
        $drainpipeDevExtra['drupal-scaffold'] = $scaffoldConfig;
        $drainpipeDevPackage->setExtra($drainpipeDevExtra);
    }

    /**
     * Find the drainpipe-dev package in the composer repository.
     *
     * @return PackageInterface|null
     */
    protected function findDrainpipeDevPackage(): ?PackageInterface {
        $repositoryManager = $this->composer->getRepositoryManager();
        $localRepository = $repositoryManager->getLocalRepository();

        // Look for drainpipe-dev package in installed packages
        foreach ($localRepository->getPackages() as $package) {
            if ($package->getName() === 'lullabot/drainpipe-dev') {
                return $package;
            }
        }

        // If not found in local repository, try to find it in the installed packages
        $installedRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $packages = $installedRepository->findPackages('lullabot/drainpipe-dev');

        if (!empty($packages)) {
            return $packages[0];
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
            '[project-root]/nightwatch.conf.js' => [
                'source' => 'vendor/lullabot/drainpipe-dev/scaffold/nightwatch/nightwatch.conf.js',
                'overwrite' => false,
            ],
            '[project-root]/.ddev/docker-compose.selenium.yaml' => [
                'source' => 'vendor/lullabot/drainpipe-dev/scaffold/nightwatch/.ddev/docker-compose.selenium.yaml',
                'overwrite' => false,
            ],
            '[project-root]/test/nightwatch/example.nightwatch.js' => [
                'source' => 'vendor/lullabot/drainpipe-dev/scaffold/nightwatch/test/nightwatch/example.nightwatch.js',
                'overwrite' => false,
            ],
            '[project-root]/test/nightwatch/vrt/.gitignore' => [
                'source' => 'vendor/lullabot/drainpipe-dev/scaffold/nightwatch/vrt.gitignore',
                'overwrite' => false,
            ],
            '[web-root]/sites/chrome/settings.php' => [
                'source' => 'vendor/lullabot/drainpipe-dev/scaffold/nightwatch/chrome.settings.php',
                'overwrite' => false,
            ],
            '[web-root]/sites/firefox/settings.php' => [
                'source' => 'vendor/lullabot/drainpipe-dev/scaffold/nightwatch/firefox.settings.php',
                'overwrite' => false,
            ],
            '[web-root]/sites/sites.php' => [
                'source' => 'vendor/lullabot/drainpipe-dev/scaffold/nightwatch/sites.php',
                'overwrite' => false,
            ],
        ];
    }
}
