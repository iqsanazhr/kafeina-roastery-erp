<?php

exec('wmic process where "name=\'php.exe\'" get processid,commandline', $output);
foreach ($output as $line) {
    if (trim($line)) {
        echo trim($line)."\n";
    }
}
