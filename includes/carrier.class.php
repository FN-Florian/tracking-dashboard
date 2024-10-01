<?php

class CarrierClass{

    public function AddParcel($carrier, $trackingnumber, $name = "")
    {
        global $mysqli, $S_userID;
        if($S_userID > 0){}else{$S_userID = 1;}
        // Trackingnummer darf nur aus Buchstaben und Zahlen bestehen
        $trackingnumber = preg_replace("/[^a-zA-Z0-9]/", "", $trackingnumber);

        if($name == "")
        {
            $name = $trackingnumber;
        }

        // Carrier darf nur aus Buchstaben bestehen
        $carrier = preg_replace("/[^a-zA-Z]/", "", $carrier);

        // Prüfe ob Trackingnummer in Verbindung mit User bereits existiert
        $sql = "SELECT * FROM parcels WHERE owner_id = '".$S_userID."' AND trackingnumber = '".$trackingnumber."'";
        $result = $mysqli->query($sql);

        if($result->num_rows > 0)
        {
            return false;
        }

        $sql = "INSERT INTO parcels (owner_id, name, carrier, trackingnumber, created_date, updated_date) VALUES ('".$S_userID."', '".$name."', '".$carrier."', '".$trackingnumber."', NOW(), NOW())";
        
        $mysqli->query($sql);

        return $mysqli->insert_id;
    }

    public function WriteTrackingUpdate($trackingnumber, $Tracking)
    {
        global $mysqli;
        /*
        * Tracking Data
        *
        * $Tracking - Carrier Tracking API Response
        * Format current status and tracking details for database
        * $activity['status'] - Status of the tracking
        * $activity['status_categorie'] - StatusCategories: new, in_progress, done, error
        * $activity['activity'] - Serialized tracking details
        */

        if($Tracking['carrier'] == "UPS")
        {
            $activity = $this->WriteUPSTrackingUpdate($Tracking["data"]);
        }
        elseif($Tracking['carrier'] == "DHL")
        {
            $activity = $this->WriteDHLTrackingUpdate($Tracking["data"]);
        }
        else
        {
            return false;
        }

        $sql = "
        UPDATE 
            parcels 
        SET 
            trackingstatus              = '".$activity['status']."', 
            trackingstatus_categorie    = '".$activity['status_categorie']."', 
            tracking_details            = '".$mysqli->real_escape_string($activity['activity'])."', 
            last_tracking_update        = NOW() 
        WHERE 
            trackingnumber = '".$trackingnumber."'
        ";
        $mysqli->query($sql);

        return true;
    }

    private function WriteDHLTrackingUpdate($Tracking)
    {
        switch($Tracking['shipments'][0]['status']['statusCode'])
        {
            case "delivered":
                $dbField['status'] = "Zugestellt";
                $dbField['status_categorie'] = "done";
                break;
            case "transit":
                $dbField['status'] = "Transport";
                $dbField['status_categorie'] = "in_progress";
                break;
            case "pre-transit":
                $dbField['status'] = "Angemeldet";
                $dbField['status_categorie'] = "new";
                break;
            default:
                $dbField['status'] = "Unknown";
                $dbField['status_categorie'] = "error";
                break;
        }

        $dbField['activity'] = serialize($Tracking['shipments'][0]['events']);

        return $dbField;
    }

    private function WriteUPSTrackingUpdate($Tracking)
    {
        switch ($Tracking['trackResponse']['shipment'][0]['package'][0]['currentStatus']['code'])
        {
            case "003":
                $dbField['status'] = "Angemeldet";
                $dbField['status_categorie'] = "new";
                break;
            case "005":
                $dbField['status'] = "Transport";
                $dbField['status_categorie'] = "in_progress";
                break;
            case "010":
                $dbField['status'] = "Transport";
                $dbField['status_categorie'] = "in_progress";
                break;
            case "011":
                $dbField['status'] = "Zugestellt";
                $dbField['status_categorie'] = "done";
                break;
            case "021":
                $dbField['status'] = "In Zustellung (Heute)";
                $dbField['status_categorie'] = "in_progress";
                break;
            case "057":
                $dbField['status'] = "Zugestellt (Shop)";
                $dbField['status_categorie'] = "done";
                break;
            case "087":
                $dbField['status'] = "Transport";
                $dbField['status_categorie'] = "in_progress";
                break;
            case "160":
                $dbField['status'] = "Transport";
                $dbField['status_categorie'] = "in_progress";
                break;
            default:
                $dbField['status'] = "Unknown";
                $dbField['status_categorie'] = "error";
                break;
        }

        $dbField['activity'] = serialize($Tracking['trackResponse']['shipment'][0]['package'][0]['activity']);

    
        return $dbField;
    }

    public function GetTracking($trackingnumber)
    {
        $Tracking = array();
        $carrier = $this->IdentifyCarrier($trackingnumber);

        $Tracking['carrier'] = $carrier;
        if($carrier == "UPS")
        {
            $Tracking_Data = $this->RequestUPSAPI($trackingnumber);
        }
        elseif($carrier == "DHL")
        {
            $Tracking_Data = $this->RequestDHLAPI($trackingnumber);
        }
        else
        {
            $Tracking_Data = "Carrier not supported";
        }

        $Tracking['data'] = json_decode($Tracking_Data, true);


        return $Tracking;
    }

    private function RequestDHLAPI($trackingnumber)
    {
        global $E_DHL_API_KEY, $E_DHL_API_SECRET;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api-eu.dhl.com/track/shipments?trackingNumber=".$trackingnumber,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "DHL-API-Key: ".$E_DHL_API_KEY
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }


    private function RequestUPSAPI($trackingnumber)
    {
        global $E_UPS_CLIENT_ID, $E_UPS_CLIENT_SECRET;
        // UPS API Oauth GenerateToken

        /**
         * Requires libcurl
         */

        $curl = curl_init();

        //$payload = "grant_type=authorization_code&code=string&redirect_uri=string&code_verifier=string";
        $payload = "grant_type=client_credentials";

        curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic " . base64_encode($E_UPS_CLIENT_ID.":".$E_UPS_CLIENT_SECRET)
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_URL => "https://onlinetools.ups.com/security/v1/oauth/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
        //echo "cURL Error #:" . $error;
        } else {
        //echo "debug-Response oAuthToken: ".$response;
        }

        $response = json_decode($response, true);

        $query = array(
        "locale" => "de_DE",
        "returnSignature" => "false",
        "returnMilestones" => "false",
        "returnPOD" => "false"
        );

        $curl = curl_init();

        curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $response['access_token'],
            "transId: " . uniqid(),
            "transactionSrc: testing"
        ],
        CURLOPT_URL => "https://onlinetools.ups.com/api/track/v1/details/" . $trackingnumber . "?" . http_build_query($query),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
        //echo "cURL Error #:" . $error;
        } else {
        //echo $response;
        }

        return $response;
    }

    /*
    private function RequestUPSAPI($trackingnumber)
    {
        global $E_UPS_CLIENT_ID, $E_UPS_CLIENT_SECRET;

        // UPS OAuth Token URL
        $tokenUrl = "https://onlinetools.ups.com/security/v1/oauth/token";
        
        // UPS Tracking API URL
        $trackingUrl = "https://onlinetools.ups.com/track/v1/details/{$trackingnumber}";

        // Step 1: Obtain OAuth token
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "grant_type" => "client_credentials",
            "client_id" => $E_UPS_CLIENT_ID,
            "client_secret" => $E_UPS_CLIENT_SECRET,
        ]));

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);

        $tokenResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            return "Error: " . curl_error($ch);
        }

        $tokenData = json_decode($tokenResponse, true);
        var_dump($tokenData);
        $accessToken = $tokenData['access_token'];

        curl_close($ch);

        // Step 2: Use OAuth token to fetch tracking info
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $trackingUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json",
            "transId: ".uniqid(), // Replace 'your_transaction_id' with your own unique transaction ID
            "transactionSrc: tracking" // Replace 'your_source_name' with your source name
        ]);

        $trackingResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            return "Error: " . curl_error($ch);
        }

        $trackingData = json_decode($trackingResponse, true);
        
        curl_close($ch);

        // Return the tracking data
        return $trackingData;
    }
    */

    public function IdentifyCarrier($trackingnumber)
    {
        // Identify carrier by tracking number
        // Return carrier name

        // Check if tracking number is UPS
        if(substr($trackingnumber, 0, 2) == "1Z")
        {
            return "UPS";
        }
        else
        {
            return "DHL";
        }
    }

}

?>