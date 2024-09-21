<div class='container'>
    <div class='row'>
        <div class='col-md-12'>

        <div class='mb-5'></div>

            <div class='row'>
                <div class='col-md-4'>
                </div>
                <div class='col-md-4'>
                    <h2>Login</h2>
                    
                        <?php

                        if($_POST['login'])
                        {
                            $P_email    = $_POST['email'];
                            $P_password = $_POST['password'];

                            if($usersClass->UserLogin($P_email, $P_password))
                            {
                                echo "<div class='alert alert-success'>Anmeldung erfolgreich</div>
                                <meta http-equiv='refresh' content='0; URL=/dashboard.html'>
                                ";

                                $sql = "SELECT id, status FROM users WHERE email = '".$P_email."' AND status > 0";
                                $result = $mysqli->query($sql);
                                $row = $result->fetch_assoc();

                                $_SESSION['status'] = $row['status'];
                                $_SESSION['userID'] = $row['id'];

                                header("Location: /dashboard.html");
                            }
                            else
                            {
                                echo "<div class='alert alert-danger'>Anmeldung fehlgeschlagen</div>";
                            }

                        }


                        ?>

                    <form action='/login.html' method='post'>
                        <label for='email'>E-Mail Adresse</label><br>
                        <input type='text' class='form-control' name='email' placeholder='E-Mail Adresse' required />
                        <br>
                        <label for='password'>Passwort</label><br>
                        <input type='password' class='form-control' name='password' placeholder='Passwort' required />
                        <br><br>
                        <input type='submit' class='form-control btn btn-primary' name='login' value='Anmelden' />
                    </form>
                </div>
                <div class='col-md-4'>
                </div>
            </div>
        </div>
    </div>
</div>