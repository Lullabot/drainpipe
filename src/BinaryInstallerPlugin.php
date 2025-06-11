<?php

namespace Lullabot\Drainpipe;

class BinaryInstallerPlugin extends BinaryInstaller
{
    /**
     * The binaries to manage and download.
     *
     * @var array[]
     */
    protected $binaries = [
        // Also update if changing version
        // - .github/workflows/ValidateTaskfile.yml
        // - Tugboat templates
        'task' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_linux_amd64.tar.gz', 'sha' => 'd6c9c0a14793659766ee0c06f9843452942ae6982a3151c6bbd78959c1682b82'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_linux_arm.tar.gz', 'sha' => '659c3a55654c9438dbb399f33b955c834162ce99b43275df66e3b8f17559107c'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_linux_386.tar.gz', 'sha' => '83efddb7f0683e7da3709fa8cf634e6decc7cc2946ff3c03fb6f9409c6d385b5'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_linux_arm64.tar.gz', 'sha' => '3b2a79a5372e3806c4b345bdfb4a1a1a93a287a58804986bb57b16665db5db22'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_darwin_amd64.tar.gz', 'sha' => '7bb354c0ceb01d9256373749cc7155d33de7234587fbbab4d985ee77c0414274'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_darwin_arm64.tar.gz', 'sha' => 'cfbfe894ae1987a378af50230c9b60ec62a072093e96d217c1e5dd9f4b108fbe'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_windows_amd64.zip', 'sha' => 'db39c209f677c8f1513bec1b22c0997131d56e252db0a2d57208414c96ad9056'],
		            'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_windows_arm.zip', 'sha' => '30bfdaee1eb1bd91f4e063c8fcdf383cdf64329b3eedae440f4ac3c96e7966f1'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_windows_arm64.zip', 'sha' => 'e2cf946b4352626b4635e87ec8f2d65734b7f08a4eef2187e02f1d99cb2f1012'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.44.0/task_windows_386.zip', 'sha' => '94190fcb14463c03d0f0e3785eee7db6b942532ef81b7fbdb6cc838c0717d460'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.44.0',
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
