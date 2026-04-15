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

echo "GitHub dispatch HTTP $http_code: $response\n";
