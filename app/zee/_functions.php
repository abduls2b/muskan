<?php
//=============================================================================//
// SCRIPT WRITTEN BY @YGX_WORLD TEAM, FOR EDUCATION PURPOSE ONLY.
// Don't Sell this Script, This is 100% Free.
//=============================================================================//
function base64UrlEncode($data) {
    $base64 = base64_encode($data);
    $base64 = strtr($base64, '+/', '-_');
    return rtrim($base64, '=');
}

function generateDDToken() {
    $payload = [
        'schema_version' => '1',
        'os_name' => 'N/A',
        'os_version' => 'N/A',
        'platform_name' => 'Chrome',
        'platform_version' => '104',
        'device_name' => '',
        'app_name' => 'Web',
        'app_version' => '2.52.31',
        'player_capabilities' => [
            'audio_channel' => ['STEREO'],
            'video_codec' => ['H264'],
            'container' => ['MP4', 'TS'],
            'package' => ['DASH', 'HLS'],
            'resolution' => ['240p', 'SD', 'HD', 'FHD'],
            'dynamic_range' => ['SDR']
        ],
        'security_capabilities' => [
            'encryption' => ['WIDEVINE_AES_CTR'],
            'widevine_security_level' => ['L3'],
            'hdcp_version' => ['HDCP_V1', 'HDCP_V2', 'HDCP_V2_1', 'HDCP_V2_2']
        ]
    ];

    $header = base64UrlEncode(json_encode(['alg' => 'none', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode($payload));

    return "$header.$payload.";
}

function generateGuestToken() {
    $hex = '0123456789abcdef';
    $token = '';
    $segments = [8, 4, 4, 4, 12];
    
    foreach ($segments as $length) {
        for ($i = 0; $i < $length; $i++) {
            $token .= $hex[mt_rand(0, 15)];
        }
        $token .= ($length < 12) ? '-' : '';
    }
    
    return $token;
}
function fetchPlatformToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.zee5.com/live-tv/aaj-tak/0-9-aajtak');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0'
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode !== 200) {
        exit("token can't be extracted, most probably your server IP is blocked.");
    }
    preg_match('/"gwapiPlatformToken"\s*:\s*"([^"]+)"/', $response, $matches);
    return $matches[1] ?? '';
}

function fetchM3U8url() {
    $guestToken = generateGuestToken();
    $platformToken = fetchPlatformToken();
    $ddToken = generateDDToken();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://spapi.zee5.com/singlePlayback/getDetails/secure?channel_id=0-9-aajtak&device_id=' . $guestToken . '&platform_name=desktop_web&translation=en&user_language=en,hi&country=IN&state=&app_version=4.16.3&user_type=guest&check_parental_control=false');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'content-type: application/json',
        'origin: https://www.zee5.com',
        'referer: https://www.zee5.com/',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'x-access-token' => $platformToken,
        'X-Z5-Guest-Token' => $guestToken,
        'x-dd-token' => $ddToken
    ]));

    $response = curl_exec($ch);
    curl_close($ch);
    $responseData = json_decode($response, true);
    if (!$responseData) {
        exit("Invalid response recieved from api. Most probably your server IP is blocked.");
    }

    if (isset($responseData['keyOsDetails']['video_token'])) {
        if (!filter_var($responseData['keyOsDetails']['video_token'], FILTER_VALIDATE_URL)) {
            exit("Error: Invalid URL recieved.");
        }
        return $responseData['keyOsDetails']['video_token'];
    } else {
        exit("Error: Could not fetch m3u8 URL");
    }
}
function generateCookieZee5($userAgent) {
    try {
        $m3u8Url = fetchM3U8url();
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $m3u8Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode !== 200) {
            exit("hdntl token can't be extracted, most probably your server IP is blocked.");
        }
        if (preg_match('/hdntl=([^,\s]+)/', $result, $matches)) {
            return ['cookie' => $matches[0]];
        }
        exit("Something went wrong.");
    } catch (Exception $e) {
        exit("An error occurred: " . $e->getMessage());
    }
}
//@yuvraj824