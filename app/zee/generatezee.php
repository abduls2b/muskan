<?php
//================================


$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0";

include_once '_functions.php';

  $cacheFile = "tmp/cookie.txt";

    $data = generateCookieZee5($userAgent);
    if (isset($data['cookie'])) {
        file_put_contents($cacheFile, $data['cookie']);
      
    }
echo "generated successfully...\n";

include_once 'github_zee.php';
