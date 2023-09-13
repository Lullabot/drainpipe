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
        // Also update if changing version
        // - .github/workflows/ValidateTaskfile.yml
        // - Tugboat templates
        'task' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_linux_amd64.tar.gz', 'sha' => 'a6d127f91c3a78512d8a20b4aca7b48b0b420c057fc09391ee1ae311293a565e'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_linux_arm.tar.gz', 'sha' => '1be7e3bfbe1ad29d807c6a85ef33ab0bdfbc8c7c5fe33d6e08043646e02db43a'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_linux_386.tar.gz', 'sha' => 'cc4db4b0df9f947498e5a93dddf8398dd9f1d4faab11fd3c29d773b4b3920503'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_linux_arm64.tar.gz', 'sha' => '031b26ca68a5274c0d88263bdd1b334c4f87c381f750c0e22e7777a1fb3374f7'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_darwin_amd64.tar.gz', 'sha' => '068f4d35b47419047afea167cbdff7d446ea4218548dfb3b541ca8b9a378fe84'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_darwin_arm64.tar.gz', 'sha' => 'e962a63a46251952a34c1bd0a060f2eb91009058de13ea4f7750c8ae00513f95'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_windows_amd64.zip', 'sha' => 'd46e1f3a8ce6951e2d7978c5094661b3f1473cba291bcc2fa53a7af224c403a8'],
		    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_windows_arm.zip', 'sha' => '9ad664e06f4168652f211a1c6600aea36cd8e9a63e467be0f44abfb1d58f9d6a'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_windows_arm64.zip', 'sha' => '6092dc97bdbc53ca13c7451b5b4de83fad98a8058bcfdb4a1621a91e8fb320b0'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.28.0/task_windows_386.zip', 'sha' => 'a7fd49978db94496b19141519b5f457aeeda0b92be2ec7e61aeba13cda8461c7'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.28.0',
        ],
    ];

    /**
     * Gets the version for a binary.
     *
     * @param $binary
     * @return mixed|string
     */
    public function getBinaryVersion($binary) {
        return $this->binaries[$binary]['version'];
    }
}
