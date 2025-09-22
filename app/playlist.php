<?php
//==================================================================//
// ZEE
//==================================================================//

// Set proper headers for DASH content
//header('Content-Type: application/dash+xml');
//header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests
//header('Cache-Control: max-age=3600'); // Cache for 1 hour


$jsonFile = 'https://mahir-master.pages.dev/mahir/zee.json';
$jsonData = file_get_contents($jsonFile);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$requestUri = $_SERVER['REQUEST_URI'];
$scriptUrl = $protocol . $host . str_replace('playlist.php','index.php', $requestUri);

$data = json_decode($jsonData, true);

echo "#EXTM3U\n\n";
foreach ($data['data'] as $channel) {
    $id = $channel['id'];
    $name = $channel['channel_name'];
    $logo = $channel['logo'];
    $genre = $channel['genre'];
    $streamUrl = $scriptUrl.'?id='. $id;    
    echo "#EXTINF:-1 tvg-id=\"$id\" group-title=\"$genre\" tvg-logo=\"$logo\",$name\n";
    echo "$streamUrl\n\n";
}

//==================================================================//
// JIOSTAR
//==================================================================//

$ck = file_get_contents('https://mahir-master.vercel.app/abcd.txt');
$ck = scarlet_witch("decrypt", $ck);

function scarlet_witch($action, $data)
{
    $method = "aes-128-cbc";
    $iv = "JITENDRA_KUMAR_U";
    $key = "JITENDRA_KUMAR_U";
    if ($action == "encrypt") {
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        if (!empty($encrypted)) {
            $response = bin2hex($encrypted);
        }
    } elseif ($action == "decrypt") {
        $decrypted = openssl_decrypt(hex2bin($data), $method, $key, OPENSSL_RAW_DATA, $iv);
        if (!empty($decrypted)) {
            $response = $decrypted;
        }
    } else {
        $response = "something went wrong";
    }
    return $response;
}

$jsonFile = 'https://mahir-master.pages.dev/mahir/jstar.json';
$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

foreach ($data['data'] as $channel) {
   $id = $channel['id'];
   $group_title = $channel['group_title']; 
   $logo = $channel['logo'];
   $license_key = $channel['license_key'];
   $channel_name = $channel['channel_name'];   

   $url = $channel['url'];
   echo "\n#EXTINF:-1 tvg-id=\"$id\" group-title=\"$group_title\" tvg-logo=\"$logo\", $channel_name\n";
   echo "#KODIPROP:inputstream.adaptive.license_type=clearkey\n";
   echo "#KODIPROP:inputstream.adaptive.license_key=$license_key\n";
 //  echo "#EXTHTTP:{\"cookie\":\"$ck\"}\n";
  // echo "#EXTVLCOPT:http-user-agent=plaYtv/7.1.3 (Linux;Android 13) ygx/69.1 ExoPlayerLib/824.0\n";
   echo $url."?".$ck."\n\n";
 }

//============================================================//
// SONYLIV
//============================================================//

$jsonFile = file_get_contents('https://mahir-master.pages.dev/mahir/sony.json');

$data = json_decode($jsonFile, true);

// echo "#EXTM3U\n\n";

foreach ($data['data'] as $channel) {
    $id = $channel['id'];
    $name = $channel['channel_name'];
    $logo = $channel['logo'];
    $genre = $channel['group_title'];
    $streamUrl = $channel['url'];    

    echo "#EXTINF:-1 tvg-id=\"$id\" group-title=\"$genre\" tvg-logo=\"$logo\",$name\n";
    echo "$streamUrl\n\n";
}





