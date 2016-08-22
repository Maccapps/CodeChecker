<?php

function logResults($filename, $errors, $warnings, $log_folder = 'Logs' )
{
    return true;
    if (!is_dir($log_folder)) {
        echo '<h2>Warning: Log folder not found - Create a folder called "Logs" inside the "CodeSniffer" folder.</h2>';
        return false;
    }
    $log_filename = getLogFilename($filename);
    $fp = fopen($log_folder.'/'.$log_filename, 'w');
    $file_last_change = date("F d Y H:i:s.", filemtime($filename));
    $file_error_count = $errors;
    $file_warning_count = $warnings;
    $log_content = "$file_last_change\n$file_error_count\n$file_warning_count";
    fwrite($fp, $log_content);

}

switch($_GET['action']) {

    case 'read':
        $file = $_GET['url'];
        $fp = fopen($file, 'r');
        echo file_get_contents($file);
        fclose($fp);
        break;

    case 'log':
        $filename = $_GET['f'];
        $errors = $_GET['e'];
        $warnings  = $_GET['w'];
        logResults($filename, $errors, $warnings, '../CodeSniffer/Logs');
        break;

    case 'session':
        session_start();
        $_SESSION['jslint'] = $_POST['data'];
        $_SESSION['jslintdone'] = true;
        echo 1;
        break;

}
?>