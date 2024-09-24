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
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
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
        $this->installTaskfile();
        $this->installGitignore();
        $this->installDdevCommand();
        $this->installCICommands();
        $this->installEnvSupport();
    }

    /**
     * Handle post update command events.
     *
     * @param event $event The event to handle
     */
    public function onPostUpdateCmd(Event $event)
    {
        $this->installTaskfile();
        $this->installGitignore();
        $this->installDdevCommand();
        $this->installCICommands();
        $this->installEnvSupport();
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
            if (empty($projectTaskfile['tasks']['sync'])) {
                $this->io->warning(
                    'Taskfile.yml does not contain a "sync" task.'
                );
            }
            if (empty($projectTaskfile['tasks']['build'])) {
                $this->io->warning(
                    'Taskfile.yml does not contain a "build" task.'
                );
            }
            if (empty($projectTaskfile['tasks']['update'])) {
                $this->io->warning(
                    'Taskfile.yml does not contain an "update" task and will fall back to using "task drupal:update".'
                );
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
     * Install .env support.
     */
    private function installEnvSupport(): void
    {
        $fs = new Filesystem();
        $vendor = $this->config->get('vendor-dir');
        // Copy this over as the other files in composer drupal-scaffold
        // are added to the gitignore, and this should be checked in.
        if (!is_file('./.env.defaults')) {
            $fs->copy($vendor . '/lullabot/drainpipe/scaffold/env/env.defaults', './.env.defaults');
        }
        // There has to be a better way of doing this?
        $vendorRelative = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $vendor);
        $composerJson = file_get_contents('composer.json');
        $composerFullConfig = json_decode($composerJson, true);
        if (empty($composerFullConfig['autoload-dev']['files']) || !in_array("$vendorRelative/lullabot/drainpipe/scaffold/env/dotenv.php", $composerFullConfig['autoload-dev']['files'])) {
            $this->io->warning("🪠 [Drainpipe] $vendorRelative/lullabot/drainpipe/scaffold/env/dotenv.php' missing from autoload-dev files");
        }
    }

    /**
     *
     */
    private function installDdevCommand(): void
    {
        if (file_exists('./.ddev/config.yaml')) {
            $vendor = $this->config->get('vendor-dir');
            $ddevCommandPath = $vendor . '/lullabot/drainpipe/scaffold/ddev/task-command.sh';
            $fs = new Filesystem();
            $fs->ensureDirectoryExists('./.ddev/commands/web');
            $fs->copy($ddevCommandPath, './.ddev/commands/web/task');
            if (file_exists('./web/sites/default/settings.ddev.php')) {
                $settings = file_get_contents('./web/sites/default/settings.ddev.php');
                if (strpos($settings, 'environment-indicator') === false) {
                    $include = <<<'EOT'
// See https://architecture.lullabot.com/adr/20210609-environment-indicator/
$config['environment_indicator.indicator']['name'] = 'Local';
$config['environment_indicator.indicator']['bg_color'] = '#505050';
$config['environment_indicator.indicator']['fg_color'] = '#ffffff';
EOT;
                    file_put_contents('./web/sites/default/settings.ddev.php', $include . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }

    /**
     * Install CI Commands.
     */
    private function installCICommands(): void
    {
        $scaffoldPath = $this->config->get('vendor-dir') . '/lullabot/drainpipe/scaffold';
        $this->installGitlabCI($scaffoldPath);
        $this->installGitHubActions($scaffoldPath);
        $this->installTugboat($scaffoldPath);
    }

    /**
     * Install GitLab CI configuration if defined in composer.json
     *
     * @param string $scaffoldPath The path to the scaffold files to copy from.
     */
    private function installGitlabCI(string $scaffoldPath): void {
        $fs = new Filesystem();
        $fs->removeDirectory('./.drainpipe/gitlab');
        
        if (!isset($this->extra['drainpipe']['gitlab']) || !is_array($this->extra['drainpipe']['gitlab'])) {
            return;
        }

        $fs->ensureDirectoryExists('./.drainpipe/gitlab');
        if (file_exists('./.ddev/config.yaml')) {
            $fs->ensureDirectoryExists('.gitlab/drainpipe');
            $fs->copy("$scaffoldPath/gitlab/DDEV.gitlab-ci.yml", ".gitlab/drainpipe/DDEV.gitlab-ci.yml");
            $this->io->write("🪠 [Drainpipe] .gitlab/drainpipe/DDEV.gitlab-ci.yml installed");
        }
        else {
            $fs->copy("$scaffoldPath/gitlab/Common.gitlab-ci.yml", ".drainpipe/gitlab/Common.gitlab-ci.yml");
            $this->io->write("🪠 [Drainpipe] .drainpipe/gitlab/Common.gitlab-ci.yml installed");
        }

        $fs->copy("$scaffoldPath/gitlab/Nightwatch.gitlab-ci.yml", ".drainpipe/gitlab/Nightwatch.gitlab-ci.yml");
        $this->io->write("🪠 [Drainpipe] .drainpipe/gitlab/Nightwatch.gitlab-ci.yml installed");

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

    /**
     * Install GitLab CI configuration if defined in composer.json
     *
     * @param string $scaffoldPath The path to the scaffold files to copy from.
     */
    private function installGitHubActions(string $scaffoldPath): void {
        $fs = new Filesystem();
        $fs->removeDirectory('./.github/actions/drainpipe');

        if (!isset($this->extra['drainpipe']['github']) || !is_array($this->extra['drainpipe']['github'])) {
            return;
        }

        $fs->ensureDirectoryExists('./.github/actions');
        $fs->copy("$scaffoldPath/github/actions/common", './.github/actions/drainpipe');
        foreach ($this->extra['drainpipe']['github'] as $github) {
            if ($github === 'PantheonReviewApps') {
                $fs->ensureDirectoryExists('./.github/actions/drainpipe/pantheon');
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/actions/pantheon", './.github/actions/drainpipe/pantheon');
                if (file_exists('./.ddev/config.yaml')) {
                    $fs->copy("$scaffoldPath/github/workflows/PantheonReviewAppsDDEV.yml", './.github/workflows/PantheonReviewApps.yml');
                }
                else {
                    $fs->copy("$scaffoldPath/github/workflows/PantheonReviewApps.yml", './.github/workflows/PantheonReviewApps.yml');
                }
            }
            else if ($github === 'ComposerLockDiff') {
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/workflows/ComposerLockDiff.yml", './.github/workflows/ComposerLockDiff.yml');
            }
            else if ($github === 'Security') {
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/workflows/Security.yml", './.github/workflows/Security.yml');
            }
            else if ($github === 'TestStatic') {
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/workflows/TestStatic.yml", './.github/workflows/TestStatic.yml');
            }
            else if ($github === 'TestFunctional') {
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/workflows/TestFunctional.yml", './.github/workflows/TestFunctional.yml');
            }
        }
    }

    /**
     * Installs Tugboat if defined in composer.json.
     *
     * @param string $scaffoldPath
     */
    private function installTugboat(string $scaffoldPath): void {
        $fs = new Filesystem();

        if (!isset($this->extra['drainpipe']['tugboat']) || !is_array($this->extra['drainpipe']['tugboat'])) {
            return;
        }

        // Look for a config override file before we wipe the directory.
        $tugboatConfigOverride = [];
        $tugboatConfigOverridePath = './.tugboat/config.drainpipe-override.yml';
        if (file_exists($tugboatConfigOverridePath)) {
            $tugboatConfigOverride = Yaml::parseFile($tugboatConfigOverridePath);
            $tugboatConfigOverrideFile = file_get_contents($tugboatConfigOverridePath);
        }

        // Wipe the Tugboat directory and define base config.
        $fs->removeDirectory('./.tugboat');
        $binaryInstallerPlugin = new BinaryInstallerPlugin();
        $tugboatConfig = [
            'nodejs_version' => '18',
            'webserver_image' => 'tugboatqa/php-nginx:8.1-fpm',
            'database_type' => 'mariadb',
            'database_version' => '10.11',
            'php_version' => '8.1',
            'sync_command' => 'sync',
            'build_command' => 'build',
            'update_command' => 'drupal:update',
            'init' => [],
            'task_version' => $binaryInstallerPlugin->getBinaryVersion('task'),
            'pantheon' => isset($this->extra['drainpipe']['tugboat']['pantheon']),
            'overrides' => ['php' => ''],
        ];

        // Read DDEV config.
        if (file_exists('./.ddev/config.yaml')) {
            $ddevConfig = Yaml::parseFile('./.ddev/config.yaml');
            $tugboatConfig['database_type'] = $ddevConfig['database']['type'];
            $tugboatConfig['database_version'] = $ddevConfig['database']['version'];
            $tugboatConfig['webserver_image'] = 'tugboatqa/php-nginx:' . $ddevConfig['php_version'] . '-fpm';

            if (!empty($ddevConfig['nodejs_version'])) {
                $tugboatConfig['nodejs_version'] = $ddevConfig['nodejs_version'];
            }
            if (!empty($ddevConfig['webserver_type']) && $ddevConfig['webserver_type'] === 'apache-fpm') {
                $tugboatConfig['webserver_image'] = 'tugboatqa/php:' . $ddevConfig['php_version'] . '-apache';
            }
        }

        // Filter out unsupported config overrides.
        if (!empty($tugboatConfigOverride['php']) && is_array($tugboatConfigOverride['php'])) {
            $tugboatConfigOverride['php'] = array_filter($tugboatConfigOverride['php'],
                function($key) {
                    return in_array($key,
                        ['aliases', 'urls', 'visualdiff', 'screenshot']);
                },
                ARRAY_FILTER_USE_KEY);
            $overrideOutput = [];
            foreach (explode(PHP_EOL,
                Yaml::dump($tugboatConfigOverride['php'], 2, 2)) as $line) {
                $overrideOutput[] = str_repeat(' ', 4) . $line;
            }
            $tugboatConfig['overrides']['php'] = rtrim(implode("\n",
                $overrideOutput));
        }

        // Add Redis service.
        if (file_exists('./.ddev/docker-compose.redis.yaml')) {
            $redisConfig = Yaml::parseFile('.ddev/docker-compose.redis.yaml');
            $redisImage = explode(':',
                $redisConfig['services']['redis']['image']);
            $tugboatConfig['memory_cache_type'] = 'redis';
            $tugboatConfig['memory_cache_version'] = array_pop($redisImage);
        }

        // Add Elasticsearch service.
        if (file_exists('./.ddev/docker-compose.elasticsearch.yaml')) {
            $esConfig = Yaml::parseFile('.ddev/docker-compose.elasticsearch.yaml');
            $esImage = explode(':',
                $esConfig['services']['elasticsearch']['image']);
            $tugboatConfig['search_type'] = 'elasticsearch';
            $tugboatConfig['search_version'] = array_pop($esImage);
        }

        // Add commands to Task.
        if (file_exists('Taskfile.yml')) {
            // Get steps out of the Taskfile.
            $taskfile = Yaml::parseFile('./Taskfile.yml');
            if (isset($taskfile['tasks']['sync:tugboat'])) {
                $tugboatConfig['sync_command'] = 'sync:tugboat';
            }
            if (isset($taskfile['tasks']['build:tugboat'])) {
                $tugboatConfig['build_command'] = 'build:tugboat';
            }
            if (isset($taskfile['tasks']['update'])) {
                $tugboatConfig['update_command'] = 'update';
            }
            if (isset($taskfile['tasks']['update:tugboat'])) {
                $tugboatConfig['update_command'] = 'update:tugboat';
            }
            if (isset($taskfile['tasks']['online:tugboat'])) {
                $tugboatConfig['online_command'] = 'online:tugboat';
            }
            if (isset($taskfile['tasks']['tugboat:php:init'])) {
                $tugboatConfig['init']['php'] = TRUE;
            }
            if (isset($taskfile['tasks']['tugboat:mysql:init'])) {
                $tugboatConfig['init']['mysql'] = TRUE;
            }
            if (isset($taskfile['tasks']['tugboat:redis:init'])) {
                $tugboatConfig['init']['redis'] = TRUE;
            }
        }

        // Write the config.yml and settings.tugboat.php files.
        if (count($tugboatConfig) > 0) {
            $fs->ensureDirectoryExists('./.tugboat');
            $fs->ensureDirectoryExists('./.tugboat/steps');
            $loader = new FilesystemLoader(__DIR__ . '/../scaffold/tugboat');
            $twig = new Environment($loader);
            // Reinstate the override file.
            if (isset($tugboatConfigOverrideFile)) {
                file_put_contents('./.tugboat/config.drainpipe-override.yml',
                    $tugboatConfigOverrideFile);
            }
            file_put_contents('./.tugboat/config.yml',
                $twig->render('config.yml.twig', $tugboatConfig));
            file_put_contents('./.tugboat/steps/1-init.sh',
                $twig->render('steps/1-init.sh.twig', $tugboatConfig));
            file_put_contents('./.tugboat/steps/2-update.sh',
                $twig->render('steps/2-update.sh.twig', $tugboatConfig));
            file_put_contents('./.tugboat/steps/3-build.sh',
                $twig->render('steps/3-build.sh.twig', $tugboatConfig));
            chmod('./.tugboat/steps/1-init.sh', 0755);
            chmod('./.tugboat/steps/2-update.sh', 0755);
            chmod('./.tugboat/steps/3-build.sh', 0755);
            if (!empty($tugboatConfig['online_command'])) {
                file_put_contents('./.tugboat/steps/4-online.sh',
                    $twig->render('steps/4-online.sh.twig',
                        $tugboatConfig));
                chmod('./.tugboat/steps/4-online.sh', 0755);
            }

            if ($tugboatConfig['database_type'] === 'mysql') {
                $fs->ensureDirectoryExists('./.tugboat/scripts');
                $fs->copy("$scaffoldPath/tugboat/scripts/install-mysql-client.sh",
                    './.tugboat/scripts/install-mysql-client.sh');
                chmod('./.tugboat/scripts/install-mysql-client.sh', 0755);
            }

            file_put_contents('./web/sites/default/settings.tugboat.php',
                $twig->render('settings.tugboat.php.twig', $tugboatConfig));
            if (file_exists('./web/sites/default/settings.php')) {
                $settings = file_get_contents('./web/sites/default/settings.php');
                if (strpos($settings, 'settings.tugboat.php') === FALSE) {
                    $include = <<<'EOT'
include __DIR__ . "/settings.tugboat.php";
EOT;
                    file_put_contents('./web/sites/default/settings.php',
                        $include . PHP_EOL,
                        FILE_APPEND);
                }
            }
        }
    }

}
