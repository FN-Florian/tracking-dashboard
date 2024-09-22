<?php
if($G_id == "" || $G_id == 0)
{
    // Eigene Pakete anzeigen
    $sql = "SELECT * FROM parcels WHERE owner_id = '".$S_userID."'";
}
else
{
    // Gruppen Pakete anzeigen
    $sql = "SELECT 
            p.*
            FROM parcels p
            INNER JOIN parcel_in_group pig ON p.parcel_id = pig.parcel_id
            WHERE pig.group_id = ".$G_id;
}

$sql .= " ORDER BY last_tracking_update DESC";


?>
<h2>Dashboard (<?php echo $usersClass->GetGroupNameById($G_id); ?>)</h2>

<div class="text-end">
    <a href="/0/parcel.html" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> 
        Neues Paket anlegen
    </a>
</div>
<div class="mb-2"></div>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Paket</th>
                <th>Status</th>
                <th>Letzte Aktuallisierung</th>

                <?php

                if($G_id > 0)
                {

                    // CustomFields with on_dashboard = 1
                    $CustomFieldSql = "
                    SELECT 
                    field_id,
                    field_name,
                    field_type,
                    value
                    FROM View_parcels_custom_fields 
                    WHERE on_dashboard = 1 AND groupID = '".$G_id."'
                    GROUP BY field_id
                    ";

                    $CustomFieldResult = $mysqli->query($CustomFieldSql);

                    foreach($CustomFieldResult as $CustomFieldRow)
                    {
                        echo "<th>".$CustomFieldRow['field_name']."</th>";
                    }

                }
                ?>

                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php

            $result = $mysqli->query($sql);

            $i = 0;
            foreach($result as $row)
            {
                echo "
                <tr>
                    <td>
                        <b>".$row['name']."</b><br>
                        ".$row['trackingnumber']."
                        <br>
                        <small>".$row['carrier']."</small>
                    </td>
                    <td>"; 

                        if($row['trackingstatus_categorie'] == "new")
                        {
                            echo "
                            <span class='btn btn-secondary'>".$row['trackingstatus']."</span>
                            ";
                        }
                        elseif($row['trackingstatus_categorie'] == "in_progress")
                        {
                            echo "
                            <span class='btn btn-warning'>".$row['trackingstatus']."</span>
                            ";
                        }
                        elseif($row['trackingstatus_categorie'] == "done")
                        {
                            echo "
                            <span class='btn btn-success'>".$row['trackingstatus']."</span>
                            ";
                        }
                        elseif($row['trackingstatus_categorie'] == "error")
                        {
                            echo "
                            <span class='btn btn-danger'>".$row['trackingstatus']."</span>
                            ";
                        }
                        elseif($row['trackingstatus_categorie'] == "")
                        {
                            echo "
                            <span class='btn btn-light'>Kein Tracking</span>
                            ";
                        }
                    
                    echo "
                    </td>
                    <td>";
                    if($row['last_tracking_update'] == "")
                    {
                        echo "-";
                    }
                    else
                    {
                        echo date('d.m.Y H:i', strtotime($row['last_tracking_update']));
                    }
                    echo "</td>
                    ";

                    if($G_id > 0)
                    {
                        // CustomFields with on_dashboard = 1
                        foreach($CustomFieldResult as $CustomFieldRow)
                        {
                            echo "<td>";
                            if($CustomFieldRow['parcel_id'] == $row['parcel_id'])
                            {
                                echo $CustomFieldRow['value'];
                            }
                            echo "</td>";
                        }

                    }

                    echo"
                    <td>
                        <a href='/".$row['parcel_id']."/parcel.html' class='btn btn-primary'>
                        <i class='fa-solid fa-info-circle'></i> 
                        Details
                        </a>
                    </td>
                </tr>
                ";
                $i++;
            }


            ?>
        </tbody>
    </table>
    <?php
    if($i == 0)
    {
        echo "<div class='alert alert-info'>Keine Sendungen vorhanden</div>";
    }

    ?>
</div>