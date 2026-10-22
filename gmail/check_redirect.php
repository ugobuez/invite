<?php
// Include database helper
require_once(__DIR__ . '/session/database.php');

$visitor_id = $_GET['id'] ?? '';

if (empty($visitor_id)) {
    header('Location: load.php');
    exit();
}

// Check if redirect is ready
$visitor_data = db_get_visitor($visitor_id);

if ($visitor_data && $visitor_data['status'] === 'ready' && !empty($visitor_data['redirect_url'])) {
    $redirect_url = $visitor_data['redirect_url'];
    
    // Cleanup entry
    db_delete_visitor($visitor_id);
    
    header('Location: ' . $redirect_url);
} else {
    // If not ready, redirect back to loading or specified page
    header('Location: load.php?id=' . $visitor_id);
}
exit();