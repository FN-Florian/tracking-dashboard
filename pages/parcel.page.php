<?php

$parcel_id = $G_id;


if($parcel_id > 0)
{
    $sql = "
    SELECT DISTINCT p.*
    FROM parcels p
    LEFT JOIN parcel_in_group pig ON p.parcel_id = pig.parcel_id
    LEFT JOIN user_in_group uig ON pig.group_id = uig.group_id
    WHERE (p.owner_id = ".$S_userID."
    OR uig.user_id = ".$S_userID.")
    AND p.parcel_id = ".$parcel_id."
    ";

    $result = $mysqli->query($sql);
    $parcel = $result->fetch_assoc();

    if($parcel == null)
    {
        echo "<div class='alert alert-danger'>Keine Berechtigung</div>";
        exit;
    }

    
    if($_POST['retrack'])
    {
        $TrackingData = $carrierClass->GetTracking($parcel['trackingnumber']);

        if($carrierClass->WriteTrackingUpdate($parcel['trackingnumber'], $TrackingData))
        {
            echo "
                <div class='alert alert-success'>Tracking wurde erfolgreich aktualisiert</div>
                <meta http-equiv='refresh' content='2.0; URL=/".$parcel_id."/parcel.html'>
                ";
        }
        else
        {
            echo "<div class='alert alert-danger'>Tracking konnte nicht aktualisiert werden</div>";
        }
    }

    if($_POST['add_to_group'])
    {
        $group_id = $_POST['group'];

        if($group_id > 0)
        {
            $sql = "SELECT * FROM parcel_in_group WHERE group_id = ".$group_id." AND parcel_id = ".$parcel_id;
            $result = $mysqli->query($sql);
            if($result->num_rows == 0) {
                $sql = "INSERT INTO parcel_in_group (parcel_id, group_id) VALUES (".$parcel_id.", ".$group_id.")";
                $mysqli->query($sql);

                echo "<div class='alert alert-success'>Paket wurde erfolgreich für die Gruppe freigegeben</div>";
            }
            else
            {
                echo "<div class='alert alert-info'>Paket ist bereits für die Gruppe freigegeben</div>";
            }
        }
        else
        {
            echo "<div class='alert alert-danger'>Gruppe wählen</div>";
        }
    }

    //var_dump($parcel);

    ?>
    <h2>Paket Details - <?php echo $parcel['name']; ?></h2>

    <?php

    if($_POST['save_parcel_edit'])
    {
        $name = preg_replace("/[^a-zA-Z0-9öäüß\_\- ]/", "", $_POST['name']);
        $carrier = preg_replace("/[^a-zA-Z]/", "", $_POST['carrier']);

        // Custom Fields save (Key: custom_field_1, custom_field_2, custom_field_3, ...)

        foreach($_POST as $key => $value)
        {
            if(strpos($key, "custom_field_") !== false)
            {
                $field_id = str_replace("custom_field_", "", $key);
                $field_id = preg_replace("/[^0-9]/", "", $field_id);
                $value = preg_replace("/[^a-zA-Z0-9-_ ]/", "", $value);
                $value = $mysqli->real_escape_string($value);

                // Check if entry exist in custom_fields_value for parcel_id and field_id
                $sql = "
                SELECT * FROM custom_fields_value WHERE parcel_id = ".$parcel_id." AND field_id = ".$field_id."
                ";
                $result = $mysqli->query($sql);

                if($result->num_rows > 0)
                {
                    $sql = "
                    UPDATE custom_fields_value
                    SET value = '".$value."'
                    WHERE parcel_id = ".$parcel_id."
                    AND field_id = ".$field_id."
                    ";
                }
                else
                {
                    $sql = "
                    INSERT INTO custom_fields_value (parcel_id, field_id, value)
                    VALUES (".$parcel_id.", ".$field_id.", '".$value."')
                    ";
                }


                $mysqli->query($sql);
            }
        }

        $sql = "
        UPDATE parcels
        SET name = '".$name."'
        WHERE parcel_id = ".$parcel_id."
        ";

        $mysqli->query($sql);

        echo "<div class='alert alert-success'>Paket wurde erfolgreich bearbeitet</div>
        <meta http-equiv='refresh' content='2.0; URL=/".$parcel_id."/parcel.html'>
        ";
    }

    if($_POST['edit'])
    {
        ?>
        <h3>Sendung bearbeiten</h3>

        <form action='/<?php echo $parcel_id; ?>/parcel.html' method='post'>
            <div class='form-group'>
                <label for='name'>Name</label>
                <input type='text' class='form-control' name='name' id='name' value='<?php echo $parcel['name']; ?>' required>
            </div>

            <?php
            // Abrufen der Custom Fields zum Bearbeiten, nur CustomFields anzeigen die zu Gruppen gehören in denen der User ist
            $sql = "
            SELECT
            field_id,
            field_name,
            field_type,
            value
            FROM View_parcels_custom_fields
            WHERE parcel_id = ".$parcel_id."
            AND groupID IN (SELECT group_id FROM user_in_group WHERE user_id = ".$S_userID.")
            ";

            $result = $mysqli->query($sql);

            foreach($result as $row)
            {
                if($row['field_id'] > 0)
                {
                    echo "
                    <div class='form-group'>
                        <label for='custom_field_".$row['field_id']."'>".$row['field_name']."</label>
                        <input type='text' class='form-control' name='custom_field_".$row['field_id']."' id='custom_field_".$row['field_id']."' value='".$row['value']."'>
                    </div>
                    ";
                }
            }

            ?>

            <br><br>
            <input type='submit' name='save_parcel_edit' value='Speichern' class='form-control btn btn-lg btn-primary'>
        </form>

        <?php

    }
    else
    {

    ?>
        <div class='row'>
            <div class='col-md-4'>
                <form action="/<?php echo $parcel_id; ?>/parcel.html" method="post">
                    <div class="input-group">
                        <select class="form-control form-control-sm" name="group" id="group">
                            <option value="">Gruppe wählen</option>
                            <?php

                            $sql = "SELECT 
                                g.id,
                                g.name
                            FROM groups g
                            INNER JOIN user_in_group uig ON g.id = uig.group_id
                            WHERE uig.user_id = ".$S_userID."
                            AND NOT EXISTS (
                                SELECT 1 
                                FROM parcel_in_group pig 
                                WHERE pig.group_id = g.id 
                                AND pig.parcel_id = ".$parcel_id."
                            )";

                            $result = $mysqli->query($sql);

                            while($group = $result->fetch_assoc())
                            {
                                echo "<option value='".$group['id']."'>".$group['name']."</option>";
                            }
                            ?>
                        </select>
                        <input type="submit" name="add_to_group" value="Für Gruppe freigeben" class="form-control form-control-sm btn btn-sm btn-primary">
                    </div>
                </form>
            </div>
            <div class='col-md-4'>
                <div class='text-end'>

                    <form action='/<?php echo $parcel_id; ?>/parcel.html' method='post'>
                        <div class='input-group'>
                            <input type='submit' name='retrack' value='Tracking aktuallisieren' class='form-control btn btn-sm btn-primary' <?php if($parcel['carrier'] != "DHL" && $parcel['carrier'] != "UPS" ){echo "disabled";} ?>>
                            <input type='submit' name='edit' value='Bearbeiten' class='form-control btn btn-sm btn-outline-primary'>
                        </div>
                    </form>

                </div>
            </div>
            <div class='col-md-4'>

            </div>
        </div>

        <div class='mb-2'></div>

        <div class="row">
            <div class="col-md-8">
                <h3>Allgemein</h3>
                <table class="table table-striped">
                    <tr>
                        <td>Name</td>
                        <td><?php echo $parcel['name']; ?></td>
                    </tr>
                    <tr>
                        <td>Carrier</td>
                        <td><?php echo $parcel['carrier']; ?></td>
                    </tr>
                    <tr>
                        <td>Trackingnummer</td>
                        <td><?php echo $parcel['trackingnumber']; ?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>
                        
                        <?php

                        if($parcel['trackingstatus_categorie'] == "new")
                        {
                            echo "
                            <span class='btn btn-sm btn-secondary'>".$parcel['trackingstatus']."</span>
                            ";
                        }
                        elseif($parcel['trackingstatus_categorie'] == "in_progress")
                        {
                            echo "
                            <span class='btn btn-sm btn-warning'>".$parcel['trackingstatus']."</span>
                            ";
                        }
                        elseif($parcel['trackingstatus_categorie'] == "done")
                        {
                            echo "
                            <span class='btn btn-sm btn-success'>".$parcel['trackingstatus']."</span>
                            ";
                        }
                        elseif($parcel['trackingstatus_categorie'] == "error")
                        {
                            echo "
                            <span class='btn btn-sm btn-danger'>".$parcel['trackingstatus']."</span>
                            ";
                        }
                        elseif($parcel['trackingstatus_categorie'] == "")
                        {
                            echo "
                            <span class='btn btn-sm btn-light'>Kein Tracking</span>
                            ";
                        }

                        ?>


                        </td>
                    </tr>
                    <tr>
                        <td>Letzte Tracking Aktuallisierung</td>
                        <td><?php echo date("d.m.Y H:i", strtotime($parcel['last_tracking_update'])); ?></td>
                    </tr>
                    <tr>
                        <td>Erstellt am</td>
                        <td><?php echo date("d.m.Y H:i", strtotime($parcel['created_date'])); ?></td>
                    </tr>
                </table>

                <h3>Custom Fields</h3>  
                <?php

                $sql = "
                SELECT 
                parcel_id,
                groupID,
                field_id,
                on_dashboard,
                field_type,
                field_name,
                value
                FROM View_parcels_custom_fields
                WHERE parcel_id = ".$parcel_id." AND groupID IN (SELECT group_id FROM user_in_group WHERE user_id = ".$S_userID.")
                ";

                $result = $mysqli->query($sql);

                echo "
                    <table class='table table-striped'>";

                foreach($result as $row)
                {
                    echo "
                        <tr>
                            <td>".$row['field_name']."</td>
                            <td>".$row['value']."</td>
                        </tr>
                    ";
                }
                echo "
                    </table>";

                ?>
            </div>
            <div class="col-md-4">
                <h3>Tracking</h3>
                <?php

                $TrackingData = unserialize($parcel['tracking_details']);

                
                if($parcel['carrier'] == "UPS")
                {
                    foreach($TrackingData as $Tracking)
                    {
                        echo "
                        <div class='card'>
                            <div class='card-body'>
                                <span class='text-secondary'>
                                ";
                                // Date in Format 20240920 umwandeln in 20.09.2024
                                $date = date("d.m.Y", strtotime($Tracking['date']));
                                $time = date("H:i", strtotime($Tracking['time']));

                                echo $date." ".$time." - ".$Tracking['location']['address']['city']." (".$Tracking['location']['address']['countryCode'].")";

                                echo"
                                </span>
                                <br>
                                ".$Tracking['status']['description']."

                            </div>
                        </div>
                        <div class='mb-3'></div>
                        ";
                    }

                }
                elseif($parcel['carrier'] == "DHL")
                {
                    foreach($TrackingData as $Tracking)
                    {
                        echo "
                        <div class='card'>
                            <div class='card-body'>
                                <span class='text-secondary'>
                                ";
                                // Date in Format 2024-08-30T11:14:00 umwandeln in 30.08.2024 11:14
                                $date = date("d.m.Y H:i", strtotime($Tracking['timestamp']));

                                echo $date." - ".$Tracking['location']['address']['addressLocality'];

                                echo"
                                </span>
                                <br>
                                ".$Tracking['statusCode']." <span class='text-secondary'>(".$Tracking['description'].")</span>

                            </div>
                        </div>
                        <div class='mb-3'></div>
                        ";
                    }
                }

                ?>

            </div>
        </div>
    <?php
    }
}
else
{
    // Add new parcel

    if($_POST['submit'])
    {
        $carrier        = preg_replace("/[^a-zA-Z]/", "", $_POST['carrier']);
        $trackingnumber = preg_replace("/[^a-zA-Z0-9]/", "", $_POST['trackingnumber']);
        $name           = preg_replace("/[^a-zA-Z0-9öäüß\_\- ]/", "", $_POST['name']);
        
        $name = $mysqli->real_escape_string($name);


        $parcel_id = $carrierClass->AddParcel($carrier, $trackingnumber, $name);

        var_dump($parcel_id);

        if($carrier == "DHL" || $carrier == "UPS")
        {
            $TrackingData = $carrierClass->GetTracking($trackingnumber);

            //var_dump($TrackingData);

            if($carrierClass->WriteTrackingUpdate($trackingnumber, $TrackingData))
            {
                if($parcel_id > 0)
                {
                    echo "<div class='alert alert-success'>Paket wurde erfolgreich hinzugefügt.</div>";
                    echo "
                    <meta http-equiv='refresh' content='0.0; URL=/".$parcel_id."/parcel.html'>
                    ";
                }
                else
                {
                    echo "<div class='alert alert-danger'>Paket konnte nicht hinzugefügt werden.</div>";
                }
            }
            else
            {
                echo "<div class='alert alert-danger'>Paket konnte nicht hinzugefügt werden.</div>";
            }
        }
        else
        {
            if($parcel_id > 0)
            {
                echo "<div class='alert alert-success'>Paket wurde erfolgreich hinzugefügt.</div>
                <meta http-equiv='refresh' content='0.0; URL=/".$parcel_id."/parcel.html'>
                ";
            }
            else
            {
                echo "<div class='alert alert-danger'>Paket konnte nicht hinzugefügt werden.</div>";
            }
        }
    }

    ?>

    <h2>Neues Paket anlegen</h2>
    <form action='/0/parcel.html' method="post">
        <div class="form-group">
            <label for="carrier">Carrier</label>
            <select class="form-control" name="carrier" id="carrier">
                <option value="">Carrier wählen</option>
                <option value="DHL">DHL</option>
                <option value="UPS">UPS</option>
                <option value="other">Sonstige</option>
            </select>
        </div>
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" name="name" id="name" placeholder="Name" required>
        </div>
        <div class="form-group">
            <label for="trackingnumber">Trackingnummer</label>
            <input type="text" class="form-control" name="trackingnumber" id="trackingnumber" placeholder="Trackingnummer" required>
        </div>
        <br><br>
        <input type="submit" name="submit" value="Paket hinzufügen" class="form-control btn btn-lg btn-primary">
    </form>

    <?php
}

?>