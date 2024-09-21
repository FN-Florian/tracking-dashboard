<?php
error_reporting(E_ERROR | E_PARSE);

session_start();

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

/*
echo "debug session: <br>";
var_dump($_SESSION);
echo "<br><br>";
echo"debug get: <br>";
var_dump($_GET);
*/

$S_userID = 0;
$S_status = 0;

if(isset($_SESSION['userID']))
{
    $S_userID = $_SESSION['userID'];
}

if(isset($_SESSION['status']))
{
    $S_status = $_SESSION['status'];
}

$G_page = "";
if (isset($_GET['page'])) {
    $G_page = $_GET['page'];
}
else 
{
    $G_page = "dashboard";
}

$G_id = "";
if (isset($_GET['id'])) {
    $G_id = $_GET['id'];
}

$G_subpage = "";
if (isset($_GET['subpage'])) {
    $G_subpage = $_GET['subpage'];
}
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

    <?php

    if($S_userID == 0)
    {

        if($G_page == "activation")
        {
            include("pages/".$G_page.".page.php");
        }
        else
        {
            if($G_page == "login")
            {
                include("pages/login.page.php");
            }
            elseif($G_page == "portal")
            {
                include("pages/portal.page.php");
            }
            else
            {
                ?>
                <div class="mb-5"></div>
                <div class='row'>
                    <div class='col-md-1'>
                    </div>
                    <div class='col-md-4'>

                        <div class='card'>
                            <div class='card-body text-center align-middle'>
                                <div class='mb-2'></div>
                                <p>
                                    Du wurdest eingeladen um Sendungen zu verfolgen und zu verwalten. <br>
                                    Dann melde dich hier an.
                                </p>
                                <a href='/login.html' class='btn btn-lg btn-outline-primary'>
                                    <i class='fa-solid fa-sign-in'></i> 
                                    Anmelden
                                </a>
                                <div class='mb-2'></div>
                            </div>
                        </div>

                    </div>
                    <div class='col-md-2'>
                    </div>
                    <div class='col-md-4'>

                        <div class='card'>
                            <div class='card-body text-center align-middle'>
                                <div class='mb-2'></div>

                                <p>
                                    Wenn du eine Sendungsnummer erhalten hast,<br>
                                    kannst du diese hier verfolgen.
                                </p>
                                <form action='/portal.html' method="post">
                                    <input type='text' name='trackingnumber' class='form-control' placeholder="Trackingnummer" >
                                    <div class='mb-2'></div>
                                    <input type='submit' name='tracking' class='form-control btn btn-lg btn-primary' value='Suchen'>
                                </form>

                                <div class='mb-2'></div>
                            </div>
                        </div>

                    </div>
                    <div class='col-md-1'>
                    </div>
                </div>



                <?php
            }

            /*
            if($G_page != "login")
            {
                echo "<meta http-equiv='refresh' content='0; URL=/login.html'>";
            }
            else
            {
                include("pages/login.page.php");
            }
            */
        }

    }
    else
    {

    ?>

    <!-- Your HTML content here -->
    <div class="mb-5"></div>

    <nav class="navbar navbar-expand-lg bg-body-tertiary container">
        <div class='container-fluid'>
            <a class="navbar-brand" href="/"><?php echo $ProjectName; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav me-auto">
                    <li class='nav-item dropdown'>
                        <a class='nav-link dropdown-toggle <?php if($G_page == "dashboard"){echo "active";} ?>"' href='#' id='navbarDropdown' role='button' data-bs-toggle='dropdown' aria-expanded='false'>
                            Dashboards
                        </a>
                        <ul class='dropdown-menu' aria-labelledby='navbarDropdown'>
                            <?php
                            echo "<li><a class='dropdown-item' href='/0/dashboard.html'>Eigene Pakete</a></li>";

                            // Abrufen in welchen Gruppen der User ist $S_userID und auflisten /groupID/dashboard.html
                            $sql = "SELECT * FROM groups WHERE id IN (SELECT group_id FROM user_in_group WHERE user_id = '".$S_userID."')";
                            $result = $mysqli->query($sql);

                            while($group = $result->fetch_assoc())
                            {
                                echo "<li><a class='dropdown-item' href='/".$group['id']."/dashboard.html'>".$group['name']."</a></li>";
                            }
                        ?>
                        </ul>
                    </li>

                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item <?php if($G_page == "group"){echo "active";} ?>">
                        <a class="nav-link" href="/group.html">Gruppenverwaltung</a>
                    </li>
                    <li class='nav-item dropdown'>
                        <a class='nav-link dropdown-toggle <?php if($G_page == "profil"){echo "active";} ?>"' href='#' id='navbarDropdown' role='button' data-bs-toggle='dropdown' aria-expanded='false'>
                            Profil
                        </a>
                        <ul class='dropdown-menu' aria-labelledby='navbarDropdown'>
                            <li><a class='dropdown-item' href='/pwchange.html'>Passwort ändern</a></li>
                            <li><a class='dropdown-item' href='/logout.html'>Ausloggen</a></li>
                            <?php 

                            if($S_status >= 2)
                            {
                                echo "
                                <li><hr class='dropdown-divider'></li>
                                <li><a class='dropdown-item' href='/admin.html'>Adminbereich</a></li>
                                ";
                            }

                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class='mb-3'></div>
                
                <?php

                if(file_exists("pages/".$G_page.".page.php"))
                {
                    if($S_userID > 0)
                    {
                        include("pages/".$G_page.".page.php");
                    }
                    else
                    {
                        include("pages/403.page.php");
                    }                    
                }
                else
                {
                    include("pages/404.page.php");
                }
                ?>
            
            </div>
        </div>
    </div>

    <div class="mb-5"></div>

    <?php

    }

    ?>

    <div class="mb-5"></div>

    <footer class="bg-body-tertiary text-center text-lg-start">
        <div class="text-center p-3 text-body-tertiary">
            &copy; <?php echo date("Y"); ?> <?php echo $ProjectName; ?> by <a href='https://github.com/FN-Florian/tracking-dashboard'><i class='fa-brands fa-github'></i> Tracking-Dashboard</a> - <a href="<?php echo $E_Impressum_Link; ?>">Impressum</a>
        </div>
    </footer>
    
    <script src="/assets/custom/jquery.min.js"></script>
    <script src="/assets/bootstrap/js/popper.min.js"></script>
    <script src="/assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="/assets/fontawesome/js/all.min.js"></script>
</body>
</html>
