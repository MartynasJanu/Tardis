<?php

require_once './vendor/autoload.php';

use Tardis\Tardis;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$redis->subscribe(['tardis'], function (Redis $redis, string $channel, string $message) {
    $data = json_decode($message, true);

    $tardis = new Tardis($data['hub_id']);
    echo 'Received. Writing'.PHP_EOL;
    foreach($data['values'] as $timestamp => $value) {
        if (is_float($value)) {
            $tardis->setDecimal($timestamp, $value);
        } elseif (is_int($value)) {
            $tardis->setInt($timestamp, $value);
        }
    }

    $tardis->write();
    echo 'Written'.PHP_EOL;
});
