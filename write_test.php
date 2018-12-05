<?php

require_once './vendor/autoload.php';

use Tardis\Tardis;

$tardis = new Tardis('write_test');

$starttime = strtotime('2013-01-01 00:00:00') + 3600 * 2;
$values = [];
for ($i = 0; $i < 525000; ++$i) {
    $timestamp = $starttime + 60 * $i;
    $value = rand(1, 100000000000) / 100000000;
    $values[$timestamp] = $value;
}

$msg = [
    'storage_dir' => $tardis->storage->getStorageDir(),
    'hub_id' => $tardis->getHubId(),
    'values' => $values,
];

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$start = microtime(true);
$subscribersHeard = $redis->publish('tardis', json_encode($msg));
echo number_format(microtime(true) - $start, 8).' s.'.PHP_EOL;

if ($subscribersHeard === 0) {
    die('nobody heard');
} else {
    die('sent');
}
die;

for ($i = 0; $i < 525000; ++$i) {
    $timestamp = $starttime + 60 * $i;
    $value = rand(1, 100000000000) / 100000000;
    $tardis->setDecimal($timestamp, $value);
    break;
}
$start = microtime(true);
//$tardis->write();
$tardis->getValues();
echo number_format(microtime(true) - $start, 8).' s.'.PHP_EOL;
