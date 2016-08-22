<?php
session_start();

error_reporting(E_ALL ^ E_NOTICE);
ini_set('max_execution_time', 300);

define('CWD', getcwd());

require_once 'classes/_import.php';
$codeCheck = new CodeCheck();
$codeCheck->init();

?>