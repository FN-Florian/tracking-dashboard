<?php

class usersClass{

    public function UserLogin($email, $password)
    {
        global $mysqli;

        // Prüfen ob $email eine E-Mail-Adresse ist
        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            return false;
        }

        // Abrufen des Benutzers anhand der E-Mail-Adresse
        $sql = "SELECT password FROM users WHERE email = '".$email."' AND status > 0";
        $result = $mysqli->query($sql);
        $user = $result->fetch_assoc();

        if($user == null)
        {
            // Benutzer nicht gefunden
            return false;
        }
        // Überprüfen, ob das Passwort korrekt ist
        if(password_verify($password, $user['password']))
        {
            // Passwort ist korrekt
            return true;
        }
        else
        {
            // Passwort ist falsch
            return false;
        }

    }

    /*
    * Benutzer erstellen
    * @param string $email
    * @return array (userID, inviteCode)
    */
    public function CreateUser($email)
    {
        global $mysqli;

        // Prüfen ob $email eine E-Mail-Adresse ist
        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            return false;
        }

        // Prüfen ob E-Mail-Adresse bereits vorhanden ist
        $sql = "SELECT id FROM users WHERE email = '".$email."'";
        $result = $mysqli->query($sql);
        $user = $result->fetch_assoc();

        if($user != null)
        {
            // E-Mail-Adresse bereits vorhanden
            return false;
        }

        // Benutzer erstellen
        $inviteCode = uniqid();

        $sql = "INSERT INTO users (email, inviteCode) VALUES ('".$email."', '".$inviteCode."')";
        $mysqli->query($sql);

        $userID = $mysqli->insert_id;

        return array('userID' => $userID, 'inviteCode' => $inviteCode);
    }

    public function GetGroupNameById($groupID)
    {
        if($groupID == 0 || $groupID == "" || $groupID == null)
        {
            return "Eigene Pakete";
        }

        global $mysqli;

        $sql = "SELECT name FROM groups WHERE id = '".$groupID."'";
        $result = $mysqli->query($sql);
        $group = $result->fetch_assoc();

        if($group == null)
        {
            return "";
        }
        else
        {
            return $group['name'];
        }
    }

    public function ChangePassword($userID, $newPassword)
    {
        global $mysqli;

        if($userID > 0){}else{return false;}

        try
        {
            // Passwort verschlüsseln
            $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Passwort ändern
            $sql = "UPDATE users SET password = '".$newPassword."' WHERE id = '".$userID."'";
            $mysqli->query($sql);

            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
    }

    public function GetUserIDByEmail($email)
    {
        global $mysqli;

        // Prüfen ob $email eine E-Mail-Adresse ist
        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            return 0;
        }

        $sql = "SELECT id FROM users WHERE email = '".$email."'";
        $result = $mysqli->query($sql);
        $user = $result->fetch_assoc();

        if($user == null)
        {
            return 0;
        }
        else
        {
            return $user['id'];
        }
    }

    public function GetUserIDByInviteCode($inviteCode)
    {
        global $mysqli;

        // $inviteCode nur aus Zahlen und Buchstaben bestehen
        if(!ctype_alnum($inviteCode))
        {
            return 0;
        }

        $sql = "SELECT id FROM users WHERE inviteCode = '".$inviteCode."' AND status = 0";
        $result = $mysqli->query($sql);
        $user = $result->fetch_assoc();

        if($user == null)
        {
            return 0;
        }
        else
        {
            return $user['id'];
        }
    }

}

?>