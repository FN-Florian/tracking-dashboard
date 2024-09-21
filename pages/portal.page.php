<?php

if($_POST['tracking'] || $_GET['trackingnumber'])
{
    $trackingnumber = "";
    if(isset($_POST['tracking']))
    {
        $trackingnumber = $_POST['trackingnumber'];
    }
    else
    {
        $trackingnumber = $_GET['trackingnumber'];
    }
    
    if($trackingnumber != "")
    {

        $trackingnumber = $mysqli->real_escape_string($trackingnumber);

        $sql = "
                SELECT 
                    parcel_id,
                    carrier,
                    trackingnumber,
                    trackingstatus,
                    trackingstatus_categorie
                FROM 
                parcels 
                WHERE trackingnumber = '".$trackingnumber."' AND carrier IN ('DHL', 'UPS')
            ";
        $result = $mysqli->query($sql);

        if($result->num_rows > 0)
        {
            $parcelData = $result->fetch_assoc();

            echo "
            <div class='mb-5'></div>
            <div class='container card card-body'>
                <div class='text-center'>
                    <h2>Sendung ".$parcelData['trackingnumber']." (".$parcelData['carrier'].")</h2>

                    <div class='mb-3'></div>

                    ";

                    if($parcelData['trackingstatus_categorie'] == "new")
                    {
                        echo "
                        <span class='btn btn-lg btn-secondary'>".$parcelData['trackingstatus']."</span>
                        ";
                    }
                    elseif($parcelData['trackingstatus_categorie'] == "in_progress")
                    {
                        echo "
                        <span class='btn btn-lg btn-warning'>".$parcelData['trackingstatus']."</span>
                        ";
                    }
                    elseif($parcelData['trackingstatus_categorie'] == "done")
                    {
                        echo "
                        <span class='btn btn-lg btn-success'>".$parcelData['trackingstatus']."</span>
                        ";
                    }
                    elseif($parcelData['trackingstatus_categorie'] == "error")
                    {
                        echo "
                        <span class='btn btn-lg btn-danger'>".$parcelData['trackingstatus']."</span>
                        ";
                    }
                    elseif($parcelData['trackingstatus_categorie'] == "")
                    {
                        echo "
                        <span class='btn btn-lg btn-light'>Kein Tracking</span>
                        ";
                    }


                    echo "
                </div>
            </div>
            ";



        }
        else
        {
            echo "
            <div class='alert alert-danger'>Sendung nicht gefunden</div>
            ";
        }
    }
    else
    {
        echo "
        <div class='alert alert-danger'>Keine Trackingnummer angegeben!</div>
        <meta http-equiv='refresh' content='2; URL=/'>
        ";
    }
    
}

?>