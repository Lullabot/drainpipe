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
use Twig\TwigFilter;

/**
 * Composer plugin for Tugboat integration.
 *
 * This plugin automatically generates Tugboat configuration by extracting
 * service information from DDEV configuration files.
 */
class TugboatConfigPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Supported DDEV add-on services.
     *
     * Maps DDEV docker-compose file names to service names.
     */
    private const DDEV_ADDON_SERVICES = [
        '.ddev/docker-compose.redis.yaml' => 'redis',
        '.ddev/docker-compose.memcached.yaml' => 'memcached',
        '.ddev/docker-compose.solr.yaml' => 'solr',
        '.ddev/docker-compose.elasticsearch.yaml' => 'elasticsearch',
    ];

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
            ScriptEvents::POST_INSTALL_CMD => 'onPostCmd',
            ScriptEvents::POST_UPDATE_CMD => 'onPostCmd',
        ];
    }

    /**
     * Handle post install/update command events.
     *
     * @param Event $event the event to handle
     */
    public function onPostCmd(Event $event)
    {
        $this->generateTugboatConfiguration();
    }

    /**
     * Generate Tugboat configuration from DDEV settings.
     */
    private function generateTugboatConfiguration(): void
    {
        // Check if Tugboat integration is enabled
        if (!isset($this->extra['drainpipe']['tugboat']) || !is_array($this->extra['drainpipe']['tugboat'])) {
            return;
        }

        // Check if DDEV configuration exists
        if (!file_exists('./.ddev/config.yaml')) {
            $this->io->warning('🪠 [Drainpipe] DDEV configuration not found. Generating settings.php file, assuming Mariadb.');
            $this->generateSettingsFile(['database' => ['type' => 'mariadb']]);
            return;
        }

        $this->io->write('🪠 [Drainpipe] Generating Tugboat configuration from DDEV...');

        // Parse base services from DDEV configuration
        $services = $this->parseBaseConfiguration();

        // Detect and merge additional services from DDEV add-ons
        $addons = $this->detectDdevAddons();
        $services = array_merge($services, $addons);

        // Webserver depends by default on all other services
        foreach ($services as $name => $config) {
            if ($name === 'php') {
                continue;
            }
            $services['php']['depends'][] = $config['type'];
        }

        // Parse and apply custom commands from Taskfile
        $taskCommands = $this->parseTaskfileCommands();
        foreach ($taskCommands as $service => $commands) {
            if (array_key_exists($service, $services)) {
                $services[$service]['commands'] = $commands;
            }
        }

        // Load and apply override configuration
        $overrideConfig = $this->loadOverrideConfig();
        $services = $this->applyOverrides($services, $overrideConfig);

        // Check for Pantheon integration
        $isPantheon = isset($this->extra['drainpipe']['tugboat']['pantheon']) &&
                      $this->extra['drainpipe']['tugboat']['pantheon'];


        // Generate Tugboat config.yml
        $this->generateConfigYml($services, $isPantheon);

        // Generate settings.tugboat.php
        $this->generateSettingsFile($services);

        $this->io->write('🪠 [Drainpipe] Tugboat configuration generated successfully!');
    }

    /**
     * Load configuration overrides from .tugboat/config.drainpipe-override.yml
     *
     * @return array The override configuration, or empty array if file doesn't exist
     */
    private function loadOverrideConfig(): array
    {
        $overridePath = './.tugboat/config.drainpipe-override.yml';

        if (!file_exists($overridePath)) {
            return [];
        }

        return Yaml::parseFile($overridePath);
    }

    /**
     * Install custom script.
     */
    private function installScript(string $script): void
    {
        $fs = new Filesystem();
        $vendor = $this->config->get('vendor-dir');
        $scaffoldPath = $vendor . '/lullabot/drainpipe/scaffold';

        $fs->ensureDirectoryExists('./.tugboat/scripts');
        $fs->copy(
            "$scaffoldPath/tugboat/scripts/$script",
            "./.tugboat/scripts/$script"
        );
        chmod("./.tugboat/scripts/$script", 0755);
    }

    /**
     * Parse base services from DDEV main configuration file.
     *
     * @return array Array of core services (webserver, database)
     */
    private function parseBaseConfiguration(): array
    {
        $ddevConfig = Yaml::parseFile('./.ddev/config.yaml');
        $services = [];

        // Webserver configuration
        $phpVersion = $ddevConfig['php_version'];
        $webserverType = $ddevConfig['webserver_type'];

        $services['php'] = [
            'type' => $webserverType,
            'php_version' => $phpVersion,
            'image' => $this->mapToTugboatImage('php', $phpVersion, $webserverType),
            'default' => true,
            'depends' => [],
        ];

        // Database configuration
        $dbType = $ddevConfig['database']['type'];
        $dbVersion = $ddevConfig['database']['version'];

        $services['database'] = [
            'type' => $dbType,
            'version' => $dbVersion,
            'image' => $this->mapToTugboatImage('database', $dbVersion, $dbType),
        ];

        // Copy MySQL client script if needed
        if ($dbType === 'mysql') {
            $this->installScript('install-mysql-client.sh');
        }

        return $services;
    }

    /**
     * Detect additional services from DDEV docker-compose add-on files.
     *
     * @return array Array of detected services
     */
    private function detectDdevAddons(): array
    {
        $addons = [];

        foreach (self::DDEV_ADDON_SERVICES as $file => $serviceName) {
            if (file_exists($file)) {
                $config = Yaml::parseFile($file);

                $name = $serviceName;
                switch ($serviceName) {
                    case 'memcached':
                    case 'redis':
                        $name = 'memory_cache';
                        break;
                    case 'solr':
                    case 'elasticsearch':
                        $name = 'search';
                        break;
                }

                // Get the image from the service definition
                $image = $config['services'][$serviceName]['image'] ?? null;

                if ($image) {
                    $version = $this->extractVersionFromImage($image);

                    $addons[$name] = [
                        'type' => $serviceName,
                        'version' => $version,
                        'image' => $this->mapToTugboatImage($serviceName, $version),
                    ];

                    $this->io->write("🪠 [Drainpipe] Detected $serviceName:$version from DDEV");
                }
            }
        }

        return $addons;
    }

    /**
     * Extract version from Docker image string.
     *
     * Handles various formats:
     * - redis:7
     * - redis:7-alpine
     * - tugboatqa/redis:7
     * - ${VAR:-redis:7}
     * - redis:${TAG:-7}
     *
     * @param string $image The Docker image string
     * @return string The extracted version
     */
    private function extractVersionFromImage(string $image): string
    {
        // Handle environment variable syntax with fallback
        // Example: ${REDIS_DOCKER_IMAGE:-redis:7}
        if (preg_match('/^\$\{[^:}]+:-(.+)\}$/', $image, $matches)) {
            $image = $matches[1];
        }
        // Example: redis:${REDIS_TAG:-7-alpine}
        elseif (preg_match('/^([^:]+):\$\{[^:}]+:-(.+)\}$/', $image, $matches)) {
            return $matches[2];
        }

        // Extract version from image:version format
        if (preg_match('/:([a-zA-Z0-9._-]+)$/', $image, $matches)) {
            return $matches[1];
        }

        // Fallback to 'latest' if we can't extract version
        $this->io->warning("🪠 [Drainpipe] Unable to extract version from image: $image. Using 'latest'.");
        return 'latest';
    }

    /**
     * Map DDEV services to Tugboat Docker images.
     *
     * @param string $serviceType The service type (php, database, redis, etc.)
     * @param string $version The version number
     * @param string|null $subtype Additional type info (e.g., nginx-fpm vs apache-fpm)
     * @return string The Tugboat Docker image string
     */
    private function mapToTugboatImage(string $serviceType, string $version, ?string $subtype = null): string
    {
        switch ($serviceType) {
            case 'php':
                if ($subtype === 'apache-fpm') {
                    return "tugboatqa/php:$version-apache-bookworm";
                }
                return "tugboatqa/php-nginx:$version-fpm-bookworm";

            case 'database':
                // $subtype is the database type (mariadb, mysql, postgres)
                $dbType = $subtype ?? 'mariadb';

                // When DDEV uses MySQL, allow using Percona image in Tugboat
                $isPercona = isset($this->extra['drainpipe']['tugboat']['percona']) &&
                          $this->extra['drainpipe']['tugboat']['percona'];
                if ($subtype === 'mysql' && $isPercona) {
                    $dbType = 'percona';
                }

                return "tugboatqa/$dbType:$version";

            case 'redis':
                return "tugboatqa/redis:$version";

            case 'memcached':
                return "tugboatqa/memcached:$version";

            case 'solr':
                return "tugboatqa/solr:$version";

            case 'elasticsearch':
                return "tugboatqa/elasticsearch:$version";

            default:
                $this->io->warning("🪠 [Drainpipe] Unknown service type: $serviceType");
                return "tugboatqa/$serviceType:$version";
        }
    }

    /**
     * Parse Taskfile.yml for custom Tugboat commands.
     *
     * @return array Array of command names for different phases
     */
    private function parseTaskfileCommands(): array
    {
        $commands = [];

        if (!file_exists('./Taskfile.yml')) {
            return $commands;
        }

        $taskfile = Yaml::parseFile('./Taskfile.yml');
        $tasks = $taskfile['tasks'] ?? [];

        // Services that can have a custom init task
        // Search services are not allowed to add init commands like this, and
        // instead should override 'commands' in 'config.drainpipe-overrides.yml'
        $initTasks = [
            'php',
            'mysql',
            'mariadb',
            'postgres',
            'redis',
            'memcached',
        ];

        // Check for service-specific init tasks
        $shouldScaffoldInitScript = false;
        foreach ($initTasks as $service) {
            if (isset($tasks["tugboat:$service:init"])) {
                if (in_array($service, ['mariadb', 'mysql', 'postgres'])) {
                    $commands['database']['init'] = true;
                }
                elseif (in_array($service, ['redis', 'memcached'])) {
                    $commands['memory_cache']['init'] = true;
                }
                else {
                    $commands[$service]['init'] = true;
                }
                $shouldScaffoldInitScript = true;
            }
        }

        if ($shouldScaffoldInitScript) {
            $this->installScript('custom-init-command.sh');
        }

        // Check for Tugboat-specific tasks for main service (php)
        $commands['php']['update'] = 'build:drupal';
        if (isset($tasks['update'])) {
            $commands['php']['update'] = 'update';
        }
        if (isset($tasks['update:tugboat'])) {
            $commands['php']['update'] = 'update:tugboat';
        }
        if (isset($tasks['sync:tugboat'])) {
            $commands['php']['sync'] = 'sync:tugboat';
        }
        if (isset($tasks['build:tugboat'])) {
            $commands['php']['build'] = 'build:tugboat';
        }
        if (isset($tasks['online:tugboat'])) {
            $commands['php']['online'] = 'online:tugboat';
        }

        return $commands;
    }

    /**
     * Apply configuration overrides to services.
     *
     * Only allows overriding specific presentational properties.
     * Does not allow overriding: commands, volumes, environment, image, or depends.
     *
     * @param array $services The services configuration
     * @param array $overrideConfig The override configuration
     * @return array Services with overrides applied
     */
    private function applyOverrides(array $services, array $overrideConfig): array
    {
        if (empty($overrideConfig)) {
            return $services;
        }

        // Define allowed override keys per service
        // Only presentational properties are allowed
        $allowedOverrides = [
            'php' => ['aliases', 'lighthouse', 'visualdiff', 'screenshot', 'urls'],
            'solr' => ['checkout', 'commands', 'depends', 'volumes', 'environment', 'aliases', 'urls'],
        ];

        foreach ($overrideConfig as $serviceName => $overrides) {
            if (!is_array($overrides)) {
                continue;
            }

            // Only php and solr services allow overrides
            if (!isset($allowedOverrides[$serviceName])) {
                $this->io->warning("🪠 [Drainpipe] Service '$serviceName' does not support overrides. Only 'php' and 'solr' services can be overridden.");
                continue;
            }

            // Map service name to actual service key in $services array
            // php -> php (no change)
            // solr -> search (since solr is detected as 'search' service)
            $targetService = $serviceName === 'solr' ? 'search' : $serviceName;

            // Only allow overrides for services that exist in DDEV configuration
            if (!isset($services[$targetService])) {
                $this->io->warning("🪠 [Drainpipe] Cannot override '$serviceName': service not detected in DDEV configuration");
                continue;
            }

            // Get allowed keys for this service
            $allowed = $allowedOverrides[$serviceName];

            // Filter and apply overrides
            foreach ($overrides as $key => $value) {
                if (in_array($key, $allowed)) {
                    $services[$targetService]['overrides'][$key] = $value;
                    $this->io->write("🪠 [Drainpipe] Applied override: $serviceName.$key");
                } else {
                    $this->io->warning("🪠 [Drainpipe] Ignoring disallowed override: $serviceName.$key");
                }
            }
        }

        return $services;
    }

    private function initTwigLoader(): Environment
    {
        $vendor = $this->config->get('vendor-dir');
        $scaffoldPath = $vendor . '/lullabot/drainpipe/scaffold/tugboat/templates';
        $loader = new FilesystemLoader();

        // Project overrides first so they take precedence.
        if (is_dir('./.tugboat/drainpipe-templates')) {
            $loader->addPath('./.tugboat/drainpipe-templates');
            $this->io->write('🪠 [Drainpipe] Using custom templates from .tugboat/drainpipe-templates/');
        }

        $loader->addPath($scaffoldPath);

        $twig = new Environment($loader);

        $twig->addFilter(new TwigFilter('yaml_encode', function ($value) {
            return Yaml::dump($value, 4, 2);
        }));

        $twig->addFilter(new TwigFilter('indent', function (string $text, int $spaces = 4): string {
            $pad = str_repeat(' ', max(0, $spaces));
            return preg_replace('/^/m', $pad, $text);
        }));

        $twig->addFilter(new TwigFilter('no_comments', function (string $text): string {
            return preg_replace('/^[ \t]*#.*$\n?/m', '', $text);
        }));

        return $twig;
    }

    /**
     * Generate the Tugboat config.yml file using Twig templates.
     *
     * @param array $services The services configuration
     * @param bool $isPantheon Whether Pantheon integration is enabled
     */
    private function generateConfigYml(array $services, bool $isPantheon): void
    {
        $fs = new Filesystem();
        $fs->ensureDirectoryExists('./.tugboat');

        // Load Twig templates
        $twig = $this->initTwigLoader();

        // Prepare template variables
        $templateVars = [
            'services' => $services,
            'pantheon' => $isPantheon,
        ];

        // Generate config.yml
        $configYml = $twig->render('config.yml.twig', $templateVars);
        file_put_contents('./.tugboat/config.yml', $configYml);

        $this->io->write('🪠 [Drainpipe] Generated .tugboat/config.yml');
    }

    /**
     * Generate settings.tugboat.php file.
     *
     * @param array $services The services configuration
     */
    private function generateSettingsFile(array $services): void
    {
        $vendor = $this->config->get('vendor-dir');
        $scaffoldPath = $vendor . '/lullabot/drainpipe/scaffold/tugboat/templates';
        $loader = new FilesystemLoader($scaffoldPath);
        $twig = new Environment($loader);
        $twig = $this->initTwigLoader();

        $templateVars = [
            'services' => $services,
        ];

        $settingsPhp = $twig->render('settings.tugboat.php.twig', $templateVars);
        file_put_contents('./web/sites/default/settings.tugboat.php', $settingsPhp);

        // Add include to settings.php if not already present
        if (file_exists('./web/sites/default/settings.php')) {
            $settings = file_get_contents('./web/sites/default/settings.php');
            if (strpos($settings, 'settings.tugboat.php') === false) {
                $include = <<<'EOT'
include __DIR__ . "/settings.tugboat.php";
EOT;
                file_put_contents(
                    './web/sites/default/settings.php',
                    $include . PHP_EOL,
                    FILE_APPEND
                );
            }
        }

        $this->io->write('🪠 [Drainpipe] Generated web/sites/default/settings.tugboat.php');
    }
}
