<?php

declare(strict_types=1);

namespace Lullabot\DrainpipeDev;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

class DevScaffoldInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * Composer instance configuration.
     *
     * @var Config
     */
    protected $config;

    /**
     * The environment in which we're currently running e.g. ddev
     *
     * @var string|null
     */
    protected $environment = null;

    /**
     * A list of commands we would like the user to run after install or update.
     *
     * @var array
     */
    protected $userCommands = [];

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->config = $composer->getConfig();
        if (file_exists('./.ddev/config.yaml')) {
            $this->environment = 'ddev';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstallCmd',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCmd',
        ];
    }

    /**
     * Handle post install command events.
     *
     * @param Event $event the event to handle
     */
    public function onPostInstallCmd(Event $event)
    {
        $this->installDevTaskfile();
        $this->installDdevSeleniumConfig();
        $this->installNightwatchConfig();
        $this->installPhpCsConfig();
        $this->printUserCommands();
    }

    /**
     * Handle post update command events.
     *
     * @param event $event The event to handle
     */
    public function onPostUpdateCmd(Event $event)
    {
        $this->installDevTaskfile();
        $this->installDdevSeleniumConfig();
        $this->installNightwatchConfig();
        $this->printUserCommands();
    }

    /**
     * Helper to copy a file from the scaffold directory into the project.
     *
     * @param string $source
     * @param string $destination
     */
    private function installScaffoldFile(string $source, string $destination): void
    {
        $vendor = $this->config->get('vendor-dir');
        $filePath = "$vendor/lullabot/drainpipe-dev/scaffold/$source";

        if (!file_exists("./$destination")) {
            $this->io->write("<info>Creating initial $destination...</info>");
            $fs = new Filesystem();
            $fs->copy(
                $filePath,
                "./$destination"
            );
        }
    }

    /**
     * Let's the user know they need to run some commands post update/install.
     */
    private function printUserCommands() {
        if (!empty($this->userCommands)) {
            foreach ($this->userCommands as $userCommand) {
                $this->io->warning($userCommand);
            }
        }
    }

    /**
     * Copies Taskfile.dev.yml from the scaffold directory if it doesn't yet exist.
     */
    private function installDevTaskfile(): void
    {
        $this->installScaffoldFile('Taskfile.dev.yml', 'Taskfile.dev.yml');
        $vendor = $this->config->get('vendor-dir');
        $taskfilePath = "$vendor/lullabot/drainpipe-dev/scaffold/Taskfile.dev.yml";
        $scaffoldTaskfile = Yaml::parseFile($taskfilePath);
        $projectTaskfile = Yaml::parseFile("./Taskfile.dev.yml");
        foreach ($scaffoldTaskfile['includes'] as $key => $value) {
            if (empty($projectTaskfile['includes'][$key]) || $projectTaskfile['includes'][$key] !== $value) {
                $this->io->warning(
                    'Taskfile.dev.yml has either been customized or requires review.'
                );
                $this->io->warning(
                    sprintf(
                        'Compare Taskfile.dev.yml includes in the root of your repository with %s and update as needed.',
                        $taskfilePath
                    )
                );
                break;
            }
        }
    }

    /**
     * Copies docker-compose.selenium.yaml from the scaffold directory if it doesn't yet exist.
     */
    private function installDdevSeleniumConfig(): void
    {
        if ($this->environment === 'ddev') {
            $vendor = $this->config->get('vendor-dir');
            $fs = new Filesystem();
            $fs->ensureDirectoryExists('web/sites/firefox');
            $fs->ensureDirectoryExists('web/sites/chrome');
            $scaffoldFiles = [
                'docker-compose.selenium.yaml' => '.ddev/docker-compose.selenium.yaml',
                'firefox.settings.php' => 'web/sites/firefox/settings.php',
                'chrome.settings.php' => 'web/sites/chrome/settings.php',
                'sites.php' => 'web/sites/sites.php',
            ];
            foreach ($scaffoldFiles as $scaffoldFile => $destination) {
                $source = "$vendor/lullabot/drainpipe-dev/scaffold/$scaffoldFile";
                $this->installScaffoldFile($scaffoldFile, $destination);
                if (sha1_file($source) !== sha1_file($destination)) {
                    $this->io->warning(
                        sprintf('%s has either been customized or requires review.',
                            $destination
                        )
                    );
                    $this->io->warning(
                        sprintf(
                            'Compare %s with %s and update as needed.',
                            $destination,
                            $source,
                        )
                    );
                }
            }
            $ddevConfig = Yaml::parseFile('./.ddev/config.yaml');
            $ddevRestartNeeded = false;
            // Check Databases will be created for Firefox and Chrome
            $postStartExec = [];
            if (!empty($ddevConfig['hooks']['post-start'])) {
                $postStartExec = array_filter($ddevConfig['hooks']['post-start'], function ($command) {
                    return !empty($command['exec']);
                });
                $postStartExec = array_map(function ($command) {
                    return $command['exec'];
                }, $postStartExec);
            }
            $databaseFirefox = 'mysql -uroot -proot -hdb -e "CREATE DATABASE IF NOT EXISTS firefox; GRANT ALL ON firefox.* TO \'db\'@\'%\';"';
            $databaseChrome = 'mysql -uroot -proot -hdb -e "CREATE DATABASE IF NOT EXISTS chrome; GRANT ALL ON chrome.* TO \'db\'@\'%\';"';
            if (!in_array($databaseFirefox, $postStartExec) || !in_array($databaseChrome, $postStartExec)) {
                $this->userCommands[] = 'Add the following to .ddev/config.yaml post-start hooks to .ddev/config.yaml:';
                $this->userCommands[] = "hooks:";
                $this->userCommands[] = "  post-start:";
                $this->userCommands[] = "    - exec: $databaseFirefox";
                $this->userCommands[] = "    - exec: $databaseChrome";
                $ddevRestartNeeded = true;
            }
            // Make sure Selenium can access the Drupal site.
            $webEnvironmentFirefox = 'NIGHTWATCH_DRUPAL_URL_FIREFOX=https://drupal_firefox';
            $webEnvironmentChrome = 'NIGHTWATCH_DRUPAL_URL_CHROME=https://drupal_chrome';
            if (!is_array($ddevConfig['web_environment']) || !in_array($webEnvironmentFirefox, $ddevConfig['web_environment']) || !in_array($webEnvironmentChrome, $ddevConfig['web_environment'])) {
                $this->userCommands[] = 'Run:';
                $this->userCommands[] = 'ddev config --web-environment="NIGHTWATCH_DRUPAL_URL_FIREFOX=https://drupal_firefox,NIGHTWATCH_DRUPAL_URL_CHROME=https://drupal_chrome"';
                $ddevRestartNeeded = true;
            }
            // Set additional hostnames so chrome and firefox sites can be accessed.
            $additionalHostname = '*.' . getenv('DDEV_SITENAME');
            if (!is_array($ddevConfig['additional_hostnames']) || !in_array($additionalHostname, $ddevConfig['additional_hostnames'])) {
                $this->userCommands[] = "ddev config --additional-hostnames=\"$additionalHostname\"";
                $ddevRestartNeeded = true;
            }
            if ($ddevRestartNeeded) {
                $this->userCommands[] = 'ddev restart';
            }
        }
    }

    /**
     * Copies nightwatch.conf.js from the scaffold directory if it doesn't yet exist.
     */
    private function installNightwatchConfig(): void
    {
        $fs = new Filesystem();

        if (!file_exists('./nightwatch.conf.js')) {
            $this->installScaffoldFile('nightwatch.conf.js', 'nightwatch.conf.js');
        }
        // Install an example test if none exist.
        if (!file_exists('./test/nightwatch')) {
            $fs->ensureDirectoryExists('./test/nightwatch');
            $this->installScaffoldFile('example.nightwatch.js', 'test/nightwatch/example.nightwatch.js');
        }

        // Create a new yarn project if package.json doesn't exist
        if (!file_exists('./package.json')) {
            $yarn = new Process(['yarn', 'set', 'version', 'berry']);
            $yarn->run(function($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->io->write('ERR > '.$buffer);
                } else {
                    $this->io->write('YARN > '.$buffer);
                }
            });
            $yarn = new Process(['yarn', 'init', '-p']);
            $yarn->run(function($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->io->write('ERR > '.$buffer);
                } else {
                    $this->io->write('YARN > '.$buffer);
                }
            });
            // Override the package.json name field as yarn will have used the
            //folder name, which is just "html" in DDEV.
            if ($this->environment === 'ddev' && !empty(getenv('DDEV_PROJECT'))) {
                $packageJsonContents = file_get_contents('./package.json');
                $packageJsonContents = str_replace('html', getenv('DDEV_PROJECT'), $packageJsonContents);
                file_put_contents('./package.json', $packageJsonContents);
            }
        }

        $dependencies = [
            '@lullabot/nightwatch-drupal-commands' => '@lullabot/nightwatch-drupal-commands@https://github.com/Lullabot/nightwatch-drupal-commands.git#main --dev',
            'nightwatch' => 'nightwatch',
            'nightwatch-accessibility' => 'nightwatch-accessibility',
        ];
        $needToInstall = [];

        // Detect if the required dependencies are already present.
        if (file_exists('package.json')) {
            $packageJson = json_decode(file_get_contents('./package.json'), true);
            foreach(array_keys($dependencies) as $dependency) {
                if (!isset($packageJson['devDependencies'][$dependency]) || empty($packageJson['devDependencies'][$dependency])) {
                    $needToInstall[] = $dependency;
                }
            }
        } else {
            throw new \Exception('package.json does not exist and was unable to be auto-created. Please create one.');
        }

        if (!empty($needToInstall)) {
            if (file_exists('yarn.lock')) {
                $this->userCommands[] = sprintf('yarn add %s --dev', implode(' ', array_values($dependencies)));
                if ($this->environment === 'ddev') {
                    $this->userCommands[] = 'ddev exec yarn';
                }
            } else if (file_exists('package-lock.json')) {
                $this->userCommands[] = sprintf('npm install %s --save-dev', implode(' ', array_values($dependencies)));
                if ($this->environment === 'ddev') {
                    $this->userCommands[] = 'ddev npm install';
                }
            } else {
                $this->io->warning(
                    sprintf('Yarn or NPM lockfile not found, please manually install Nightwatch dependencies %s',
                        implode(', ', $needToInstall)
                    )
                );
            }
        }

    }

    private function installPhpCsConfig(): void
    {
        if (!file_exists('./phpcs.xml')) {
            $this->installScaffoldFile('phpcs.xml', 'phpcs.xml');
        }
    }
}
