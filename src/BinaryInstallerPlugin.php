<?php

namespace Lullabot\Drainpipe;

use Composer\Cache;
use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

class BinaryInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * The binaries to manage and download.
     *
     * @var string[]
     */
    protected $binaries = [
        'local-php-security-checker' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_amd64', 'sha' => 'e5b12488ca78bc07c149e9352278bf10667b88a8461caac10154f9a6f5476369'],
                    '386' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_386', 'sha' => 'ed395fbb6441bd7b21020a08c05919600419b47709a8e5c9679e3ee0a2952d05'],
                    'arm64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_arm64', 'sha' => 'd2c0bd8b3f6059e55a55ece56461d04728eeaad73ece902a8e8078d287721eb3'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_darwin_amd64', 'sha' => '8c363d605116e84cf9ac28ac3838ca7979f7306916049bdb3f0f1fe2a8764d82'],
                    // The Macbook M1 will run the amd64 binary in emulation mode.
                    'arm64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_darwin_amd64', 'sha' => '8c363d605116e84cf9ac28ac3838ca7979f7306916049bdb3f0f1fe2a8764d82'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_windows_amd64.exe', 'sha' => '6dd4d20483b263fd6ad9464976f8bb8b4467c5e7e8b3b4630156a654ce8dbe4d'],
                    '386' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_windows_386.exe', 'sha' => '6fe96de992da1579c30e7b3da3c90d389db3cb09a689c8b05b4e5cc0d8ae97bf5c2316ff3321cfeed930a9fc80ba5578f9cf9c45'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '1.0.0',
        ],
        'task' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_linux_amd64.tar.gz', 'sha' => '1492e0d185eb7e8547136c8813e51189f59c1d9e21e5395ede9b9a40d55c796e'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_linux_arm.tar.gz', 'sha' => '5d96701230abe2ce44ab416674431953e177ad949f8be388646a562876fe7921'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_linux_386.tar.gz', 'sha' => '9a2fe84cfb7a0007360116b69598ba7b1b63ead0ec3ced5f7330864705977f20'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_linux_arm64.tar.gz', 'sha' => 'd1d56f3fbf54965c0ac5366f8679745f315ca2d4c56f962e73ee8f48bea311ee'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_darwin_amd64.tar.gz', 'sha' => 'a82117a3b560f35be9d5d34a1eb6707f1cdde1e2ab9ed22cd5a72bd97682a83e'],
                    // The Macbook M1 will run the amd64 binary in emulation mode.
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_darwin_amd64.tar.gz', 'sha' => 'a82117a3b560f35be9d5d34a1eb6707f1cdde1e2ab9ed22cd5a72bd97682a83e'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_windows_amd64.zip', 'sha' => '68633544333abe848f1244c90d2178e7d86d59e8f9c15b8ad2e288266949988a'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_windows_arm.zip', 'sha' => '4001a78451caee56f3146bfce50a2205942963927fe1f570bbd0d7a58fb4551a'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.4.3/task_windows_386.zip', 'sha' => '72602187b4ddcd89c6c91f862cd14535ae8ee137f07108f4755accf764ba3100'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.4.3',
        ],
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
     * Composer cache.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * The platform the installer is being run on.
     *
     * @var string linux, windows, or darwin
     */
    protected $platform;

    /**
     * System architecture.
     *
     * @var string 386, amd64, or arm64
     */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->config = $composer->getConfig();
        $this->platform = strtolower(\PHP_OS_FAMILY);
        $uname = strtolower(php_uname('v'));
        if (false !== strpos($uname, 'arm64')) {
            $this->processor = 'arm64';
        } elseif (\PHP_INT_SIZE === 8) {
            $this->processor = 'amd64';
        } else {
            $this->processor = '386';
        }

        $this->cache = new Cache(
            $this->io,
            implode(\DIRECTORY_SEPARATOR, [
                $this->config->get('cache-dir'),
                'files',
                'lullabot',
                'drainpipe-installer',
                'bin',
            ])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        $fs = new Filesystem();
        foreach ($this->binaries as $binary => $info) {
            $fs->remove($this->config->get('bin-dir').\DIRECTORY_SEPARATOR.$binary);
        }
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
        $this->installBinaries($event);
    }

    /**
     * Handle post update command events.
     *
     * @param event $event The event to handle
     */
    public function onPostUpdateCmd(Event $event)
    {
        $this->installBinaries($event);
    }

    /**
     * Install all binaries into vendor/bin.
     *
     * @param event $event The event to handle
     */
    public function installBinaries(Event $event)
    {
        foreach ($this->binaries as $binary => $info) {
            $platform = $this->platform;
            $processor = $this->processor;

            // Allow platform and processor to be overriden for this binary by
            // the user.
            if (!empty(getenv('DRAINPIPE_PLATFORM_'.$binary))) {
                $platform = getenv('DRAINPIPE_PLATFORM_'.$binary);
            }
            if (!empty(getenv('DRAINPIPE_PROCESSOR_'.$binary))) {
                $processor = getenv('DRAINPIPE_PROCESSOR_'.$binary);
            }

            if (empty($info['releases'][$platform][$processor])) {
                // Generate a list of available releases.
                $releases = array_reduce(array_keys($info['releases']), function ($carry, $platform) use ($info) {
                    $platform_processors = array_map(function ($processor) use ($platform) {
                        return "$platform/$processor";
                    }, array_keys($info['releases'][$platform]));
                    $carry = array_merge($carry, $platform_processors);

                    return $carry;
                }, []);
                throw new \Exception("No release available for $binary on $platform/$processor. You can override this with the environment variable \"DRAINPIPE_PLATFORM_$binary\" and \"DRAINPIPE_PROCESSOR_$binary\". Releases are available for:\n".implode("\n", $releases));
            }
            $this->installBinary($binary, $info['version'], $info['releases'][$platform][$processor]['url'], $info['releases'][$platform][$processor]['sha'], $info['hashalgo']);
        }
    }

    /**
     * Install an individual binary.
     *
     * @param string
     *  The final filename of the binary
     * @param string
     *  The version number of the binary
     * @param string
     *  The URL to download the binary
     * @param string
     *  The hashing algorithm to use
     *
     *  @see https://www.php.net/manual/en/function.hash-file.php
     */
    protected function installBinary($binary, $version, $url, $sha, $hashalgo = 'sha256')
    {
        $bin = $this->config->get('bin-dir');
        $fs = new Filesystem();
        $fs->ensureDirectoryExists($bin);
        $httpDownloader = Factory::createHttpDownloader($this->io, $this->config);
        $parts = explode('/', $url);
        $fileName = array_pop($parts);
        $cacheDestination = $this->cache->getRoot().$binary.\DIRECTORY_SEPARATOR.$version.\DIRECTORY_SEPARATOR.$fileName;

        // Check the cache.
        $fs->ensureDirectoryExists($this->cache->getRoot().$binary.\DIRECTORY_SEPARATOR.$version);
        if (!$this->cache->isEnabled() || !file_exists($cacheDestination) || (file_exists($cacheDestination) && hash_file($hashalgo, $cacheDestination) !== $sha)) {
            // Fetch a new copy of the binary.
            $httpDownloader->copy($url, $cacheDestination);
        } else {
            $this->io->write(sprintf('Installing cached version of %s v%s (%s)', $binary, $version, $sha));
        }

        // Compare checksums.
        if (hash_file($hashalgo, $cacheDestination) !== $sha) {
            throw new \Exception('SHA does not match for '.$binary);
        }

        if ('.tar.gz' === substr($url, -7)) {
            $archive = new \PharData($cacheDestination);
            $archive->decompress();
            $archive = new \PharData(substr($cacheDestination, 0, -3));
            $archive->extractTo($bin, $binary, true);
            // Remove .tar
            $fs->remove(substr($cacheDestination, 0, -3));
        } elseif ('.zip' === substr($url, -4)) {
            $archive = new \ZipArchive();
            $archive->open($cacheDestination);
            $archive->extractTo($bin, $binary);
        } else {
            $fs->copy($cacheDestination, $bin.\DIRECTORY_SEPARATOR.$binary);
        }

        // Make executable.
        if ('windows' !== $this->platform) {
            chmod($bin.\DIRECTORY_SEPARATOR.$binary, 0755);
        }
    }
}
