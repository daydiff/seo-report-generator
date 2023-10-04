<?php

require_once('class/func.php');
ini_set("session.use_cookies", 1);
ini_set("session.use_trans_sid", 0);
ini_set("session.auto_start", 0);
session_start();
if (!isset($_SESSION['rev_seo'])) {

    if (!empty($_POST['ulogin']) && !empty($_POST['upass'])) {
        $ulogin = $_POST['ulogin'];
        $upass = $_POST['upass'];
        $in = login::getInstance()->isTrue($ulogin, $upass);
        if ($in) {
            $_SESSION['rev_seo'] = login::getInstance()->getVector($ulogin, $upass);
            header('Location: /report');
        } else {

            header('Location: login.php?wrong=1');
        }
    }

    ?>

    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" href="css/stat.css">
    </head>

    <body>
    <div class="header">
        <div id="name"><a href="<?php echo "http://" . $_SERVER['SERVER_NAME'] . "/report" ?>">Site progress report</a>
        </div>
    </div>
    <div class="login">

        <form action="login.php" method="post">
            <label for="ulogin">Username: </label>
            <input type="text" name="ulogin"/>
            <label for="upass">Password: </label>
            <input type="password" name="upass"/>
            <input type="submit" name="dologin" value="Sign in"/>
        </form>

    </div>

    </body>

    </html>

    <?php
} else {
    header('Location: /report');
}

?>