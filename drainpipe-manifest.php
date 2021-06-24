<?php

require_once __DIR__ . '/vendor/autoload.php';

if ($argc < 2) {
    fwrite(fopen('php://stderr', 'w'), "Usage: drainpipe-manfiest.php <include file> [exclude file]\n");
    exit(1);
}

$include = $argv[1];

if (isset($argv[2])) {
    $exclude = $argv[2];
}

if (!file_exists($include) && !is_readable($include)) {
    fwrite(fopen('php://stderr', 'w'), sprintf("Error: %s is not readable.\n", $include));
    exit(2);
}

$include_patterns = explode("\n", file_get_contents($include));
$paths = [];
foreach ($include_patterns as $pattern) {
    if (empty($pattern) || strpos($pattern, '#') === 0) {
        continue;
    }

    $paths = Webmozart\Glob\Glob::glob(\Webmozart\PathUtil\Path::makeAbsolute($pattern, getcwd()));
}

foreach ($paths as $index => $path) {
    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $paths[] = $file;
        }
        unset($paths[$index]);
    }
}

if ($exclude) {
    if (!file_exists($exclude) && !is_readable($exclude)) {
        fwrite(fopen('php://stderr', 'w'), sprintf("Error: %s is not readable.\n", $exclude));
        exit(2);
    }

    $exclude_patterns = explode("\n", file_get_contents($exclude));
    $to_remove = [];
    foreach ($exclude_patterns as $pattern) {
        if (empty($pattern) || strpos($pattern, '#') === 0) {
            continue;
        }

        $to_remove = Webmozart\Glob\Glob::glob(\Webmozart\PathUtil\Path::makeAbsolute($pattern, getcwd()));
    }

    foreach ($to_remove as $index => $path) {
        if (is_dir($path)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $to_remove[] = $file;
            }
            unset($to_remove[$index]);
        }
    }

    $paths = array_diff($paths, $to_remove);
}

foreach ($paths as $path) {
    $path = \Webmozart\PathUtil\Path::makeRelative((string) $path, getcwd());
    print "$path\n";
}
