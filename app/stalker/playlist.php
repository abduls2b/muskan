<?php
// Stalker-Portal To M3U Generator Script

// ⚙ INSTRUCTIONS FOR USERS ⚙
// Update the 'config' array below with your Stalker-Portal details
// Access the generated M3U playlist by visiting: <your-domain>/playlist.php?playlist

// ============ ⚙ CONFIGURATION ============
$config = [
    'host' => 'tv.fusion4k.cc', // Replace with your Stalker-Portal host (e.g., 'example.com')
    'mac_address' => '00:1A:79:00:01:7A', // Replace with your MAC address
    'serial_number' => '25A4C25BBE6D5', // Replace with your serial number
    'device_id' => '2ED3DF4F2378C3F50EA2AD643AD0DD809DE76EBED0A3C342046636A9FE224594', // Replace with your device_id
    'device_id_2' => '2ED3DF4F2378C3F50EA2AD643AD0DD809DE76EBED0A3C342046636A9FE224594', // Replace with your device_id_2
    'stb_type' => 'MAG250', // Replace with Stalker-Portal Stb_type
    'api_signature' => '263', // No need to change
];

// Auto-generate hw_version & hw_version_2
function generateHardwareVersions() {
    global $config;
    $config['hw_version'] = '1.7-BD-' . strtoupper(substr(md5($config['mac_address']), 0, 2));
    $config['hw_version_2'] = md5(strtolower($config['serial_number']) . strtolower($config['mac_address']));
}

function logDebug($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message);
}

function getHeaders($token = '') {
    global $config;
    $headers = [
        'Cookie: mac=' . $config['mac_address'] . '; stb_lang=en; timezone=GMT',
        'Referer: http://' . $config['host'] . '/stalker_portal/c/',
        'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
        'X-User-Agent: Model: ' . $config['stb_type'] . '; Link: WiFi'
    ];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    return $headers;
}

function makeRequest($url, $headers = []) {
    $context = stream_context_create([
        'http' => [
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        logDebug("Request failed for URL: $url");
        return ['success' => false, 'data' => null];
    }
    
    return ['success' => true, 'data' => $response];
}

function getToken() {
    global $config;
    $url = "http://{$config['host']}/stalker_portal/server/load.php?type=stb&action=handshake&token=&JsHttpRequest=1-xml";
    
    logDebug("Fetching token from $url");
    $result = makeRequest($url, getHeaders());
    
    if (!$result['success']) {
        logDebug("getToken failed");
        return '';
    }
    
    logDebug("getToken response (first 500 chars): " . substr($result['data'], 0, 500));
    $data = json_decode($result['data'], true);
    $token = $data['js']['token'] ?? '';
    logDebug("Extracted token: " . ($token ? 'Success' : 'Empty'));
    
    return $token;
}

function auth($token) {
    global $config;
    
    $metrics = [
        'mac' => $config['mac_address'],
        'model' => '',
        'type' => 'STB',
        'uid' => '',
        'device' => '',
        'random' => ''
    ];
    $metricsEncoded = urlencode(json_encode($metrics));

    $url = "http://{$config['host']}/stalker_portal/server/load.php?type=stb&action=get_profile" .
        "&hd=1&ver=ImageDescription:%200.2.18-r14-pub-250;" .
        "%20PORTAL%20version:%205.5.0;%20API%20Version:%20328;" .
        "&num_banks=2&sn={$config['serial_number']}" .
        "&stb_type={$config['stb_type']}&client_type=STB&image_version=218&video_out=hdmi" .
        "&device_id={$config['device_id']}&device_id2={$config['device_id_2']}" .
        "&signature=&auth_second_step=1&hw_version={$config['hw_version']}" .
        "&not_valid_token=0&metrics={$metricsEncoded}" .
        "&hw_version_2={$config['hw_version_2']}&api_signature={$config['api_signature']}" .
        "&prehash=&JsHttpRequest=1-xml";

    logDebug("Authenticating with URL: " . substr($url, 0, 200) . "...");
    $result = makeRequest($url, getHeaders($token));
    
    if (!$result['success']) {
        logDebug("auth failed");
        return [];
    }
    
    logDebug("auth response (first 500 chars): " . substr($result['data'], 0, 500));
    $data = json_decode($result['data'], true);
    return $data['js'] ?? [];
}

function handShake($token) {
    global $config;
    $url = "http://{$config['host']}/stalker_portal/server/load.php?type=stb&action=handshake&token={$token}&JsHttpRequest=1-xml";
    
    logDebug("Performing handshake with token: $token");
    $result = makeRequest($url, getHeaders());
    
    if (!$result['success']) {
        logDebug("handShake failed");
        return '';
    }
    
    logDebug("handShake response (first 500 chars): " . substr($result['data'], 0, 500));
    $data = json_decode($result['data'], true);
    $newToken = $data['js']['token'] ?? '';
    logDebug("New token: " . ($newToken ? 'Success' : 'Empty'));
    
    return $newToken;
}


function getAccountInfo($token) {
    global $config;
    $url = "http://{$config['host']}/stalker_portal/server/load.php?type=account_info&action=get_main_info&JsHttpRequest=1-xml";
    
    logDebug("Fetching account info from $url");
    $result = makeRequest($url, getHeaders($token));
    
    if (!$result['success']) {
        logDebug("getAccountInfo failed");
        return [];
    }
    
    logDebug("getAccountInfo response (first 500 chars): " . substr($result['data'], 0, 500));
    $data = json_decode($result['data'], true);
    logDebug("Account info response: " . json_encode($data, JSON_PRETTY_PRINT));
    
    return $data['js'] ?? [];
}

function getGenres($token) {
    global $config;
    $url = "http://{$config['host']}/stalker_portal/server/load.php?type=itv&action=get_genres&JsHttpRequest=1-xml";
    
    logDebug("Fetching genres from $url");
    $result = makeRequest($url, getHeaders($token));
    
    if (!$result['success']) {
        logDebug("getGenres failed");
        return [];
    }
    
    logDebug("getGenres response (first 500 chars): " . substr($result['data'], 0, 500));
    $data = json_decode($result['data'], true);
    logDebug("Fetched genres data");
    
    return $data['js'] ?? [];
}




function getStreamURL($id, $token) {
    global $config;
    $url = "http://{$config['host']}/stalker_portal/server/load.php?type=itv&action=create_link&cmd=ffrt%20http://localhost/ch/{$id}&JsHttpRequest=1-xml";
    
    logDebug("Fetching stream URL for channel ID: $id");
    $result = makeRequest($url, getHeaders($token));
    
    if (!$result['success']) {
        logDebug("getStreamURL failed");
        return '';
    }
    
    logDebug("getStreamURL response (first 500 chars): " . substr($result['data'], 0, 500));
    $data = json_decode($result['data'], true);
    $stream = $data['js']['cmd'] ?? '';
    logDebug("Stream URL: " . ($stream ? 'Success' : 'Empty'));
    
    return $stream;
}


function genToken() {
    generateHardwareVersions();
    $token = getToken();
    
    if (!$token) {
        logDebug('Failed to retrieve initial token');
        return ['token' => '', 'profile' => [], 'account_info' => []];
    }
    
    $profile = auth($token);
    $newToken = handShake($token);
    
    if (!$newToken) {
        logDebug('Failed to retrieve new token');
        return ['token' => '', 'profile' => $profile, 'account_info' => []];
    }
    
    $account_info = getAccountInfo($newToken);
    return ['token' => $newToken, 'profile' => $profile, 'account_info' => $account_info];
}

function convertJsonToM3U($channels, $profile, $account_info) {
    global $config;
    
    $m3u = [
        '#EXTM3U',
        '# Total Channels => ' . count($channels),
        '# Script => @tg_aadi',
        ''
    ];

    $server_ip = $profile['ip'] ?? 'Unknown';
    $m3u[] = '#EXTINF:-1 tvg-name="IP" tvg-logo="https://img.icons8.com/?size=160&id=OWj5Eo00EaDP&format=png" group-title="Portal | Info",IP • ' . $server_ip;
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $m3u[] = '#EXTINF:-1 tvg-name="Telegram: @tg_aadi" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/thumb/8/82/Telegram_logo.svg/1024px-Telegram_logo.svg.png?20220101141644" group-title="Portal | Info",Telegram • @tg_aadi';
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $m3u[] = '#EXTINF:-1 tvg-name="User IP" tvg-logo="https://uxwing.com/wp-content/themes/uxwing/download/location-travel-map/ip-location-color-icon.svg" group-title="Portal | Info",User IP • ' . $user_ip;
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $m3u[] = '#EXTINF:-1 tvg-name="Portal" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/6/6f/IPTV.png?20180223064625" group-title="Portal | Info",Portal • ' . $config['host'];
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $created = $profile['created'] ?? 'Unknown';
    $m3u[] = '#EXTINF:-1 tvg-name="Created" tvg-logo="https://cdn-icons-png.flaticon.com/128/1048/1048953.png" group-title="Portal | Info",Created • ' . $created;
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $end_date = $account_info['end_date'] ?? 'Unknown';
    $m3u[] = '#EXTINF:-1 tvg-name="Expire" tvg-logo="https://www.citypng.com/public/uploads/preview/hand-drawing-clipart-14-feb-calendar-icon-701751694973910ds70zl0u9u.png" group-title="Portal | Info",End date • ' . $end_date;
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $tariff_plan = $account_info['tariff_plan'] ?? 'Unknown';
    $m3u[] = '#EXTINF:-1 tvg-name="Tariff Plan" tvg-logo="https://img.lovepik.com/element/45004/5139.png_300.png" group-title="Portal | Info",Tariff Plan • ' . $tariff_plan;
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $max_online = 'Unknown';
    if (isset($profile['storages']) && count($profile['storages']) > 0) {
        $first_storage = reset($profile['storages']);
        $max_online = $first_storage['max_online'] ?? 'Unknown';
    }
    $m3u[] = '#EXTINF:-1 tvg-name="Max Online" tvg-logo="https://thumbs.dreamstime.com/b/people-vector-icon-group-symbol-illustration-businessman-logo-multiple-users-silhouette-153484048.jpg?w=1600" group-title="Portal | Info",Max Connection • ' . $max_online;
    $m3u[] = 'https://tg-aadi.vercel.app/intro.m3u8';

    $origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";



$requestUri = $_SERVER['REQUEST_URI'];
$scriptUrl = $origin . str_replace('playlist.php?playlist','play.php', $requestUri);
$origin = $scriptUrl;


    if (empty($channels)) {
        logDebug('No channels found');
    } else {
        foreach ($channels as $index => $channel) {
            $cmd = $channel['cmd'] ?? '';
            $real_cmd = str_replace('ffrt http://localhost/ch/', '', $cmd);
            if (!$real_cmd) {
                $real_cmd = 'unknown';
                logDebug("Invalid or empty cmd for channel #$index: " . $channel['name']);
            }
            $logo_url = $channel['logo'] ? "http://{$config['host']}/stalker_portal/misc/logos/320/{$channel['logo']}" : '';
            $m3u[] = '#EXTINF:-1 tvg-id="' . $channel['tvgid'] . '" tvg-name="' . $channel['name'] . '" tvg-logo="' . $logo_url . '" group-title="' . $channel['title'] . '",' . $channel['name'];
            $channel_stream_url = "{$origin}?channel={$real_cmd}";
            $m3u[] = $channel_stream_url;
            if ($index < 5) {
                logDebug("M3U Channel #$index: {$channel['name']}, URL: $channel_stream_url");
            }
        }
    }

    return implode("\n", $m3u);
}

// Main request handler
if (isset($_GET['playlist'])) {
    handlePlaylistRequest();
} elseif (isset($_GET['channel'])) {
    handleChannelRequest();
} else {
    // Default response
    header('Content-Type: text/plain');
    echo "Stalker-Portal M3U Generator\n";
    echo "Access playlist at: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]?playlist\n";
    echo "Created by: @\n";
}

function handlePlaylistRequest() {
    global $config;
    
    logDebug('Starting token generation');
    $tokenData = genToken();
    $token = $tokenData['token'];
    $profile = $tokenData['profile'];
    $account_info = $tokenData['account_info'];
    
    if (!$token) {
        logDebug('Token generation failed, exiting');
        http_response_code(500);
        echo 'Token generation failed';
        return;
    }
    logDebug('Token generation successful');

    // Fetch all channels
    $channelsUrl = "http://{$config['host']}/stalker_portal/server/load.php?type=itv&action=get_all_channels&JsHttpRequest=1-xml";
    
    logDebug("Fetching channels from $channelsUrl");
    $result = makeRequest($channelsUrl, getHeaders($token));
    
    if (!$result['success']) {
        logDebug("Failed to fetch channels");
        http_response_code(500);
        echo 'Failed to fetch channels';
        return;
    }
    
    logDebug("Channels response (first 500 chars): " . substr($result['data'], 0, 500));
    $channelsData = json_decode($result['data'], true);

    // Fetch genres
    logDebug('Fetching genres');
    $genres = getGenres($token);

    // Parse channels
    $channels = [];
    if (isset($channelsData['js']['data'])) {
        logDebug("Found " . count($channelsData['js']['data']) . " channels in response");
        foreach ($channelsData['js']['data'] as $index => $item) {
            $channel = [
                'name' => $item['name'] ?? 'Unknown',
                'cmd' => $item['cmd'] ?? '',
                'tvgid' => $item['xmltv_id'] ?? '',
                'id' => $item['tv_genre_id'] ?? '',
                'logo' => $item['logo'] ?? ''
            ];
            if ($index < 5) {
                logDebug("Channel #$index: " . json_encode($channel, JSON_PRETTY_PRINT));
            }
            $channels[] = $channel;
        }
    } else {
        logDebug('No channel data found in response');
    }

    // Map genres to channels
    $groupTitleMap = [];
    foreach ($genres as $group) {
        $groupTitleMap[$group['id']] = $group['title'] ?? 'Other';
    }

    foreach ($channels as &$channel) {
        $channel['title'] = $groupTitleMap[$channel['id']] ?? 'Other';
    }

    // Generate M3U
    logDebug('Generating M3U content');
    $m3uContent = convertJsonToM3U($channels, $profile, $account_info);

    // Return M3U response
    logDebug('Returning M3U response');
 //   header('Content-Type: application/vnd.apple.mpegurl');
    echo $m3uContent;
}



function handleChannelRequest() {
    global $config;
    
    $channelId = $_GET['channel'] ?? '';
    if (!$channelId) {
        logDebug('Missing channel ID in URL');
        http_response_code(400);
        echo '❌ Missing channel ID in URL';
        return;
    }

    logDebug('Starting token generation for channel request');
    $tokenData = genToken();
    $token = $tokenData['token'];
    
    if (!$token) {
        logDebug('Token generation failed for channel request');
        http_response_code(500);
        echo 'Token generation failed';
        return;
    }

    $stream = getStreamURL($channelId, $token);
    if (!$stream) {
        logDebug('No stream URL received');
        http_response_code(500);
        echo 'No stream URL received';
        return;
    }

    header("Location: $stream", true, 302);
    exit;
}


// ========================================== { THE END } ================================================================================
?>