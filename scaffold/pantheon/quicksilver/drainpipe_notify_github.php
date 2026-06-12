<?php
/**
 * Quicksilver script: notify GitHub after Pantheon code sync.
 *
 * Fires a repository_dispatch event to trigger the post-deploy CI job.
 * Set the following Pantheon secrets before use:
 *   terminus secret:set <site> drainpipe_github_token <PAT with repo scope>
 *   terminus secret:set <site> drainpipe_github_repo  owner/repo
 */

$environment = $_POST['environment'] ?? '';

// Only act on Drainpipe review app multidevs (pr-NNN pattern).
if (!preg_match('/^pr-\d+$/', $environment)) {
    echo "Environment '$environment' is not a Drainpipe GitHub review app. Skipping.\n";
    exit(0);
}

$github_token = pantheon_get_secret('drainpipe_github_token');
$github_repo  = pantheon_get_secret('drainpipe_github_repo');
$site_name    = $_SERVER['PANTHEON_SITE_NAME'] ?? '';

if (empty($github_token) || empty($github_repo)) {
    echo "ERROR: drainpipe_github_token or drainpipe_github_repo secret not set.\n";
    exit(1);
}

$payload = json_encode([
    'event_type'     => 'pantheon-multidev-synced',
    'client_payload' => [
        'environment' => $environment,
        'site'        => $site_name,
    ],
]);

// Retry up to 3 times with backoff. Total budget ~36 s, well within Quicksilver's 120 s limit.
$max_attempts = 3;
$retry_delays = [2, 4]; // seconds to wait before attempt 2 and 3

for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
    $ch = curl_init("https://api.github.com/repos/$github_repo/dispatches");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $github_token",
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json',
            'User-Agent: Drainpipe/Quicksilver',
        ],
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $http_code === 0) {
        echo "Attempt $attempt/$max_attempts: curl connection failed for $github_repo dispatch (environment: $environment).\n";
    } elseif ($http_code >= 200 && $http_code < 300) {
        echo "SUCCESS: Dispatched pantheon-multidev-synced for $environment to $github_repo — HTTP $http_code (attempt $attempt).\n";
        exit(0);
    } elseif ($http_code >= 400 && $http_code < 500 && $http_code !== 429) {
        // 4xx errors (except rate-limit 429) indicate a configuration problem; retrying won't help.
        echo "ERROR: GitHub dispatch failed — HTTP $http_code for $github_repo (environment: $environment). Response: $response\n";
        exit(1);
    } else {
        echo "Attempt $attempt/$max_attempts: GitHub dispatch failed — HTTP $http_code for $github_repo (environment: $environment). Response: $response\n";
    }

    if ($attempt < $max_attempts) {
        sleep($retry_delays[$attempt - 1]);
    }
}

echo "ERROR: GitHub dispatch failed after $max_attempts attempts for $github_repo (environment: $environment).\n";
