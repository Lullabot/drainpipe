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
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_linux_amd64.tar.gz', 'sha' => 'a6241c9fbcc49bdffef907e4d6325adb074295fd094f2bfa6a2e32282c2ed06e'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_linux_arm.tar.gz', 'sha' => '623d0fdf13b2940495c177f4dce83ec9f2db26645011052280a812a8ba1f146a'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_linux_386.tar.gz', 'sha' => '4ae95300c51d53f894cce9bc45f22b2347492772b21c3a6ddf3d47304c7bbefa'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_linux_arm64.tar.gz', 'sha' => '30d3c727a434ee3bf69fb69e5d1aa84c3ab401fc2343a2760b4c7808acc689b8'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_darwin_amd64.tar.gz', 'sha' => 'a2aefff4f3cb2851ce133c13a20502f1dfa8a6bc34d3f4e01c67cf3278f4f73d'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_darwin_arm64.tar.gz', 'sha' => 'a575c9e10591bb35d1bb678bdde2fa221330d207a787b0a5979d4287ee7f4c0f'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_windows_amd64.zip', 'sha' => '6586105949b4359b37f770b7604542c23f064e055c6521791cd8d5916ec287fb'],
		            'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_windows_arm.zip', 'sha' => 'cda87056d8289f55d040e413171357ad7b1efaa75ae3c9433106f13a9057053e'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_windows_arm64.zip', 'sha' => '955e88bb22b3396bd95c9d8d25e8fd71a323364549e012d863287fd069d51ee7'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.38.0/task_windows_386.zip', 'sha' => '72962ea63388db41750e60cd0184e8c68ffd008ce277ba7502e974e40ee36bf2'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.38.0',
        ],
        'action-validator' => [
            'releases' => [
                'linux' => [
                    'amd64' =>  ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_linux_amd64', 'sha' => 'fa61521913ee4cf5de7e4d5b803741b2c60ebde447ee38e2b71abbd213d3354a'],
                    'arm64' =>  ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_linux_arm64', 'sha' => '38a582690ab7e64ba33b4c29eaf16979ed116d4daf40fde39ec18992c475c0b1'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_darwin_amd64', 'sha' => '10f453ad4ed011eb3866bd35c25311bd2fe1fbf353cbe64793115de2348f8ddb'],
                    'arm64' => ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_darwin_arm64', 'sha' => '68e09e0793cf958daf0aebe69fb2bf858232fb9c4f74f913dab02db67d32224d'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '0.6.0',
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
