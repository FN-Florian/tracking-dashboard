<h2>Passwort ändern</h2>

<?php

if(isset($_POST['pwchange']))
{
    $oldpassword = $_POST['oldpassword'];
    $newpassword = $_POST['newpassword'];
    $newpassword2 = $_POST['newpassword2'];

    $mysqli;
    $sql = "SELECT * FROM users WHERE id = '".$S_userID."'";
    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();

    if(password_verify($oldpassword, $user['password']))
    {
        if($newpassword == $newpassword2)
        {
            $newPassword = password_hash($newpassword, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = '".$newPassword."' WHERE id = '".$S_userID."'";
            $mysqli->query($sql);

            echo "<div class='alert alert-success'>Passwort erfolgreich geändert</div>";
        }
        else
        {
            echo "<div class='alert alert-danger'>Die neuen Passwörter stimmen nicht überein</div>";
        }
    }
    else
    {
        echo "<div class='alert alert-danger'>Das alte Passwort ist falsch</div>";
    }
}

?>

<form action="/pwchange.html" method="post">
    <label for="oldpassword">Altes Passwort</label><br>
    <input type="password" class="form-control" name="oldpassword" placeholder="Altes Passwort" required />
    <br>
    <label for="newpassword">Neues Passwort</label><br>
    <input type="password" class="form-control" name="newpassword" placeholder="Neues Passwort" required />
    <br>
    <label for="newpassword2">Neues Passwort wiederholen</label><br>
    <input type="password" class="form-control" name="newpassword2" placeholder="Neues Passwort wiederholen" required />
    <br><br>
    <input type="submit" class="form-control btn btn-primary" name="pwchange" value="Passwort ändern" />
</form>