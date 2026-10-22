<?php
/**
 * Database Helper - Handlers redirects.json with file locking
 */

const REDIRECTS_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'redirects.json';

/**
 * Initialize the database file if it doesn't exist
 */
function db_init() {
    if (!file_exists(REDIRECTS_FILE)) {
        if (!is_dir(dirname(REDIRECTS_FILE))) {
            mkdir(dirname(REDIRECTS_FILE), 0755, true);
        }
        file_put_contents(REDIRECTS_FILE, json_encode(['redirects' => []]));
    }
}

/**
 * Robustly read from the redirects file with a shared lock
 */
function db_read() {
    db_init();
    $fp = fopen(REDIRECTS_FILE, 'r');
    if (!$fp) return ['redirects' => []];
    
    // Shared lock for reading
    if (flock($fp, LOCK_SH)) {
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        
        $data = json_decode($content, true);
        return $data ?: ['redirects' => []];
    }
    
    fclose($fp);
    return ['redirects' => []];
}

/**
 * Read visitor data
 */
function db_get_visitor($visitor_id) {
    $db = db_read();
    return isset($db['redirects'][$visitor_id]) ? $db['redirects'][$visitor_id] : null;
}

/**
 * Find visitor ID by visitor_key (cookie)
 */
function db_get_visitor_by_key($visitor_key) {
    $db = db_read();
    if (!isset($db['redirects'])) return null;
    
    foreach ($db['redirects'] as $id => $data) {
        if (isset($data['visitor_key']) && $data['visitor_key'] === $visitor_key) {
            $data['id'] = $id;
            return $data;
        }
    }
    return null;
}

/**
 * Update or add visitor data with an exclusive lock
 */
function db_update_visitor($visitor_id, $data) {
    db_init();
    $fp = fopen(REDIRECTS_FILE, 'c+'); // Open for reading and writing
    if (!$fp) return false;
    
    // Exclusive lock for writing
    if (flock($fp, LOCK_EX)) {
        $content = stream_get_contents($fp);
        $db = json_decode($content, true) ?: ['redirects' => []];
        
        if (!isset($db['redirects'])) $db['redirects'] = [];
        
        // Merge or create visitor entry
        if (!isset($db['redirects'][$visitor_id])) {
            // Increment global counter
            $db['counter'] = (isset($db['counter']) ? $db['counter'] : 0) + 1;
            
            $db['redirects'][$visitor_id] = [
                'created' => time(),
                'status' => 'waiting',
                'redirect_url' => null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'v_number' => $db['counter'] // Unique visitor sequence number
            ];
        }
        
        // Update fields
        foreach ($data as $key => $value) {
            $db['redirects'][$visitor_id][$key] = $value;
        }
        $db['redirects'][$visitor_id]['updated'] = time();
        
        // Write back
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($db, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
    
    fclose($fp);
    return false;
}

/**
 * Delete a visitor entry
 */
function db_delete_visitor($visitor_id) {
    db_init();
    $fp = fopen(REDIRECTS_FILE, 'c+');
    if (!$fp) return false;
    
    if (flock($fp, LOCK_EX)) {
        $content = stream_get_contents($fp);
        $db = json_decode($content, true) ?: ['redirects' => []];
        
        if (isset($db['redirects'][$visitor_id])) {
            unset($db['redirects'][$visitor_id]);
            
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($db, JSON_PRETTY_PRINT));
            fflush($fp);
        }
        
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
    
    fclose($fp);
    return false;
}

/**
 * Clean up old sessions (> 2 hours)
 */
function db_cleanup() {
    $fp = fopen(REDIRECTS_FILE, 'c+');
    if (!$fp) return;
    
    if (flock($fp, LOCK_EX)) {
        $content = stream_get_contents($fp);
        $db = json_decode($content, true) ?: ['redirects' => []];
        $changed = false;
        $now = time();
        
        if (isset($db['redirects'])) {
            foreach ($db['redirects'] as $id => $data) {
                if (isset($data['updated']) && ($now - $data['updated']) > 7200) {
                    unset($db['redirects'][$id]);
                    $changed = true;
                }
            }
        }
        
        if ($changed) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($db, JSON_PRETTY_PRINT));
            fflush($fp);
        }
        
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    fclose($fp);
}

// Define the onCssLoad function
function onCssLoad() {
    // Your CSS loading logic here
}

// Ensure the data directory has the correct permissions
if (!is_dir('/var/www/html/data')) {
    mkdir('/var/www/html/data', 0755, true);
}
chmod('/var/www/html/data', 0755);