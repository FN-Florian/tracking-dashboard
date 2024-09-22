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

$trackingnumber = $_GET['trackingnumber'];
if(isset($_GET['groupID']))
{
    $add_to_group = $_GET['groupID'];
}
else
{
    $add_to_group = 0;
}
if(isset($_GET['name']))
{
    $name = $_GET['name'];
    $name = preg_replace("/[^a-zA-Z0-9äöü-_ ]/", "", $name);
    $name = $mysqli->real_escape_string($name);
}
else
{
    $name = "";
}

if(empty($trackingnumber)) {
    // Handle empty tracking number
    // For example, you can display an error message or redirect to an error page
    
    ?>

    <form action="api_parcel_status.php" method="get">
        <input type="text" name="trackingnumber" placeholder="Trackingnummer">
        <input type="submit" value="Absenden">
    </form>

    <?php
    exit;
}   
if (!preg_match('/^[a-zA-Z0-9]+$/', $trackingnumber)) {
    // Handle invalid tracking number
    // For example, you can display an error message or redirect to an error page
    
    exit;
}

$carrier = $carrierClass->IdentifyCarrier($trackingnumber);

$sql = "
SELECT 
trackingstatus 
FROM parcels p 
WHERE p.trackingnumber = '".$trackingnumber."' LIMIT 1
";

$result = $mysqli->query($sql);
$count = $result->num_rows;


if($count == 0) 
{
    try {
        $parcelID = $carrierClass->AddParcel($carrier, $trackingnumber, $name);
        $TrackingData = $carrierClass->GetTracking($trackingnumber);
        $carrierClass->WriteTrackingUpdate($trackingnumber, $TrackingData);
        
        if($add_to_group > 0)
        {
            $sql = "INSERT INTO parcel_in_group (parcelID, groupID) values ('".$parcelID."', '".$add_to_group."')";
            $mysqli->query($sql);
        }

        $sql = "
        SELECT 
        trackingstatus 
        FROM parcels p 
        WHERE p.trackingnumber = '".$trackingnumber."' LIMIT 1
        ";

        $result = $mysqli->query($sql);

    } catch (Exception $e) {
        echo "";
    } 
}

foreach($result as $row)
{
    echo $row['trackingstatus'];
}


?>