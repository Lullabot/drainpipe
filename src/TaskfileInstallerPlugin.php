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

        // Skip if already installed
        if (file_exists($taskBin)) {
            return;
        }

        $binDir = $vendorDir . DIRECTORY_SEPARATOR . 'bin';

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        if ($os === 'Windows') {
            $this->installWindows($binDir, $io);
        } else {
            $this->installUnix($binDir, $io);
        }
    }

    private function installWindows(string $binDir, IOInterface $io): void
    {
        $url = 'https://taskfile.dev/install.ps1';
        $script = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'install-task.ps1';

        file_put_contents($script, file_get_contents($url));

        $cmd = sprintf(
            'powershell -ExecutionPolicy Bypass -File "%s" -InstallPath "%s"',
            $script,
            $binDir
        );

        exec($cmd, $output, $returnCode);

        @unlink($script);

        if ($returnCode === 0) {
            $io->write('<info>✓ Taskfile installed successfully</info>');
        } else {
            $io->writeError('<error>Failed to install Taskfile</error>');
        }
    }

    private function installUnix(string $binDir, IOInterface $io): void
    {
        $url = 'https://taskfile.dev/install.sh';

        $cmd = sprintf(
            'curl -sL %s | sh -s -- -d -b %s',
            escapeshellarg($url),
            escapeshellarg($binDir)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            $io->write('<info>✓ Taskfile installed successfully</info>');
        } else {
            $io->writeError('<error>Failed to install Taskfile</error>');
        }
    }
}
