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
        // Also update .github/workflows/ValidateTaskfile.yml if changing version
        'task' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_linux_amd64.tar.gz', 'sha' => '7232508b0040398b3dcce5d92dfe05f65723680eab2017f3cee6c0a7cf9dd6c1'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_linux_arm.tar.gz', 'sha' => '52b3f9de1c16c6c9eaf9655b54f4d89c154c811e72aa660f1ba08acf7f24f7c5'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_linux_386.tar.gz', 'sha' => 'a412502002457dfd0a7dd94f18c57a4f454d5eb1d1ddeb6ad5d5a8484ef39950'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_linux_arm64.tar.gz', 'sha' => '7fd883e2b3238c040667cd8598d56370db2c387fb3915224eaf9c503c36dd9b1'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_darwin_amd64.tar.gz', 'sha' => 'e714fc98692b12d30f633f533b636301a82472da3532ee8e34c4ae31071d46d8'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_darwin_arm64.tar.gz', 'sha' => 'a9ca252299b38d018de515bc8b8e185f71e649e6fb5acfaf76169a93b44e3330'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_windows_amd64.zip', 'sha' => '8abe73c80e28688873e687ede743b8b82f875e78b42ea1a9a38477ab1d2839c0'],
		    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_windows_arm.zip', 'sha' => '30767ade71e5b2b807483c5cfbfd818cef2c7f819e3f7343c6908d73a04ef315'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_windows_arm64.zip', 'sha' => 'f745313e93123662a7f2d29d69d14bf07d6911a86bdf869f3f298531c6590812'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.21.0/task_windows_386.zip', 'sha' => 'f7d3c0338451a84576efcdbe1791dd1c3aef61db62e8dfc06fc9f60bd78c2000'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.21.0',
        ],
    ];
}
