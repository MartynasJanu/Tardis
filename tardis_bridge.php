<?php
require_once './vendor/autoload.php';

use Tardis\Redis\Subscriber as RedisSubscriber;
use Tardis\Exceptions\RedisUnsubscribedException;

try {
    if (count($argv) > 2) {
        $settings = [
            'host' => $argv[2],
            'port' => $argv[3] ?? 6379,
            'read_write_timeout' => 0,
        ];
        RedisSubscriber::setServerSettings($settings);
    }
    $channels = $argv[1] ?? RedisSubscriber::DEFAULT_CHANNEL;
    new RedisSubscriber(explode(',', $channels));
} catch (RedisUnsubscribedException $e) {
    echo 'Unsubscribe command recieved. Geronimooooo!'.PHP_EOL;
}
