<?php

require_once __DIR__.'/vendor/autoload.php';

const TARDIS_HUB_DIR = 'data/r/recreate_test';
const LEGACY_DATA_DIR = '_legacy_test_data';

use Tardis\Tardis;

echo 'Deleting old files (if present)'.PHP_EOL;
deleteDir(TARDIS_HUB_DIR);

echo 'Copying test data (legacy format)'.PHP_EOL;
recurseCopy(LEGACY_DATA_DIR, TARDIS_HUB_DIR);

echo PHP_EOL;
$tardis = new Tardis('recreate_test');

$start = microtime(true);
$legacyValues = $tardis->getValues();
$legacyFormatReadTime = microtime(true) - $start;
printf('Legacy format: Read %d item(s) in %0.4f seconds'.PHP_EOL, count($legacyValues), $legacyFormatReadTime);

$tardis->getHub()->recreate();

// new instance to clear cache
$tardis = new Tardis('recreate_test');
$start = microtime(true);
$recreatedValues = $tardis->getValues();
$readTime = microtime(true) - $start;
printf('New format: Read %d item(s) in %0.4f seconds'.PHP_EOL, count($recreatedValues), $readTime);

echo PHP_EOL;
$wrongs = 0;
$missings = 0;
$i = 0;
foreach ($legacyValues as $timestamp => $value) {
    if (!isset($recreatedValues[$timestamp])) {
        ++$missings;
    } elseif ($recreatedValues[$timestamp] !== $value) {
        ++$wrongs;
        echo ' |'.$i.'| ';
        var_dump($recreatedValues[$timestamp]);
        echo ' != ';
        var_dump($value);
        echo PHP_EOL;
    }

    ++$i;
}

printf("WRONG: %d\tMISSING: %d".PHP_EOL, $wrongs, $missings);

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        return;
    }

    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }

    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }

    rmdir($dirPath);
}

function recurseCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while(($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir($src.'/'.$file)) {
                recurse_copy($src.'/'.$file, $dst.'/'.$file);
            } else {
                copy($src.'/'.$file, $dst.'/'.$file);
            }
        }
    }
    closedir($dir);
}
