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
        $this->installHostingProviderSupport();
        $this->installCICommands($event->getComposer());
        $this->installEnvSupport();
        if ($this->hasPantheonConfigurationFiles()) {
            $this->checkPantheonSystemDrupalIntegrations($event->getComposer());
        }
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
        $this->installHostingProviderSupport();
        $this->installCICommands($event->getComposer());
        $this->installEnvSupport();
        if ($this->hasPantheonConfigurationFiles()) {
            $this->pantheonSystemDrupalIntegrationsWarning();
        }
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
     * Install hosting provider support.
     */
    private function installHostingProviderSupport(): void
    {
        $fs = new Filesystem();
        $scaffoldPath = $this->config->get('vendor-dir') . '/lullabot/drainpipe/scaffold';
        if (isset($this->extra['drainpipe']['acquia'])) {
            if (!file_exists('.drainpipeignore')) {
                $fs->copy("$scaffoldPath/acquia/.drainpipeignore", '.drainpipeignore');
            }
            if (!empty($this->extra['drainpipe']['acquia']['settings'])) {
                // settings.acquia.php
                if (!file_exists('./web/sites/default/settings.acquia.php')) {
                    $fs->copy("$scaffoldPath/acquia/settings.acquia.php", './web/sites/default/settings.acquia.php');
                }
                if (file_exists('./web/sites/default/settings.php')) {
                    $settings = file_get_contents('./web/sites/default/settings.php');
                    if (strpos($settings, 'settings.acquia.php') === false) {
                        $include = <<<'EOT'
include __DIR__ . "/settings.acquia.php";
EOT;
                        file_put_contents('./web/sites/default/settings.php', $include . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        }
    }

    /**
     * Install CI Commands.
     */
    private function installCICommands(Composer $composer): void
    {
        $scaffoldPath = $this->config->get('vendor-dir') . '/lullabot/drainpipe/scaffold';
        $this->installGitlabCI($scaffoldPath, $composer);
        $this->installGitHubActions($scaffoldPath, $composer);
        $this->installTugboat($scaffoldPath);
    }

    /**
     * Install GitLab CI configuration if defined in composer.json
     *
     * @param string $scaffoldPath The path to the scaffold files to copy from.
     */
    private function installGitlabCI(string $scaffoldPath, Composer $composer): void {
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

        if (isset($this->extra['drainpipe']['testing']) && is_array($this->extra['drainpipe']['testing'])) {
            foreach ($this->extra['drainpipe']['testing'] as $framework) {
                $fs->copy("$scaffoldPath/testing/$framework.gitlab-ci.yml", ".drainpipe/gitlab/$framework.gitlab-ci.yml");
                $this->io->write("🪠 [Drainpipe] .drainpipe/gitlab/$framework.gitlab-ci.yml installed");
            }
        }

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

                $this->checkPantheonSystemDrupalIntegrations($composer);
            }
        }
        if (!file_exists('./.gitlab-ci.yml')) {
            $fs->copy("$scaffoldPath/gitlab/gitlab-ci.example.yml", './.gitlab-ci.yml');
        }
    }

    /**
     * Check if package pantheon-systems/drupal-integrations is installed.
     * If not installed, recommend the user to install it.
     *
     * @param \Composer\Composer $composer
     *   The Composer instance.
     *
     * @return void
     *   No return value.
     */
    private function checkPantheonSystemDrupalIntegrations(Composer $composer): void {
        $repositoryManager = $composer->getRepositoryManager();
        $localRepository = $repositoryManager->getLocalRepository();
        $package = $localRepository->findPackage('pantheon-systems/drupal-integrations', '*');
        if ($package) {
            return; // Found the package, no warning needed.
        }
        $this->pantheonSystemDrupalIntegrationsWarning();
    }

    /**
     * Check for common Pantheon configuration files.
     *
     * @return bool
     *   True if the site uses Pantheon, false otherwise.
     */
    private function hasPantheonConfigurationFiles(): bool
    {
        return is_file('./pantheon.yml')
            || is_file('./pantheon.upstream.yml');
    }

    /**
     * Display a warning about the pantheon-systems/drupal-integrations package.
     */
    private function pantheonSystemDrupalIntegrationsWarning(): void {
        $this->io->warning("🪠 [Drainpipe] For Pantheon sites, we strongly recommend installing the pantheon-systems/drupal-integrations package. Essential Pantheon functionality depends on this package.");
    }

    /**
     * Install GitLab CI configuration if defined in composer.json
     *
     * @param string $scaffoldPath The path to the scaffold files to copy from.
     */
    private function installGitHubActions(string $scaffoldPath, Composer $composer): void {
        $fs = new Filesystem();
        $fs->removeDirectory('./.github/actions/drainpipe');

        if (!isset($this->extra['drainpipe']['github']) || !is_array($this->extra['drainpipe']['github'])) {
            return;
        }

        $fs->ensureDirectoryExists('./.github/actions');
        $fs->copy("$scaffoldPath/github/actions/common", './.github/actions/drainpipe');
        foreach ($this->extra['drainpipe']['github'] as $github) {
            if ($github === 'PantheonReviewApps' || $github === 'PantheonReviewAppsManual') {
                $fs->ensureDirectoryExists('./.github/actions/drainpipe/pantheon');
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/actions/pantheon", './.github/actions/drainpipe/pantheon');
                $pantheon_review_apps = ($github === 'PantheonReviewApps') ? 'PantheonReviewApps' : 'PantheonReviewAppsManual';
                if (file_exists('./.ddev/config.yaml')) {
                    $pantheon_review_apps_ddev = $pantheon_review_apps . 'DDEV';
                    $fs->copy("$scaffoldPath/github/workflows/$pantheon_review_apps_ddev.yml", './.github/workflows/PantheonReviewApps.yml');
                }
                else {
                    $fs->copy("$scaffoldPath/github/workflows/$pantheon_review_apps.yml", './.github/workflows/PantheonReviewApps.yml');
                }
                $this->checkPantheonSystemDrupalIntegrations($composer);
            }
            else if ($github === 'acquia') {
                $fs->ensureDirectoryExists('./.github/actions/drainpipe/acquia');
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/actions/acquia", './.github/actions/drainpipe/acquia');
                $fs->copy("$scaffoldPath/github/workflows/AcquiaDeploy.yml", './.github/workflows/AcquiaDeploy.yml');
            }
            else if ($github === 'ComposerLockDiff') {
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/workflows/ComposerLockDiff.yml", './.github/workflows/ComposerLockDiff.yml');
            }
            else if ($github === 'Security') {
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/workflows/Security.yml", './.github/workflows/Security.yml');
            } 
            else if ($github === 'Security:Zizmor') {
                $fs->ensureDirectoryExists('./.github/workflows');
                $fs->copy("$scaffoldPath/github/workflows/Security.yml", './.github/workflows/Security.yml');
                $fs->copy("$scaffoldPath/github/workflows/TestZizmor.yml", './.github/workflows/TestZizmor.yml');
                $fs->copy("$scaffoldPath/zizmor.yml", './zizmor.yml');
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

        if (isset($this->extra['drainpipe']['acquia'])) {
            // TODO: Add Acquia related GitHub Actions.
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
        $filesToRemove = [
            './.tugboat/config.drainpipe-override.yml',
            './.tugboat/config.yml',
            './.tugboat/steps/1-init.sh',
            './.tugboat/steps/2-update.sh',
            './.tugboat/steps/3-build.sh',
            './.tugboat/steps/4-online.sh',
            './.tugboat/scripts/install-mysql-client.sh',
        ];
        foreach ($filesToRemove as $file) {
            if (file_exists($file)) {
                $fs->remove($file);
            }
        }
        $binaryInstallerPlugin = new BinaryInstallerPlugin();
        $tugboatConfig = [
            'nodejs_version' => '18',
            'webserver_image' => 'tugboatqa/php-nginx:8.1-fpm-bookworm',
            'database_type' => 'mariadb',
            'database_version' => '10.11',
            'php_version' => '8.1',
            'sync_command' => 'sync',
            'build_command' => 'build',
            'update_command' => 'drupal:update',
            'init' => [],
            'task_version' => $binaryInstallerPlugin->getBinaryVersion('task'),
            'pantheon' => isset($this->extra['drainpipe']['tugboat']['pantheon']),
            'overrides' => ['php' => '', 'solr' => ''],
        ];

        // Read DDEV config.
        if (file_exists('./.ddev/config.yaml')) {
            $ddevConfig = Yaml::parseFile('./.ddev/config.yaml');
            $tugboatConfig['database_type'] = $ddevConfig['database']['type'];
            $tugboatConfig['database_version'] = $ddevConfig['database']['version'];
            $tugboatConfig['webserver_image'] = 'tugboatqa/php-nginx:' . $ddevConfig['php_version'] . '-fpm-bookworm';

            if (!empty($ddevConfig['nodejs_version'])) {
                $tugboatConfig['nodejs_version'] = $ddevConfig['nodejs_version'];
            }
            if (!empty($ddevConfig['webserver_type']) && $ddevConfig['webserver_type'] === 'apache-fpm') {
                $tugboatConfig['webserver_image'] = 'tugboatqa/php:' . $ddevConfig['php_version'] . '-apache-bookworm';
            }
        }

        // Process PHP config overrides.
        $tugboatConfig['overrides']['php'] = $this->processTugboatOverride(
            $tugboatConfigOverride,
            'php',
            ['aliases', 'urls', 'visualdiff', 'screenshot']
        );

        // Extract Solr image configuration before filtering for service detection
        $solrOverrideImage = null;
        if (!empty($tugboatConfigOverride['solr']) && is_array($tugboatConfigOverride['solr'])) {
            $solrOverrideImage = $tugboatConfigOverride['solr']['image'] ?? null;
        }

        // Process Solr config overrides.
        $tugboatConfig['overrides']['solr'] = $this->processTugboatOverride(
            $tugboatConfigOverride,
            'solr',
            ['commands', 'depends', 'aliases', 'urls', 'volumes', 'environment', 'checkout']
        );

        // Add Redis service.
        if (file_exists('./.ddev/docker-compose.redis.yaml')) {
            $redisConfig = Yaml::parseFile('.ddev/docker-compose.redis.yaml');
            $image = $redisConfig['services']['redis']['image'] ?? '';

            $version = self::extractRedisImageVersion($image);
            $tugboatConfig['memory_cache_type'] = 'redis';
            $tugboatConfig['memory_cache_version'] = $version;
        }

        // Add search service (mutually exclusive).
        // Priority: Solr override -> Solr DDEV -> Elasticsearch DDEV
        if (!empty($solrOverrideImage)) {
            $solrImage = explode(':', $solrOverrideImage);
            $tugboatConfig['search_type'] = 'solr';
            $tugboatConfig['search_version'] = array_pop($solrImage);
        }
        // Fall back to DDEV docker-compose configuration if not specified in override
        elseif (file_exists('./.ddev/docker-compose.solr.yaml')) {
            $solrConfig = Yaml::parseFile('.ddev/docker-compose.solr.yaml');
            $solrImage = explode(':',
                $solrConfig['services']['solr']['image']);
            $tugboatConfig['search_type'] = 'solr';
            $tugboatConfig['search_version'] = array_pop($solrImage);
        }
        elseif (file_exists('./.ddev/docker-compose.elasticsearch.yaml')) {
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
            if (isset($taskfile['tasks']['tugboat:solr:init'])) {
                $tugboatConfig['init']['solr'] = TRUE;
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

    /**
     * Processes Tugboat service configuration overrides.
     *
     * @param array $configOverride The configuration override array.
     * @param string $service The service name (e.g., 'php', 'solr').
     * @param array $allowedKeys The keys allowed for this service override.
     * @return string The formatted YAML string for the overrides.
     */
    private function processTugboatOverride(array $configOverride, string $service, array $allowedKeys): string
    {
        if (empty($configOverride[$service]) || !is_array($configOverride[$service])) {
            return '';
        }

        $filteredOverride = array_filter($configOverride[$service],
            function($key) use ($allowedKeys) {
                return in_array($key, $allowedKeys);
            },
            ARRAY_FILTER_USE_KEY);

        $overrideOutput = [];
        foreach (explode(PHP_EOL, Yaml::dump($filteredOverride, 2, 2)) as $line) {
            $overrideOutput[] = str_repeat(' ', 4) . $line;
        }

        return rtrim(implode("\n", $overrideOutput));
    }

    /**
     * Extracts the Redis version tag from a Docker image string.
     *
     * Supports formats using environment variable fallbacks such as:
     *   - ${REDIS_DOCKER_IMAGE:-redis:7}
     *   - redis:${REDIS_TAG:-6-bullseye}
     * As well as direct image values like:
     *   - redis:7-alpine
     *   - tugboatqa/redis:bookworm
     *
     * @param string $image The raw or interpolated image string from docker-compose.redis.yaml.
     *
     * @return string The extracted Redis version/tag (e.g. "7", "6-bullseye", "bookworm").
     *
     * @throws \RuntimeException If the version tag cannot be extracted.
     */
    public static function extractRedisImageVersion(string $image): string
    {
        // Normalize image from possible environment syntax.
        // Example: $image = '${REDIS_DOCKER_IMAGE:-redis:7}'.
        if (preg_match('/^\$\{[^:}]+:-(.+)\}$/', $image, $matches)) {
            $image = $matches[1];
        // Example: $image = 'redis:${REDIS_TAG:-6-bullseye}'.
        } elseif (preg_match('/^([^:]+):\$\{[^:}]+:-(.+)\}$/', $image, $matches)) {
            $image = "{$matches[1]}:{$matches[2]}";
        }

        // Extract the version/tag from the image string.
        // Example: $image = 'redis:6-bullseye' → $version = '6-bullseye'
        if (!preg_match('/:([a-zA-Z0-9._-]+)$/', $image, $versionMatch)) {
            throw new \RuntimeException("Unable to extract Redis version from image: {$image}");
        }

        return $versionMatch[1];
    }

}
