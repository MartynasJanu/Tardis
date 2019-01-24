<?php

namespace Tardis\Redis;

use Tardis\Exceptions\RedisException;
use Tardis\Abstracts\RedisAbstract;

class Publisher extends RedisAbstract {
    /**
     * @param string $channel
     * @param array $data
     */
    public static function publishArray(string $channel, array $data) {
        static::publishString($channel, json_encode($data));
    }

    /**
     * @throws RedisException
     * @param string $channel
     * @param string $message
     */
    public static function publishString(string $channel, string $message) {
        if (static::$redisClient === null) {
            static::initClient();
        }

        $subscribersHeard = static::$redisClient->publish($channel, $message);
        if ($subscribersHeard === 0) {
            throw new RedisException('No subscribers listening to channel: '.$channel);
        }
    }
}
