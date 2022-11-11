<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ScaffoldInstallerPlugin implements PluginInterface, EventSubscriberInterface
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
     * Composer extra field configuration.
     *
     * @var array
     */
    protected $extra;

    /**
     * @var \Composer\Package\Link[]
     */
    private $platformRequirements;

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->platformRequirements = $composer->getLocker()->getPlatformRequirements();
        $this->config = $composer->getConfig();
        $this->extra = $composer->getPackage()->getExtra();
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
        $this->platformRequirements = $event->getComposer()->getLocker()->getPlatformRequirements();
        $this->installTaskfile();
        $this->installGitignore();
        $this->installDdevCommand();
        $this->installCICommands();
    }

    /**
     * Handle post update command events.
     *
     * @param event $event The event to handle
     */
    public function onPostUpdateCmd(Event $event)
    {
        $this->platformRequirements = $event->getComposer()->getLocker()->getPlatformRequirements();
        $this->installTaskfile();
        $this->installGitignore();
        $this->installDdevCommand();
        $this->installCICommands();
    }

    /**
     * Copies Taskfile.yml from the scaffold directory if it doesn't yet exist.
     */
    private function installTaskfile(): void
    {
        $vendor = $this->config->get('vendor-dir');
        $taskfilePath = $vendor.'/lullabot/drainpipe/scaffold/Taskfile.yml';

        if (!file_exists('./Taskfile.yml')) {
            $this->io->write('<info>Creating initial Taskfile.yml...</info>');
            $fs = new Filesystem();
            $fs->copy(
                $taskfilePath,
                './Taskfile.yml'
            );
        } else {
            $scaffoldTaskFile = Yaml::parseFile($taskfilePath);
            $projectTaskfile = Yaml::parseFile('./Taskfile.yml');
            foreach ($scaffoldTaskFile['includes'] as $key => $value) {
                if (empty($projectTaskfile['includes'][$key]) || $projectTaskfile['includes'][$key] !== $value) {
                    $this->io->warning(
                        'Taskfile.yml has either been customized or requires review.'
                    );
                    $this->io->warning(
                        sprintf(
                            'Compare Taskfile.yml includes in the root of your repository with %s and update as needed.',
                            $taskfilePath
                        )
                    );
                    break;
                }
            }
        }
    }

    /**
     * Copies gitignore from the scaffold directory if it doesn't yet exist.
     */
    private function installGitignore(): void
    {
        $vendor = $this->config->get('vendor-dir');
        $gitignorePath = $vendor.'/lullabot/drainpipe/scaffold/gitignore';
        if (!file_exists('./.gitignore')) {
            $this->io->write('<info>Creating initial .gitignore...</info>');
            $fs = new Filesystem();
            $fs->copy("$gitignorePath/common.gitignore", './.gitignore');
            if (file_exists('./.ddev/config.yaml')) {
                $fp = fopen('./.gitignore', 'a');
                fwrite($fp, file_get_contents("$gitignorePath/ddev.gitignore"));
                fclose($fp);
            }
        } else {
            $contents = file_get_contents('./.gitignore');
            if (strpos($contents, '.task') === false) {
                $this->io->warning(
                    sprintf(
                        '.gitignore does not contain drainpipe ignores. Compare .gitignore in the root of your repository with %s and update as needed.',
                        $gitignorePath
                    )
                );
            }
        }
    }

    /**
     *
     */
    private function installDdevCommand(): void
    {
        if (file_exists('./.ddev/config.yaml')) {
            $vendor = $this->config->get('vendor-dir');
            $ddevCommandPath = $vendor.'/lullabot/drainpipe/scaffold/ddev/task-command.sh';
            $fs = new Filesystem();
            $fs->ensureDirectoryExists('./.ddev/commands/web');
            $fs->copy($ddevCommandPath, './.ddev/commands/web/task');

            # Enable .env file support via docker-composer web environment.
            if (is_file('./.env')) {
                $fs->copy($vendor.'/lullabot/drainpipe/scaffold/ddev/docker-compose.env-file.yaml', './.ddev/docker-compose.env-file.yaml');
            }
        }
    }

    /**
     * Install CI Commands.
     */
    private function installCICommands(): void
    {
        $scaffoldPath = $this->config->get('vendor-dir') . '/lullabot/drainpipe/scaffold';
        $fs = new Filesystem();
        // GitLab
        $fs->removeDirectory('./.drainpipe/gitlab');
        if (isset($this->extra['drainpipe']['gitlab']) && is_array($this->extra['drainpipe']['gitlab'])) {
            $fs->ensureDirectoryExists('./.drainpipe/gitlab');
            $fs->copy("$scaffoldPath/gitlab/Common.gitlab-ci.yml", ".drainpipe/gitlab/Common.gitlab-ci.yml");
            $this->io->write("🪠 [Drainpipe] .drainpipe/gitlab/Common.gitlab-ci.yml installed");
            foreach ($this->extra['drainpipe']['gitlab'] as $gitlab) {
                $file = "gitlab/$gitlab.gitlab-ci.yml";
                if (file_exists("$scaffoldPath/$file")) {
                    $fs->copy("$scaffoldPath/$file", ".drainpipe/$file");
                    $this->io->write("🪠 [Drainpipe] .drainpipe/$file installed");
                }
                else {
                    $this->io->warning("🪠 [Drainpipe] $scaffoldPath/$file does not exist");
                }

                if ($gitlab === 'Pantheon') {
                    // @TODO this isn't really specific to GitLab
                    // .drainpipeignore
                    if (!file_exists('.drainpipeignore')) {
                        $fs->copy("$scaffoldPath/pantheon/.drainpipeignore", '.drainpipeignore');
                    }
                    else {
                        $contents = file_get_contents('./.drainpipeignore');
                        if (strpos($contents, '/web/sites/default/files') === false) {
                            $this->io->warning(
                                sprintf(
                                    '.gitignore does not contain drainpipe ignores. Compare .drainpipeignore in the root of your repository with %s and update as needed.',
                                    "$scaffoldPath/pantheon/.drainpipeignore"
                                )
                            );
                        }
                    }
                    // pantheon.yml
                    if (!file_exists('./pantheon.yml')) {
                        $fs->copy("$scaffoldPath/pantheon/pantheon.yml", './pantheon.yml');
                    }
                    // settings.pantheon.php
                    if (!file_exists('./web/sites/default/settings.pantheon.php')) {
                        $fs->copy("$scaffoldPath/pantheon/settings.pantheon.php", './web/sites/default/settings.pantheon.php');
                    }
                }
            }
            if (!file_exists('./.gitlab-ci.yml')) {
                $fs->copy("$scaffoldPath/gitlab/gitlab-ci.example.yml", './.gitlab-ci.yml');
            }
        }
        // GitHub
        $fs->removeDirectory('./.github/actions/drainpipe');
        if (isset($this->extra['drainpipe']['github']) && is_array($this->extra['drainpipe']['github'])) {
            $fs->ensureDirectoryExists('./.github/actions');
            $fs->copy("$scaffoldPath/github/actions/common", './.github/actions/drainpipe');
            foreach ($this->extra['drainpipe']['github'] as $github) {
                if ($github === 'PantheonReviewApps') {
                    $fs->ensureDirectoryExists('./.github/actions/drainpipe/pantheon');
                    $fs->ensureDirectoryExists('./.github/workflows');
                    $fs->copy("$scaffoldPath/github/actions/pantheon", './.github/actions/drainpipe/pantheon');
                    if (!file_exists('./.github/workflows/PantheonReviewApps.yml')) {
                        if (file_exists('./.ddev/config.yaml')) {
                            $fs->copy("$scaffoldPath/github/workflows/PantheonReviewAppsDDEV.yml", './.github/workflows/PantheonReviewApps.yml');
                        }
                        else {
                            $fs->copy("$scaffoldPath/github/workflows/PantheonReviewApps.yml", './.github/workflows/PantheonReviewApps.yml');
                        }
                    }
                }
            }
        }

        // Tugboat
        if (isset($this->extra['drainpipe']['tugboat']) && is_array($this->extra['drainpipe']['tugboat'])) {
            $php = $this->getPhpVersion();
            if (!file_exists('./.tugboat/config.yml')) {
                $fs->ensureDirectoryExists('./.tugboat');
                $provider = $this->extra['drainpipe']['tugboat']['provider'];
                if (!empty($provider)) {
                    $host = $this->extra['drainpipe']['tugboat']['provider']['host'];
                }
                else {
                    $host = ProviderInterface::HOST_UNKNOWN;
                }

                $tugboatConfig = new TugboatConfig($php);
                $downsync = $this->extra['drainpipe']['tugboat']['provider']['downsync'] ?? false;
                $tugboatConfig->writeFile('config.yml.twig', './.tugboat/', $host,
                    $downsync
                );
                $fs->ensureDirectoryExists('./.tugboat/steps');
                $fs->copy("$scaffoldPath/tugboat/steps/init.sh", './.tugboat/steps/init.sh');
                $fs->copy("$scaffoldPath/tugboat/steps/build.sh", './.tugboat/steps/build.sh');
                $tugboatConfig->writeFile('steps/update.sh.twig', './.tugboat/steps/', $host,
                    $downsync
                );

                $this->io->write("🪠 [Drainpipe] .tugboat/ directory installed. Please commit this directory.");
                if (!file_exists('./web/sites/default/settings.tugboat.php')) {
                    $tugboatConfig->writeFile('settings.tugboat.php.twig', './web/sites/default/', $host);

                    $this->io->write("🪠 [Drainpipe] web/sites/default/settings.tugboat.php installed. Please commit this file.");
                    if (file_exists('./web/sites/default/settings.php')) {
                        $include=<<<EOD

include __DIR__ . "/settings.tugboat.php";
EOD;

                        file_put_contents('./web/sites/default/settings.php', $include, FILE_APPEND);
                        $this->io->write("🪠 [Drainpipe] web/sites/default/settings.php modified to include settings.tugboat.php. Please commit this file.");
                    }
                    else {
                        $this->io->write("🪠 [Drainpipe] web/sites/default/settings.php does not exist. Please include tugboat.settings.php from your settings.php files.");
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    private function getPhpVersion(): string
    {
        $php = '8.1';
        foreach ($this->platformRequirements as $link) {
            if ($link->getTarget() === "php") {
                $lower = $link->getConstraint()->getLowerBound()->getVersion();
                $php = substr($lower, 0, 3);
            }
        }

        return $php;
    }

}
