<div class='container'>
    <div class='mb-5'></div>
    <div class='row'>
        <div class='col-md-4'>
        </div>
        <div class='col-md-4'>
        
            <?php 

            $activationCode = $G_id;

            if($activationCode != "")
            {
                $userID = $usersClass->GetUserIDByInviteCode($activationCode);

                if($userID > 0)
                {
                    if($_POST['changePassword'])
                    {
                        $P_password = $_POST['password'];
                        $P_password2 = $_POST['password2'];

                        if($P_password == $P_password2)
                        {
                            if($usersClass->ChangePassword($userID, $P_password))
                            {
                                $sql = "UPDATE users SET status = 1 WHERE id = '".$userID."'";
                                $mysqli->query($sql);
                                echo "<div class='alert alert-success'>Passwort erfolgreich geändert - Du kannst dich nun einloggen</div>
                                <meta http-equiv='refresh' content='0; URL=/login.html'>
                                ";

                                header("Location: /login.html");
                            }
                            else
                            {
                                echo "<div class='alert alert-danger'>Fehler beim Ändern des Passworts</div>";
                            }
                        }
                        else
                        {
                            echo "<div class='alert alert-danger'>Passwörter stimmen nicht überein</div>";
                        }
                    }
                    else
                    {
                        echo "<h2>Benutzer Aktivierung</h2><div class='alert alert-info'>Benutzerkonto gefunden - Lege nun ein Passwort fest</div>";

                        ?>

                        <form action='/<?php echo $activationCode; ?>/activation.html' method='post'>
                            <input type='password' class='form-control' name='password' placeholder='Passwort' required />
                            <input type='password' class='form-control' name='password2' placeholder='Passwort wiederholen' required />
                            <input type='submit' class='form-control btn btn-primary' name='changePassword' value='Passwort festlegen' />
                        </form>

                        <?php
                    }
                }
                else
                {
                    echo "<div class='alert alert-danger'>Benutzerkonto nicht gefunden</div>";
                }
            }
            else
            {
                echo "<div class='alert alert-danger'>Aktivierungscode ungültig</div>";
            }


            ?>

        </div> 
        <div class='col-md-4'>
        </div> 
    </div>
</div>