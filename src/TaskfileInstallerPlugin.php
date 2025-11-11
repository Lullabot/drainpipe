<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class TaskfileInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'installTaskfile',
            ScriptEvents::POST_UPDATE_CMD => 'installTaskfile',
        ];
    }

    public function installTaskfile(Event $event): void
    {
        $io = $event->getIO();
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        $os = PHP_OS_FAMILY;
        $taskBin = $vendorDir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'task';

        if ($os === 'Windows') {
            $taskBin .= '.exe';
        }

        // Check if task is already available system-wide
        $systemTaskPath = $this->findSystemTask($os);
        if ($systemTaskPath) {
            $io->write("<info>Found existing Taskfile binary at: {$systemTaskPath}</info>");
            $this->createSymlink($systemTaskPath, $taskBin, $io);
            return;
        }

        $binDir = $vendorDir . DIRECTORY_SEPARATOR . 'bin';

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $drainpipeDir = $vendorDir . DIRECTORY_SEPARATOR . 'lullabot' . DIRECTORY_SEPARATOR . 'drainpipe';
        $version = trim(@file_get_contents($drainpipeDir . DIRECTORY_SEPARATOR . '.taskfile') ?: '');

        if ($os === 'Windows') {
            $this->installWindows($binDir, $io, $version);
        } else {
            $this->installUnix($binDir, $io, $version);
        }
    }

    private function findSystemTask(string $os): ?string
    {
        if ($os === 'Windows') {
            // Use 'where' command on Windows
            exec('where task.exe 2>NUL', $output, $returnCode);
            if ($returnCode === 0 && !empty($output[0])) {
                return trim($output[0]);
            }
        } else {
            $path = '/usr/local/bin/task';
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function createSymlink(string $target, string $link, IOInterface $io): void
    {
        $linkDir = dirname($link);

        if (!is_dir($linkDir)) {
            mkdir($linkDir, 0755, true);
        }

        // Remove existing symlink/file if it exists
        if (file_exists($link) || is_link($link)) {
            @unlink($link);
        }

        if (@symlink($target, $link)) {
            $io->write("<info>Created symlink to system Taskfile in vendor/bin</info>");
        } else {
            $io->writeError('Failed to create symlink, will install locally instead');
        }
    }

    private function installWindows(string $binDir, IOInterface $io, string $version): void
    {
        // Do not reinstall if desired version is already present
        if (file_exists($binDir . DIRECTORY_SEPARATOR . 'task.exe')) {
            exec('"' . $binDir . DIRECTORY_SEPARATOR . 'task.exe" --version', $output, $returnCode);
            if ($returnCode === 0 && isset($output[0])) {
                if (trim($output[0]) === ltrim($version, 'v')) {
                    return;
                }
            }
        }

        // Run official installer
        $script = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'install-task.ps1';
        file_put_contents($script, file_get_contents('https://taskfile.dev/install.ps1'));

        $cmd = sprintf(
            'powershell -ExecutionPolicy Bypass -File "%s" -InstallPath "%s"',
            $script,
            $binDir
        );

        if ($version) {
            $cmd .= sprintf(' -Version "%s"', $version);
        }

        exec($cmd, $output, $returnCode);

        @unlink($script);

        if ($returnCode === 0) {
            $io->write('<info>Taskfile installed successfully</info>');
        } else {
            $io->writeError('Failed to install Taskfile');
        }
    }

    private function installUnix(string $binDir, IOInterface $io, string $version): void
    {
        // Do not reinstall if desired version is already present
        if (file_exists($binDir . '/task')) {
            exec($binDir . '/task --version', $output, $returnCode);
            if ($returnCode === 0 && isset($output[0])) {
                if (trim($output[0]) === ltrim($version, 'v')) {
                    return;
                }
            }
        }

        // Run official installer
        $cmd = sprintf(
            'curl -sL %s | sh -s -- -b %s -d %s',
            escapeshellarg('https://taskfile.dev/install.sh'),
            escapeshellarg($binDir),
            escapeshellarg($version)
        );
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            $io->write('<info>Taskfile installed successfully</info>');
        } else {
            $io->writeError('Failed to install Taskfile');
        }
    }
}
