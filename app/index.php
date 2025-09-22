<?php
//=============================================================================//
// SCRIPT WRITTEN BY @YGX_WORLD TEAM, FOR EDUCATION PURPOSE ONLY.
// Don't Sell this Script, This is 100% Free.
//=============================================================================//
include_once '_functions.php';

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0";

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    die("Channel id not found in parameter.");
}

function getCookieZee5($userAgent) {
//    $UAhash = md5($userAgent);
    $cacheFile = "tmp/cookie.txt";
    if (!file_exists(dirname($cacheFile))) {mkdir(dirname($cacheFile), 0755, true);}
    $cacheExpiry = 86000; // 24 hour
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheExpiry)) {
        return file_get_contents($cacheFile);
    }
    $data = generateCookieZee5($userAgent);
    if (isset($data['cookie'])) {
        file_put_contents($cacheFile, $data['cookie']);
        return $data['cookie'];
    }
}


$file = 'zee_data.json';
$json_data = file_get_contents($file);

if ($json_data === false) {
    http_response_code(500);
    die('data.json file not found.');
}

$data = json_decode($json_data, true);
$channelData = null;
foreach ($data['data'] as $channel) {
    if ($channel['id'] == $id) {
        $channelData = $channel;
        break;
    }
}
if ($channelData === null) {
    http_response_code(404);
    die('Channel not found.');
}

$initialUrl = $channelData['url'];
$w = getCookieZee5($userAgent);


header("Location: $initialUrl?$w");exit;
