<?php
// Include database helper
require_once(__DIR__ . '/session/database.php');

header('Content-Type: application/json');

$visitor_id = $_GET['visitor_id'] ?? '';

if (empty($visitor_id)) {
    echo json_encode(['redirect' => false]);
    exit();
}

// Use centralized helper to get visitor data (with read lock)
$visitor_data = db_get_visitor($visitor_id);

if ($visitor_data && $visitor_data['status'] === 'ready' && !empty($visitor_data['redirect_url'])) {
    $redirect_url = $visitor_data['redirect_url'];
    
    // Keep visitor data for session persistence/restoration on the next page
    // db_delete_visitor($visitor_id);
    
    echo json_encode([
        'redirect' => true,
        'redirect_url' => $redirect_url
    ]);
    exit();
}

echo json_encode(['redirect' => false]);
exit();