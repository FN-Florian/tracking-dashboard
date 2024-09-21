
<div class='container'>
    <div class='row'>
        <div class='col-md-12'>
            <?php
            
            if($G_subpage == "invite")
            {
                $groupID = $G_id;
                
                if($groupID > 0)
                {
                    ?>
                    <h2>Benutzer Einladung</h2>
                    <?php

                    if(isset($_POST['invite']))
                    {
                        $email = $_POST['email'];

                        
                        $userID = $usersClass->GetUserIDByEmail($email);

                        if($userID > 0)
                        {
                            // Benutzer besteht bereits
                            // Hinzufügen zu Gruppe
                            $sql = "INSERT INTO user_in_group (user_id, group_id) VALUES ('".$userID."', '".$groupID."')";
                            $mysqli->query($sql);
                        }
                        else
                        {
                            // Benutzer nicht vorhanden, Benutzer erstellen und anschließend einladen
                            $userData = $usersClass->CreateUser($email);
                            $mailClass->SendInviteMail($email, $userData['inviteCode']);
                            $userID = $userData['userID'];
                            
                            $sql = "INSERT INTO user_in_group (user_id, group_id) VALUES ('".$userID."', '".$groupID."')";
                            $mysqli->query($sql);
                        }

                        echo "<div class='alert alert-success'>Benutzer erfolgreich eingeladen</div>";
                    }


                    ?>
                    <p>
                        Gib hier die E-Mail Adresse des Benutzers ein, den du in die Gruppe einladen möchtest. 
                        Wenn der Benutzer bereits existiert, wird er hinzugefügt. Sollte er noch nicht existieren, wird er eingeladen.
                    </p>

                    <form action='/invite/<?php echo $groupID; ?>/group.html' method='post'>
                        <label for='email'>E-Mail Adresse</label><br>
                        <input type='text' class='form-control' name='email' placeholder='E-Mail Adresse' required />
                        <br>
                        <input type='submit' class='form-control btn btn-primary' name='invite' value='Einladen' />
                    </form>

                    <?php
                }
                else
                {
                    echo "<div class='alert alert-danger'>Gruppe nicht gefunden</div>";
                }
            }
            elseif($G_subpage == "create")
            {
                ?>
                <h2>Gruppe erstellen</h2>
                <?php

                if(isset($_POST['create']))
                {
                    $name = $_POST['name'];

                    //$name nur Buchstaben und ein Minus zulassen
                    $name = preg_replace("/[^a-zA-Z0-9-]/", "", $name);

                    $name = $mysqli->real_escape_string($name);

                    $sql = "INSERT INTO groups (name, created_date, owner_id) VALUES ('".$name."', NOW(), '".$S_userID."')";
                    $mysqli->query($sql);
                    $groupID = $mysqli->insert_id;

                    $sql = "INSERT INTO user_in_group (user_id, group_id) VALUES ('".$S_userID."', '".$groupID."')";
                    $mysqli->query($sql);

                    echo "
                        <div class='alert alert-success'>Gruppe erfolgreich erstellt</div>
                        <meta http-equiv='refresh' content='1; URL=/group.html'>
                        ";
                }

                ?>
                <p>
                    Gib hier den Namen der Gruppe ein, die du erstellen möchtest.
                </p>

                <form action='/create/0/group.html' method='post'>
                    <label for='name'>Gruppenname</label><br>
                    <input type='text' class='form-control' name='name' placeholder='Gruppenname' required />
                    <br>
                    <input type='submit' class='form-control btn btn-primary' name='create' value='Erstellen' />
                </form>

                <?php
            }
            elseif($G_subpage == "members")
            {
                $groupID = $G_id;

                if($groupID > 0)
                {
                    $sql = "SELECT name, owner_id FROM groups WHERE id = '".$groupID."'";
                    $result = $mysqli->query($sql);
                    $groupData = $result->fetch_assoc();

                    if ($groupData['owner_id'] == $S_userID) 
                    {
                        ?>
                        <h2>Mitglieder <small>(Gruppe: <?php echo $groupData['name']; ?>)</small></h2>
                        <?php
                        if(isset($_POST['remove_user']))
                        {
                            $userID = $_POST['user_id'];

                            if($userID > 0)
                            {
                                $sql = "DELETE FROM user_in_group WHERE user_id = '".$userID."' AND group_id = '".$groupID."'";
                                $mysqli->query($sql);

                                echo "<div class='alert alert-success'>Mitglied erfolgreich entfernt</div>";
                            }
                            else
                            {
                                echo "<div class='alert alert-danger'>Mitglied nicht gefunden</div>";
                            }
                        }

                        $sql = "SELECT 
                            uig.user_id, 
                            u.email 
                        FROM 
                            user_in_group uig 
                        INNER JOIN 
                            users u ON uig.user_id = u.id 
                        WHERE 
                            uig.group_id = '".$groupID."'
                        ";
                        $result = $mysqli->query($sql);

                        if($result->num_rows > 0)
                        {
                            echo "<table class='table table-striped table-hover'>";
                            echo "<tr>";
                            echo "<th>E-Mail Adresse</th>";
                            echo "<th></th>";
                            echo "</tr>";

                            foreach($result as $row)
                            {
                                echo "<tr>";
                                echo "<td>".$row['email']."</td>";
                                echo "<td>
                                    <form action='/members/".$groupID."/group.html' method='post'>
                                        <input type='hidden' name='user_id' value='".$row['user_id']."' />
                                        <input type='submit' class='btn btn-danger' name='remove_user' value='Entfernen' />
                                    </form>
                                </td>";
                                echo "</tr>";
                            }

                            echo "</table>";
                        }
                        else
                        {
                            echo "<div class='alert alert-info'>Keine Mitglieder vorhanden</div>";
                        }
                    } 
                    else 
                    {
                        echo "<div class='alert alert-danger'>Du bist nicht berechtigt diese Aktion durchzuführen</div>";
                    }
                }
                else
                {
                    echo "<div class='alert alert-danger'>Gruppe nicht gefunden</div>";
                }
                

            }
            elseif($G_subpage == "customfields")
            {
                $groupID = $G_id;

                if($groupID > 0)
                {
                    if($_POST['add_field'])
                    {
                        $fieldID = $_POST['field_id'];
                        $field_name = $_POST['field_name'];
                        $field_type = $_POST['field_type'];
                        $on_dashboard = $_POST['on_dashboard'];

                        $field_name = preg_replace("/[^a-zA-Z0-9_ ]/", "", $field_name);

                        $field_name = $mysqli->real_escape_string($field_name);
                        $field_type = $mysqli->real_escape_string($field_type);

                        if($on_dashboard == 0 || $on_dashboard == 1){}else{$on_dashboard = 0;}

                        if($fieldID > 0)
                        {
                            $sql = "UPDATE 
                                        custom_fields 
                                    SET 
                                        field_name = '".$field_name."', 
                                        field_type = '".$field_type."', 
                                        on_dashboard = '".$on_dashboard."' 
                                    WHERE field_id = '".$fieldID."'";
                            $mysqli->query($sql);

                            echo "<div class='alert alert-success'>Feld erfolgreich bearbeitet</div>";
                        }
                        else
                        {
                            $sql = "INSERT INTO custom_fields (field_name, field_type, on_dashboard) VALUES ('".$field_name."', '".$field_type."', '".$on_dashboard."')";
                            $mysqli->query($sql);
                            $fieldID = $mysqli->insert_id;

                            $sql = "INSERT INTO custom_fields_to_group (field_id, group_id) VALUES ('".$fieldID."', '".$groupID."')";
                            $mysqli->query($sql);

                            echo "<div class='alert alert-success'>Feld erfolgreich hinzugefügt</div>";
                        }
                    }


                    // Prüfen ob $S_userID der Besitzer der Gruppe ist
                    $sql = "SELECT name, owner_id FROM groups WHERE id = '".$groupID."'";
                    $result = $mysqli->query($sql);
                    $group = $result->fetch_assoc();

                    if($group['owner_id'] == $S_userID)
                    {
                        ?>
                        <h2>Benutzerdefinierte Felder <small>(Gruppe: <?php echo $group['name']; ?>)</small></h2>

                        <?php

                        $sql = "SELECT 
                                *
                                FROM 
                                custom_fields cf
                                INNER JOIN custom_fields_to_group cftg ON cftg.field_id = cf.field_id
                                WHERE cftg.group_id = '".$groupID."'";
                        $result = $mysqli->query($sql);

                        if($result->num_rows > 0)
                        {
                            echo "<table class='table table-striped table-hover'>";
                            echo "<tr>";
                            echo "<th>Feldname</th>";
                            echo "<th>Typ</th>";
                            echo "<th>Sichtbar im Dashboard</th>";
                            echo "<th></th>";
                            echo "</tr>";

                            foreach($result as $row)
                            {
                                echo "<tr>";
                                    echo "<td>".$row['field_name']."</td>";
                                    echo "<td>".$row['field_type']."</td>";
                                    echo "<td>".($row['on_dashboard'] == 1 ? "Ja" : "Nein")."</td>";
                                    echo "<td>
                                        <form action='/customfields/".$groupID."/group.html#field_edit' method='post'>
                                            <input type='hidden' name='field_id' value='".$row['field_id']."' />
                                            <input type='submit' class='btn btn-danger' name='delete_field' value='Löschen' />
                                            <input type='submit' class='btn btn-warning' name='edit_field' value='Bearbeiten' />
                                        </form>
                                        
                                    </td>";
                                echo "</tr>";
                            }

                            echo "</table>";
                        }
                        else
                        {
                            echo "<div class='alert alert-info'>Keine benutzerdefinierten Felder vorhanden</div>";
                        }

                        ?>

                        <div class='mb-3'></div>

                        <?php

                        if($_POST['edit_field'])
                        {
                            $fieldID = $_POST['field_id'];

                            if($fieldID > 0)
                            {
                                $sql = "
                                SELECT 
                                *
                                FROM custom_fields cf 
                                INNER JOIN custom_fields_to_group cftg ON cf.field_id = cftg.field_id 
                                WHERE cf.field_id = '".$fieldID."' AND cftg.group_id = '".$groupID."'";
                                $result = $mysqli->query($sql);
                                $field = $result->fetch_assoc();

                                $field_name = $field['field_name'];
                                $field_type = $field['field_type'];
                                $on_dashboard = $field['on_dashboard'];
                            
                            echo "
                            <h3 id='field_edit'>Feld bearbeiten</h3>
                            ";


                            }
                            else
                            {
                                echo "<div class='alert alert-danger'>Feld nicht gefunden</div>";
                            }
                        }
                        else
                        {
                            $fieldID = 0;
                            $field_name = "";
                            $field_type = "";
                            $on_dashboard = 0;

                            echo "
                            <h3>Feld hinzufügen</h3>
                            ";
                        }
                        ?>
                        <form action='/customfields/<?php echo $groupID; ?>/group.html' method='post'>
                            <input type='hidden' name='field_id' value='<?php echo $fieldID; ?>' />
                            <label for='field_name'>Feldname</label><br>
                            <input type='text' class='form-control' name='field_name' placeholder='Feldname' value='<?php echo $field_name;?>' pattern='[a-zA-Z0-9_ ]*' required />
                            <br>
                            <label for='field_type'>Feldtyp</label><br>
                            <select class='form-control' name='field_type'>
                                <option value='' selected disabled>Feldtyp wählen</option>
                                <option value='text' <?php if($field_type == "text"){echo "selected";} ?>>Text</option>
                            </select>
                            <br>
                            <label for='on_dashboard'>Sichtbar im Dashboard</label><br>
                            <select class='form-control' name='on_dashboard'>
                                <option value='1' <?php if($on_dashboard == 1){echo "selected";} ?>>Ja</option>
                                <option value='0' <?php if($on_dashboard == 0){echo "selected";} ?>>Nein</option>
                            </select>
                            <br>
                            <input type='submit' class='form-control btn btn-primary' name='add_field' value='Hinzufügen' />
                        </form>

                        <?php
                    }
                    else
                    {
                        echo "<div class='alert alert-danger'>Du bist nicht berechtigt diese Aktion durchzuführen</div>";
                    }
                }
                else
                {
                    echo "<div class='alert alert-danger'>Gruppe nicht gefunden</div>";
                }
            }
            else
            {


                ?>
                <h2>Gruppen</h2>

                <div class='text-end'>
                    <a href='/create/0/group.html' class='btn btn-primary'><i class='fa-solid fa-plus'></i> Gruppe erstellen</a>
                </div>
                <div class='mb-3'></div>
                <table class="table table-striped table-hover table-responsive">
                    <tr>
                        <th>Gruppenname</th>
                        <th>Gruppenmitglieder</th>
                        <th>Gegründet von</th>
                        <th>Aktionen</th>
                    </tr>
                    <?php
                    $sql = "SELECT 
                        g.id, 
                        g.name, 
                        (SELECT COUNT(uig2.user_id) 
                        FROM user_in_group uig2 
                        WHERE uig2.group_id = g.id) AS user_count,
                        g.owner_id,
                        (SELECT email FROM users WHERE id = g.owner_id) AS owner_email
                    FROM 
                        groups g
                    INNER JOIN 
                        user_in_group uig ON g.id = uig.group_id
                    WHERE 
                        uig.user_id = ".$S_userID."
                    GROUP BY 
                        g.id, g.name;

                    ";
                    $result = $mysqli->query($sql);

                    while($group = $result->fetch_assoc())
                    {
                        echo "<tr>";
                        echo "<td>".$group['name']."</td>";
                        echo "<td>".$group['user_count']."</td>";
                        echo "<td>".$group['owner_email']."</td>";
                        echo "<td>
                            <a href='/invite/".$group['id']."/group.html' class='btn btn-primary'><i class='fa-solid fa-user-plus'></i> Einladen</a>
                            ";

                            if($group['owner_id'] == $S_userID)
                            {
                                echo "<a href='/customfields/".$group['id']."/group.html' class='btn btn-primary'><i class='fa-solid fa-bars'></i> Felder</a>";
                                echo "<a href='/members/".$group['id']."/group.html' class='btn btn-primary'><i class='fa-solid fa-users'></i> Mitglieder</a>";
                            }

                            echo"
                        </td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
                <?php
            }
            ?>
        </div>
    </div>
</div>