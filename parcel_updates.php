<?php

include("includes/config.php");
include("includes/mysql.class.php");
include("includes/users.class.php");
include("includes/mail.class.php");
include("includes/carrier.class.php");


$mySQLClass = new mysqliClass();
$usersClass = new usersClass();
$mysqli = $mySQLClass->ConnectDatabase();
$mailClass = new mailClass();
$carrierClass = new carrierClass();


/* Calc Update Interval 
* Default Interval: 30 minutes
* Between 20:00 and 08:00 Interval 2 Hours
*/
$UpdateInterval = "30 MINUTE";
$Hour = date("H");
if($Hour >= 20 || $Hour < 8)
{
    $UpdateInterval = "2 HOUR";
}

$sql = "
SELECT 
* 
FROM parcels p 
WHERE p.carrier IN ('DHL', 'UPS') 
AND p.trackingstatus_categorie IN ('new', 'in_progress') 
AND (last_tracking_update IS NULL OR last_tracking_update < NOW() - INTERVAL ".$UpdateInterval.") 
LIMIT 5
";

$result = $mysqli->query($sql);

foreach($result as $row)
{
    $TrackingData = $carrierClass->GetTracking($row['trackingnumber']);
    $carrierClass->WriteTrackingUpdate($row['trackingnumber'], $TrackingData);
}





$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $E_Hearthbeat_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

$headers = array();
$headers[] = "Connection: keep-alive";
$headers[] = "Pragma: no-cache";
$headers[] = "Cache-Control: no-cache";
$headers[] = "Upgrade-Insecure-Requests: 1";
$headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3";
$headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8";
$headers[] = "DNT: 1";
$headers[] = "Accept-Encoding: gzip, deflate, sdch, br";
$headers[] = "Accept-Language: de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

curl_close ($ch);


echo "done";

?>