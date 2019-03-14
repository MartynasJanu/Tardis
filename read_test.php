<?php

require_once __DIR__.'/vendor/autoload.php';

use Tardis\Tardis;


$cmd = 'rm -rf data/r/read_test';
@exec($cmd);
$starttime = strtotime('2013-01-01 00:00:00') + 3600 * 0;
$writtenValues = [];
for ($i = 0; $i < 525000; ++$i) {
    $timestamp = $starttime + 60 * $i;
    $value = rand(1, 100000000000) / 100000000;
    $writtenValues[$timestamp] = $value;
}

$tardisWrite = new Tardis('read_test');
foreach ($writtenValues as $timestamp => $value) {
    $tardisWrite->setDecimal($timestamp, $value);
}
$tardisWrite->setString($starttime, 'Hello');
$writtenValues[$starttime] = 'Hello';
$tardisWrite->setString($starttime + 60, "Hello\nworld");
$writtenValues[$starttime + 60] = "Hello\nworld";
$tardisWrite->setArray($starttime + 60 * 2, ["Hello\nworld", 3, 23]);
$writtenValues[$starttime + 60 * 2] = ["Hello\nworld", 3, 23];
$tardisWrite->setArray($timestamp, [$value]);
$writtenValues[$timestamp] = [$value];
$tardisWrite->write();


$max = 0;
$min = PHP_INT_MIN;
$times = [];
$readValues = [];
for ($i = 0; $i < 1; ++$i) {
    $tardis = new Tardis('read_test');

    $start = microtime(true);
    $readValues = $tardis->getValues();
    $elapsed = microtime(true) - $start;
    $times[] = $elapsed;
    if ($max < $elapsed) {
        $max = $elapsed;
    }
    if ($min > $elapsed) {
        $min = $elapsed;
    }

    printf('Read %d item(s) in %0.4f seconds'.PHP_EOL, count($readValues), $elapsed);
}

$sum = 0;
$c = 0;
foreach ($times as $time) {
    if (count($times) > 4 && ($time == $min || $time == $max)) {
        continue;
    }

    $sum += $time;
    ++$c;
}

printf('AVG: %0.4f'.PHP_EOL, $sum / $c);

$wrongs = 0;
$missings = 0;
$i = 0;
foreach ($writtenValues as $timestamp => $value) {
    if (!isset($readValues[$timestamp])) {
        ++$missings;
    } elseif ($readValues[$timestamp] !== $value) {
        ++$wrongs;
        echo ' |'.$i.'| ';
        var_dump($readValues[$timestamp]);
        echo ' != ';
        var_dump($value);
        echo PHP_EOL;
    }

    ++$i;
}

printf("WRONG: %d\tMISSING: %d".PHP_EOL, $wrongs, $missings);