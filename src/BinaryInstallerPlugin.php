<?php

namespace Lullabot\Drainpipe;

class BinaryInstallerPlugin extends BinaryInstaller
{
    /**
     * The binaries to manage and download.
     *
     * @var string[]
     */
    protected $binaries = [
        'task' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_linux_amd64.tar.gz', 'sha' => '6b2567253c9f6b9aaffb39751af0c7ff94c3466bf4c5ec4371fcb00df5485bd3'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_linux_arm.tar.gz', 'sha' => '6b2567253c9f6b9aaffb39751af0c7ff94c3466bf4c5ec4371fcb00df5485bd3'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_linux_386.tar.gz', 'sha' => '6b2567253c9f6b9aaffb39751af0c7ff94c3466bf4c5ec4371fcb00df5485bd3'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_linux_arm64.tar.gz', 'sha' => '6b2567253c9f6b9aaffb39751af0c7ff94c3466bf4c5ec4371fcb00df5485bd3'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_darwin_amd64.tar.gz', 'sha' => '4d59ec362a04d39ae6f1d1a2419071cc1d6230e6bf779e06567927a73d79e475'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_darwin_arm64.tar.gz', 'sha' => '03129bcbd62aa59409e9147e4c7c3d7aa9b1a1b7946d43581fd99835c781411f'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_windows_amd64.zip', 'sha' => '6b2567253c9f6b9aaffb39751af0c7ff94c3466bf4c5ec4371fcb00df5485bd3'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_windows_arm.zip', 'sha' => 'cc93110790c011af31bfd35d300e443628cd1649b7cd2d58386770085ec66752'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_windows_arm64.zip', 'sha' => '6b2567253c9f6b9aaffb39751af0c7ff94c3466bf4c5ec4371fcb00df5485bd3'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.8.0/task_windows_386.zip', 'sha' => '00dac9b129ccbb2ae019d2ded470c87baec538a9d97e2b74db9b3e6e5e6d179f'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.8.0',
        ],
    ];
}
