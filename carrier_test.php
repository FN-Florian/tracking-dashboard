<?php
error_reporting(E_ERROR | E_PARSE);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $ProjectName; ?></title>
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/custom/custom.css">
</head>
<body>
    <div class='container'>

        <?php

        require 'vendor/autoload.php';

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

        $S_userID = 1;

        if(isset($_POST['submit']))
        {
            $carrier = $_POST['carrier'];
            $tracking = $_POST['tracking'];

            echo "<h2>Tracking (".$carrier." - ".$tracking.")</h2>";
            
            $carrierClass->AddParcel($carrier, $tracking);
            $TrackingData = $carrierClass->GetTracking($tracking);

            var_dump($TrackingData);
            
            if($carrierClass->WriteTrackingUpdate($tracking, $TrackingData))
            {
                echo "<div class='alert alert-success'>Tracking wurde erfolgreich aktualisiert.</div>";
            }
            else
            {
                echo "<div class='alert alert-danger'>Tracking konnte nicht aktualisiert werden.</div>";
            }

            
            echo "<pre>";
            var_dump($TrackingData);
            echo "</pre>";

            echo "
            <br><hr><br>
            ";
        }


        ?>



        <form action="carrier_test.php" method="post">
            <select name='carrier'>
                <option value=''>Carrier wählen</option>
                <option value='DHL'>DHL</option>
                <option value='UPS'>UPS</option>
            </select>
            <br>
            <input type='text' name='tracking' placeholder='Trackingnummer' required />
            <br><br>
            <input type='submit' name='submit' value='Trackingnummer prüfen' />
        </form>
    </div>

    <script src="/assets/custom/jquery.min.js"></script>
    <script src="/assets/bootstrap/js/popper.min.js"></script>
    <script src="/assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="/assets/fontawesome/js/all.min.js"></script>
</body>
</html>