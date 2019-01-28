<?php
require_once './vendor/autoload.php';

use Tardis\Exceptions\RedisException;
use Tardis\Redis\Publisher as RedisPublisher;
use Tardis\Tardis;

/**
 * launch daemon tardis_bridge.php
 * php -f ./tardis_bridge.php 127.0.0.1 6379 test test-control
 */

Tardis::setRedisServer('127.0.0.1', 6379);
Tardis::setRedisChannels('test', 'test_control');

// TEST ASYNC WRITE

$tardis = generateValues('write_test_async', 100000);

$start = microtime(true);
$tardis->writeAsync();
echo 'Async: '.number_format(microtime(true) - $start, 8).' s.'.PHP_EOL;

// CHECK IF VALUES ARE WRITTEN

echo 'Values count: '.count($tardis->getValues()).PHP_EOL;
sleep(5);
echo 'Values count: '.count($tardis->getValues()).PHP_EOL;

// COMPARE WRITE TIME

$tardis = generateValues('write_test', 100000);
$start = microtime(true);
$tardis->write();
echo 'Direct: '.number_format(microtime(true) - $start, 8).' s.'.PHP_EOL;

// SHUTDOWN DAEMON

shutdownDaemon();

/**
 * helpers
 */

function generateValues(string $hubId, int $length): Tardis {
    echo 'Generating '.$length.' values...';

    $tardis = new Tardis($hubId);
    $starttime = strtotime('2013-01-01 00:00:00') + 3600 * 2;

    for ($i = 0; $i < $length; ++$i) {
        $timestamp = $starttime + 60 * $i;
        $value = rand(1, 100000000000) / 100000000;
        $tardis->setDecimal($timestamp, $value);
    }

    echo ' Done!'.PHP_EOL;

    return $tardis;
}

function shutdownDaemon() {
    echo 'Sending shutdown command.'.PHP_EOL;
    RedisPublisher::publishString('test_control', 'unsubscribe');
}
