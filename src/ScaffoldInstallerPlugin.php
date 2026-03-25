<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Json\JsonManipulator;
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
     * Composer autoload-dev configuration.
     *
     * @var array
     */
    protected $autoloadDev;

    /**
     * Whether checkPantheonSystemDrupalIntegrations() has already been called.
     *
     * Prevents duplicate warnings during a single composer run.
     *
     * @var bool
     */
    protected $pantheonIntegrationsChecked = false;

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->config = $composer->getConfig();
        $this->extra = $composer->getPackage()->getExtra();
        $this->autoloadDev = $composer->getPackage()->getDevAutoload();
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
        $this->installNvmRc();
        $this->installTaskfile();
        $this->installGitignore();
        $this->installDdevCommand();
        $this->installHostingProviderSupport($event->getComposer());
        $this->installCICommands($event->getComposer());
        $this->configureRenovateIgnore();
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
        $this->installNvmRc();
        $this->installTaskfile();
        $this->installGitignore();
        $this->installDdevCommand();
        $this->installHostingProviderSupport($event->getComposer());
        $this->installCICommands($event->getComposer());
        $this->configureRenovateIgnore();
        $this->installEnvSupport();
        if ($this->hasPantheonConfigurationFiles()) {
            $this->pantheonSystemDrupalIntegrationsWarning();
        }
    }

    /**
     * Copies .nvmrc from Drainpipe root directory if it doesn't yet exist.
     */
    private function installNvmRc(): void
    {
        $vendor = $this->config->get('vendor-dir');
        $nvmrcPath = $vendor.'/lullabot/drainpipe/.nvmrc';

        if (!file_exists('./.nvmrc')) {
            $this->io->write('<info>Creating initial .nvmrc file...</info>');
            $fs = new Filesystem();
            $fs->copy(
                $nvmrcPath,
                './.nvmrc'
            );
        } else {
            $this->io->write('<info>.nvmrc file already present, skipping...</info>');
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
            $missingIncludes = [];
            foreach ($scaffoldTaskFile['includes'] as $key => $value) {
                if (empty($projectTaskfile['includes'][$key]) || $projectTaskfile['includes'][$key] !== $value) {
                    $missingIncludes[] = $key;
                }
            }
            if (!empty($missingIncludes)) {
                $this->io->warning(
                    'Taskfile.yml has either been customized or requires review. Currently the following includes are missing or outdated:'
                );
                foreach ($missingIncludes as $include) {
                    $value = $scaffoldTaskFile['includes'][$include];
                    $valueString = is_array($value) ? Yaml::dump($value, 0) : $value;
                    $this->io->warning(
                        sprintf(
                            '  - %s: %s',
                            $include,
                            $valueString
                        )
                    );
                }
                $this->io->warning(
                    sprintf(
                        'Compare Taskfile.yml includes in the root of your repository with %s and update as needed.',
                        $taskfilePath
                    )
                );
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
     * Configures Renovate to ignore dependencies managed by Drainpipe.
     */
    private function configureRenovateIgnore(): void
    {
        $renovateConfigPath = './renovate.json';
        $fs = new Filesystem();

        // Create base renovate.json if it doesn't exist
        if (!file_exists($renovateConfigPath)) {
            $this->io->write('<info>Creating initial renovate.json file...</info>');
            $baseConfig = [
                '$schema' => 'https://docs.renovatebot.com/renovate-schema.json',
                'extends' => [
                    'config:recommended'
                ]
            ];
            $this->writeJsonFile($renovateConfigPath, $baseConfig);
        }

        // Read and parse the existing renovate.json
        $renovateConfigJson = file_get_contents($renovateConfigPath);
        $renovateConfig = json_decode($renovateConfigJson, true);

        if ($renovateConfig === null) {
            $this->io->warning('Unable to parse renovate.json - skipping Renovate configuration');
            return;
        }

        // Initialize packageRules if it doesn't exist
        if (!isset($renovateConfig['packageRules']) || !is_array($renovateConfig['packageRules'])) {
            $renovateConfig['packageRules'] = [];
        }

        // Check if the rule already exists
        $ruleExists = false;
        foreach ($renovateConfig['packageRules'] as $rule) {
            if (isset($rule['matchPackageNames'])
                && is_array($rule['matchPackageNames'])
                && in_array('marocchino/sticky-pull-request-comment', $rule['matchPackageNames'])
                && isset($rule['matchManagers'])
                && is_array($rule['matchManagers'])
                && in_array('github-actions', $rule['matchManagers'])
            ) {
                $ruleExists = true;
                break;
            }
        }

        // Add the rule if it doesn't exist
        if (!$ruleExists) {
            $renovateConfig['packageRules'][] = [
                'matchManagers' => ['github-actions'],
                'matchPackageNames' => ['marocchino/sticky-pull-request-comment'],
                'enabled' => false,
                'description' => 'Managed by Drainpipe',
            ];

            $this->writeJsonFile($renovateConfigPath, $renovateConfig);
            $this->io->write('<info>Updated renovate.json to ignore Drainpipe managed dependencies</info>');
        }
    }

    /**
     * Writes JSON data to a file with 2-space indentation.
     *
     * @param string $path
     *   The file path.
     * @param array $data
     *   The data to encode.
     */
    private function writeJsonFile(string $path, array $data): void
    {
        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->io->warning(sprintf('Failed to encode JSON for file "%s": %s', $path, $e->getMessage()));
            return;
        }

        $json = preg_replace_callback('/^ +/m', function ($m) {
            return str_repeat(' ', strlen($m[0]) / 2);
        }, $json);

        if ($json === null) {
            $this->io->warning(sprintf('Failed to process JSON for file "%s" (preg_replace_callback error).', $path));
            return;
        }

        if (file_put_contents($path, $json . PHP_EOL) === false) {
            $this->io->warning(sprintf('Failed to write to file: %s', $path));
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
        $vendorRelative = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $vendor);
        $dotenvFile = "$vendorRelative/lullabot/drainpipe/scaffold/env/dotenv.php";
        $files = $this->autoloadDev['files'] ?? [];

        if (!in_array($dotenvFile, $files)) {
            $files[] = $dotenvFile;
            $composerFile = $this->config->getConfigSource()->getName();
            $manipulator = new JsonManipulator(file_get_contents($composerFile));
            $manipulator->addSubNode('autoload-dev', 'files', $files);
            file_put_contents($composerFile, $manipulator->getContents());
            $this->io->write('<info>🪠 [Drainpipe] Added dotenv.php to autoload-dev files in composer.json</info>');
        }
    }

    /**
     * Installs DDEV custom task command and settings.php file.
     */
    private function installDdevCommand(): void
    {
        if (file_exists('./.ddev/config.yaml')) {
            $vendor = $this->config->get('vendor-dir');
            $ddevScaffoldDir = $vendor . '/lullabot/drainpipe/scaffold/ddev/';
            $fs = new Filesystem();
            $fs->ensureDirectoryExists('./.ddev/commands/web');
            $fs->copy($ddevScaffoldDir . 'task-command.sh', './.ddev/commands/web/task');
            $fs->ensureDirectoryExists('./.ddev/web-build');
            $fs->copy($ddevScaffoldDir . 'drainpipe.Dockerfile', './.ddev/web-build/Dockerfile.drainpipe');
            $fs->copy($vendor . '/lullabot/drainpipe/.taskfile', './.ddev/web-build/taskfile');
            if (file_exists('./web/sites/default/settings.ddev.php')) {
                $settings = file_get_contents('./web/sites/default/settings.ddev.php');
                if (str_contains($settings, 'environment_indicator.indicator')) {
                    $include = <<<'EOT'
// See https://architecture.lullabot.com/adr/20210609-environment-indicator/
$config['environment_indicator.indicator']['name'] = 'Local';
$config['environment_indicator.indicator']['bg_color'] = '#505050';
$config['environment_indicator.indicator']['fg_color'] = '#ffffff';
EOT;
                    file_put_contents('./web/sites/default/settings.ddev.php', $include . PHP_EOL, FILE_APPEND);
                }
            }

            // Configure DDEV to use NodeJS version set in .nvmrc
            $data = Yaml::parseFile('./.ddev/config.yaml', Yaml::PARSE_OBJECT_FOR_MAP);
            $data = json_decode(json_encode($data), true);
            if (!is_array($data)) {
                $data = [];
            }
            $data['nodejs_version'] = 'auto';
            file_put_contents('./.ddev/config.yaml', Yaml::dump($data, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));
        }
    }

    /**
     * Normalizes deprecated hosting provider config values in memory.
     *
     * Detects deprecated string-based provider values in
     * $this->extra['drainpipe']['github'] and
     * $this->extra['drainpipe']['gitlab'], emits deprecation warnings, and
     * rewrites them to the new provider sub-key object form. The user's
     * composer.json is never modified.
     *
     * Non-provider string values (TestStatic, Security, ComposerLockDiff,
     * LockfileDiff, TestFunctional) are not deprecated and remain unchanged.
     */
    private function normalizeHostingProviderConfig(): void
    {
        // Normalize github config.
        if (isset($this->extra['drainpipe']['github']) && is_array($this->extra['drainpipe']['github'])) {
            $github = $this->extra['drainpipe']['github'];

            // Only normalize if it looks like a flat (indexed) array, i.e. the
            // legacy string-list form. An associative array is already the new
            // object form and must not be touched.
            if (array_values($github) === $github) {
                $newGithub = [];
                foreach ($github as $value) {
                    if ($value === 'acquia') {
                        $this->io->warning(
                            "The 'github: [\"acquia\"]' value is deprecated. Use 'github: {\"acquia\": [\"Deploy\"]}' instead."
                        );
                        $newGithub['acquia'] = array_merge($newGithub['acquia'] ?? [], ['Deploy']);
                    }
                    elseif ($value === 'PantheonReviewApps') {
                        $this->io->warning(
                            "The 'github: [\"PantheonReviewApps\"]' value is deprecated. Use 'github: {\"pantheon\": [\"ReviewApps\"]}' instead."
                        );
                        $newGithub['pantheon'] = array_merge($newGithub['pantheon'] ?? [], ['ReviewApps']);
                    }
                    elseif ($value === 'Pantheon') {
                        $this->io->warning(
                            "The 'github: [\"Pantheon\"]' value is deprecated and was previously a no-op. It now scaffolds Pantheon GitHub Actions. Use 'github: {\"pantheon\": [\"Actions\"]}' instead."
                        );
                        $newGithub['pantheon'] = array_merge($newGithub['pantheon'] ?? [], ['Actions']);
                    }
                    else {
                        // Non-provider string values remain as-is (keyed by value).
                        $newGithub[$value] = $value;
                    }
                }
                $this->extra['drainpipe']['github'] = $newGithub;
            }
        }

        // Normalize gitlab config.
        if (isset($this->extra['drainpipe']['gitlab']) && is_array($this->extra['drainpipe']['gitlab'])) {
            $gitlab = $this->extra['drainpipe']['gitlab'];

            // Only normalize if it looks like a flat (indexed) array.
            if (array_values($gitlab) === $gitlab) {
                $newGitlab = [];
                foreach ($gitlab as $value) {
                    if ($value === 'Pantheon') {
                        $this->io->warning(
                            "The 'gitlab: [\"Pantheon\"]' value is deprecated. Use 'gitlab: {\"pantheon\": [\"Deploy\"]}' instead."
                        );
                        $newGitlab['pantheon'] = array_merge($newGitlab['pantheon'] ?? [], ['Deploy']);
                    }
                    elseif ($value === 'PantheonReviewApps') {
                        $this->io->warning(
                            "The 'gitlab: [\"PantheonReviewApps\"]' value is deprecated. Use 'gitlab: {\"pantheon\": [\"ReviewApps\"]}' instead."
                        );
                        $newGitlab['pantheon'] = array_merge($newGitlab['pantheon'] ?? [], ['ReviewApps']);
                    }
                    else {
                        // Non-provider string values remain as-is.
                        $newGitlab[$value] = $value;
                    }
                }
                $this->extra['drainpipe']['gitlab'] = $newGitlab;
            }
        }
    }

    /**
     * Returns true if any Pantheon CI configuration is present.
     *
     * Checks for a non-empty 'pantheon' sub-key under either
     * $this->extra['drainpipe']['github'] or
     * $this->extra['drainpipe']['gitlab'].
     *
     * @return bool
     */
    private function hasAnyPantheonCIConfig(): bool
    {
        $githubPantheon = $this->extra['drainpipe']['github']['pantheon'] ?? [];
        $gitlabPantheon = $this->extra['drainpipe']['gitlab']['pantheon'] ?? [];
        return !empty($githubPantheon) || !empty($gitlabPantheon);
    }

    /**
     * Install hosting provider support.
     *
     * @param Composer $composer The Composer instance.
     */
    private function installHostingProviderSupport(Composer $composer): void
    {
        $this->normalizeHostingProviderConfig();

        $scaffoldPath = $this->config->get('vendor-dir') . '/lullabot/drainpipe/scaffold';

        if (isset($this->extra['drainpipe']['acquia'])) {
            $this->installAcquiaSupport($scaffoldPath);
        }

        if ($this->hasAnyPantheonCIConfig()) {
            $this->installPantheonSupport($scaffoldPath, $composer);
        }
    }

    /**
     * Install Acquia hosting provider support files.
     *
     * Copies CI-agnostic Acquia scaffold files into the project.
     *
     * @param string $scaffoldPath The path to the scaffold files to copy from.
     */
    private function installAcquiaSupport(string $scaffoldPath): void
    {
        $fs = new Filesystem();
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

    /**
     * Install Pantheon hosting provider support files (CI-agnostic).
     *
     * Copies files required for Pantheon deployments regardless of which CI
     * system the project uses.
     *
     * @param string $scaffoldPath The path to the scaffold files to copy from.
     * @param Composer $composer The Composer instance.
     */
    private function installPantheonSupport(string $scaffoldPath, Composer $composer): void
    {
        $fs = new Filesystem();

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

    /**
     * Install CI Commands.
     */
    private function installCICommands(Composer $composer): void
    {
        $scaffoldPath = $this->config->get('vendor-dir') . '/lullabot/drainpipe/scaffold';
        $this->installGitlabCI($scaffoldPath, $composer);
        $this->installGitHubActions($scaffoldPath, $composer);
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

        foreach ($this->extra['drainpipe']['gitlab'] as $key => $value) {
            // Handle the pantheon provider sub-key.
            if ($key === 'pantheon' && is_array($value)) {
                if (in_array('Deploy', $value)) {
                    $file = "gitlab/Pantheon.gitlab-ci.yml";
                    if (file_exists("$scaffoldPath/$file")) {
                        $fs->copy("$scaffoldPath/$file", ".drainpipe/$file");
                        $this->io->write("🪠 [Drainpipe] .drainpipe/$file installed");
                    }
                    else {
                        $this->io->warning("🪠 [Drainpipe] $scaffoldPath/$file does not exist");
                    }
                }
                if (in_array('ReviewApps', $value)) {
                    $file = "gitlab/PantheonReviewApps.gitlab-ci.yml";
                    if (file_exists("$scaffoldPath/$file")) {
                        $fs->copy("$scaffoldPath/$file", ".drainpipe/$file");
                        $this->io->write("🪠 [Drainpipe] .drainpipe/$file installed");
                    }
                    else {
                        $this->io->warning("🪠 [Drainpipe] $scaffoldPath/$file does not exist");
                    }
                }
                continue;
            }

            // Handle non-provider feature values (ComposerLockDiff, etc.).
            // Accept both string values (from flat-array normalization) and boolean true
            // (from the new object form e.g. "ComposerLockDiff": true).
            if (!is_string($value) && $value !== true) {
                continue;
            }
            $gitlab = is_string($value) ? $value : $key;
            $file = "gitlab/$gitlab.gitlab-ci.yml";
            if (file_exists("$scaffoldPath/$file")) {
                $fs->copy("$scaffoldPath/$file", ".drainpipe/$file");
                $this->io->write("🪠 [Drainpipe] .drainpipe/$file installed");
            }
            else {
                $this->io->warning("🪠 [Drainpipe] $scaffoldPath/$file does not exist");
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
        if ($this->pantheonIntegrationsChecked) {
            return;
        }
        $this->pantheonIntegrationsChecked = true;

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
     * Install GitHub Actions configuration if defined in composer.json
     *
     * @param string $scaffoldPath The path to the scaffold files to copy from.
     * @param Composer $composer The Composer instance.
     */
    private function installGitHubActions(string $scaffoldPath, Composer $composer): void {
        $fs = new Filesystem();
        $fs->removeDirectory('./.github/actions/drainpipe');

        if (!isset($this->extra['drainpipe']['github']) || !is_array($this->extra['drainpipe']['github'])) {
            return;
        }

        // Install GitHub actions
        $fs->ensureDirectoryExists('./.github/actions');
        $fs->copy("$scaffoldPath/github/actions/common", './.github/actions/drainpipe');

        // Install base GitHub workflows
        $fs->ensureDirectoryExists('./.github/workflows');
        $fs->copy("$scaffoldPath/github/workflows/TestRenovate.yml", './.github/workflows/TestRenovate.yml');

        // Install configurable GitHub workflows — non-provider string values.
        foreach ($this->extra['drainpipe']['github'] as $key => $value) {
            // Skip provider sub-arrays (arrays); they are handled explicitly below.
            // Accept both string values (from flat-array normalization) and boolean true
            // (from the new object form e.g. "TestStatic": true).
            if (is_array($value)) {
                continue;
            }
            if (!is_string($value) && $value !== true) {
                continue;
            }

            $github = is_string($value) ? $value : $key;
            if ($github === 'ComposerLockDiff') {
                $fs->copy("$scaffoldPath/github/workflows/ComposerLockDiff.yml", './.github/workflows/ComposerLockDiff.yml');
            }
            else if ($github === 'LockfileDiff') {
                $fs->copy("$scaffoldPath/github/workflows/LockfileDiff.yml", './.github/workflows/LockfileDiff.yml');
            }
            else if ($github === 'Security') {
                $fs->copy("$scaffoldPath/github/workflows/Security.yml", './.github/workflows/Security.yml');
            }
            else if ($github === 'TestStatic') {
                $fs->copy("$scaffoldPath/github/workflows/TestStatic.yml", './.github/workflows/TestStatic.yml');
            }
            else if ($github === 'TestFunctional') {
                $fs->copy("$scaffoldPath/github/workflows/TestFunctional.yml", './.github/workflows/TestFunctional.yml');
            }
        }

        // Handle Pantheon GitHub Actions.
        $pantheonOptions = $this->extra['drainpipe']['github']['pantheon'] ?? [];
        if (!empty($pantheonOptions)) {
            // Actions are needed for both "Actions" and "ReviewApps".
            if (in_array('Actions', $pantheonOptions) || in_array('ReviewApps', $pantheonOptions)) {
                $fs->ensureDirectoryExists('./.github/actions/drainpipe/pantheon');
                $fs->copy("$scaffoldPath/github/actions/pantheon", './.github/actions/drainpipe/pantheon');
            }
            if (in_array('ReviewApps', $pantheonOptions)) {
                if (file_exists('./.ddev/config.yaml')) {
                    $fs->copy("$scaffoldPath/github/workflows/PantheonReviewAppsDDEV.yml", './.github/workflows/PantheonReviewApps.yml');
                }
                else {
                    $fs->copy("$scaffoldPath/github/workflows/PantheonReviewApps.yml", './.github/workflows/PantheonReviewApps.yml');
                }
            }
            $this->checkPantheonSystemDrupalIntegrations($composer);
        }

        // Handle Acquia GitHub Actions.
        $acquiaOptions = $this->extra['drainpipe']['github']['acquia'] ?? [];
        if (!empty($acquiaOptions)) {
            if (in_array('Deploy', $acquiaOptions)) {
                $fs->ensureDirectoryExists('./.github/actions/drainpipe/acquia');
                $fs->copy("$scaffoldPath/github/actions/acquia", './.github/actions/drainpipe/acquia');
                $fs->copy("$scaffoldPath/github/workflows/AcquiaDeploy.yml", './.github/workflows/AcquiaDeploy.yml');
            }
        }
    }
}
