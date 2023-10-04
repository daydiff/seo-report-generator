<?php

ini_set("session.use_cookies", 1);
ini_set("session.use_trans_sid", 0);
ini_set("session.auto_start", 0);
session_start();
unset($_SESSION['rev_seo']);
header('Location: login.php');
