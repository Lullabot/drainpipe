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

class BinaryInstaller implements PluginInterface, EventSubscriberInterface
{
    /**
     * The binaries to manage and download.
     *
     * @var string[]
     */
    protected $binaries = [];

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
                'drainpipe',
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

            // Allow platform and processor to be overridden for this binary by
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
        $cacheFolder = $this->cache->getRoot().$binary.\DIRECTORY_SEPARATOR.$version;
        $cacheDestination = $cacheFolder.\DIRECTORY_SEPARATOR.$fileName;
        $cacheExtractedBinary = $cacheFolder.\DIRECTORY_SEPARATOR.$binary;
        $binDestination = $bin.\DIRECTORY_SEPARATOR.$binary;

        // Check the cache.
        $fs->ensureDirectoryExists($cacheFolder);
        if ($this->needsDownload($cacheDestination, $hashalgo, $sha)) {
            // Fetch a new copy of the binary.
            $httpDownloader->copy($url, $cacheDestination);
        } else {
            $this->io->write(sprintf('Using cached version of %s v%s (%s)', $binary, $version, $sha));
        }

        // Compare checksums.
        if (hash_file($hashalgo, $cacheDestination) !== $sha) {
            throw new \Exception('SHA does not match for '.$binary);
        }

        if ('.tar.gz' === substr($fileName, -7)) {
            $archive = new \PharData($cacheDestination);
            $archive->decompress();
            $archive = new \PharData(substr($cacheDestination, 0, -3));
            $archive->extractTo($cacheFolder, $binary, true);
            // Remove .tar
            $fs->remove(substr($cacheDestination, 0, -3));
        } elseif ('.zip' === substr($fileName, -4)) {
            $archive = new \ZipArchive();
            $archive->open(substr($cacheDestination, 0, -4));
            $archive->extractTo($cacheFolder, $binary);
        } else {
            $fs->rename($cacheDestination, $cacheExtractedBinary);
        }

        // Check the vendor/bin directory first, otherwise we could hit a
        // condition where task has called "composer install --no-dev" after
        // "composer install" and tries to replace the task binary - this will
        // fail because the binary is already being run and you'll get a "failed
        // to open stream: Text file busy" error.
        if (file_exists($binDestination) && hash_file('sha256', $binDestination) === hash_file('sha256', $cacheExtractedBinary)) {
            $this->io->write(sprintf('%s v%s (%s) already exists in bin-dir, not overwriting.', $binary, $version, $sha));
        }
        else {
            $fs->copy($cacheExtractedBinary, $binDestination);
            // Make executable.
            if ('windows' !== $this->platform) {
                chmod($binDestination, 0755);
            }
        }
    }

    /**
     * Return if a file needs to be downloaded or not.
     *
     * @param string $cacheDestination The destination path to the downloaded file.
     * @param $hashalgo The hash algorithm used to validate the file.
     * @param $hash The hash used to validate the file.
     *
     * @return bool True if the file needs to be downloaded again, false otherwise.
     */
    private function needsDownload(string $cacheDestination, $hashalgo, $hash): bool {
        return !$this->cache->isEnabled() || !file_exists($cacheDestination) || (file_exists($cacheDestination) && hash_file($hashalgo, $cacheDestination) !== $hash);
    }
}
