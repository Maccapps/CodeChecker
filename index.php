<?php
//  check to see if we want to edit a file
if (array_key_exists('edit', $_GET) AND $_GET['edit'] != '') {
    exec(urldecode($_GET['edit']));
    header('location: http://' . $_SERVER['HTTP_HOST']);
    die();
}
require_once '_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:600' rel='stylesheet' type='text/css'>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:800italic' rel='stylesheet' type='text/css'>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic' rel='stylesheet' type='text/css'>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400' rel='stylesheet' type='text/css'>
    <link  rel="icon" type="image/png" href="_global/img/icon-code.png">
    <link rel="stylesheet" href="_global/css/reset.css">
    <link rel="stylesheet" href="_global/css/app.css">

    <?php

    switch($_SESSION['current']['action']) {

        case 'list':
            $col2 = 'overview';
            include '_global/inc/list.php';
            break;

        case 'check':
            $col2 = 'check';
            include '_global/inc/check.php';
            break;

        default:
        case 'projects':
            include '_global/inc/home.php';
            break;
    }
    ?>


    <script src="_global/js/vendor/jquery/1.12.4/jquery.min.js"></script>
    <script src="_global/js/app.js"></script>
</body>
</html>