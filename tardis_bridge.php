<?php
require_once './vendor/autoload.php';

use Predis\Connection\ConnectionException;
use Tardis\Exceptions\RedisException;
use Tardis\Exceptions\RedisUnsubscribedException;
use Tardis\Redis\Subscriber as RedisSubscriber;
use Tardis\Tardis;

try {
    $host = $argv[1] ?? '127.0.0.1';
    $port = $argv[2] ?? 6379;
    Tardis::setRedisServer($host, $port);

    $channels = $argv[3] ?? 'tardis';
    $controlChannel = $argv[4] ?? 'tardis_control';
    $unsubscribeCommand = $argv[5] ?? 'unsubscribe';
    Tardis::setRedisChannels($channels, $controlChannel, $unsubscribeCommand);

    new RedisSubscriber();
} catch (RedisException $e) {
    echo $e->getMessage().PHP_EOL;
} catch (ConnectionException $e) {
    echo $e->getMessage().PHP_EOL;
} catch (RedisUnsubscribedException $e) {
    echo 'Unsubscribe command recieved. Geronimooooo!'.PHP_EOL;
}
