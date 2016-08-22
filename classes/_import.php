<?php

if ($handle = opendir(dirname(__FILE__))) {
    while (false !== ($entry = readdir($handle))) {
        if (substr($entry, 0, 6) == 'class.' AND substr($entry, strlen($entry) - 4) == '.php') {
            include_once $entry;
        }
    }
    closedir($handle);
}

?>