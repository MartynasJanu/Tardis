<?php

namespace Tardis\Abstracts;

use Predis\Client as RedisClient;
use Tardis\Exceptions\RedisException;
use Tardis\Tardis;

abstract class RedisAbstract {
    protected static $redisClient = null;

    /**
     * @throws RedisException
     */
    public static function initClient() {
        $redisServer = Tardis::getRedisServer();

        if (empty($redisServer)) {
            throw new RedisException('Host and/or port missing in server settings');
        }

        static::$redisClient = new RedisClient($redisServer);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function resetClient() {
        static::$redisClient = null;
    }
}
