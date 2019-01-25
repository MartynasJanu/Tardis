<?php

namespace Tardis\Abstracts;

use Tardis\Exceptions\RedisException;
use Tardis\Tardis;
use Predis\Client as RedisClient;
use Exception;

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

        try {
            static::$redisClient = new RedisClient($redisServer);
        } catch (Exception $e) {
            throw new RedisException('Redis connection failed');
        }
    }
}
