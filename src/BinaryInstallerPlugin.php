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
}
