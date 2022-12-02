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
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_linux_amd64.tar.gz', 'sha' => 'b8bb5258d5fa3f0e278309b393b67a56065c0fa0e69be73e110b45094fa1e01c'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_linux_arm.tar.gz', 'sha' => '8cfd5fae7d5f9e9f8f0b74ee44bd50f60447d77cbab3251c716fdf3ac891ddb3'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_linux_386.tar.gz', 'sha' => '1605acd2801944a74dffdc537b2908b312995f776947953c365fb2dcca6a0073'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_linux_arm64.tar.gz', 'sha' => '7587552d01ddb4c52b5172b4629e1d4c83d9a17a93854c6d486dc017b4d7ba29'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_darwin_amd64.tar.gz', 'sha' => 'ae067158cb08b60ed7de63775bbb6194778d6fea096c12cea59113d69a304842'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_darwin_arm64.tar.gz', 'sha' => '59ab06d9b9a707a086a31f79f9ae05c3d817a087445b4a52d8eb58f743e6c250'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_windows_amd64.zip', 'sha' => '583e4c347fe9b7dbbe827dc1080c0adc6728b1bee42704d4ba02a5ccced8a807'],
		    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_windows_arm.zip', 'sha' => '9dc2190f94d4ad0bf192915fa6b40910353d58b9b8f08e8abaa856479ddb2644'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_windows_arm64.zip', 'sha' => '7b14b32fe48cbb900795201f152c8062f30774a47d00a1de9f339b21c227c28c'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.18.0/task_windows_386.zip', 'sha' => 'a6c467ee87650df0ce35e9d3ab65853ba7aabf4b1871cb2eaafaed711dfcbd8b'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.18.0',
        ],
    ];
}
