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
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_linux_amd64.tar.gz', 'sha' => 'cad72446d2b939ec611fea14c48f7ce28713c68cc902701fb4f1c2b12fe1fd1c'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_linux_arm.tar.gz', 'sha' => 'dfbfdcef356cbdd0c0554fbc7e7ba07554d484ef3e1c2aa2a0a1cea8eb70e39f'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_linux_386.tar.gz', 'sha' => 'c927265a095204f18b73495936ad5d041267fe76b71626bb9b5ef726c7b91400'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_linux_arm64.tar.gz', 'sha' => '03ef53f20c77572ce175ef9cbf3110ebda68e4b75450edc69ee756837cd7a9bd'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_darwin_amd64.tar.gz', 'sha' => '11945c7e9f699a64f959bd96b3ece4f6d3c7dba79b52d7a2283aa66556abc3a9'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_darwin_arm64.tar.gz', 'sha' => 'd492fe0bbb7992c02fce2f141e0b1220df16e61cca21b80039d2db2e470df5e8'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_windows_amd64.zip', 'sha' => '8a9ed4c33c94d014c09ac3cf214ed2687f00f4a9bcfa570b603f36f755fe97af'],
		    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_windows_arm.zip', 'sha' => 'f205e736904d56a373bf47353c53d36f17863bfa132fea11b3630ebcadaa3174'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_windows_arm64.zip', 'sha' => 'f7f68826d8480fe45647357e55a6b7cdd0eed5c4269e53d819070dbdd4716669'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.9.0/task_windows_386.zip', 'sha' => 'aad0c5b65b02e3df19035ad5eba4570b5d495c8acf2bd101e9c078fff9ae53c7'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.9.0',
        ],
    ];
}
