<?php

namespace Tardis\Abstracts;

use Tardis\Exceptions\RedisException;
use Predis\Client as RedisClient;
use Exception;

abstract class RedisAbstract {
    const DEFAULT_CHANNEL = 'tardis';
    const CONTROL_CHANNEL = 'tardis_control';
    const UNSUBSCRIBE_COMMAND = 'quit_loop';

    protected static $serverSettings = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'read_write_timeout' => 0,
    ];

    protected static $redisClient = null;

    /**
     * @throws RedisException
     */
    public static function initClient(array $settings = null) {
        try {
            if ($settings === null) {
                static::$redisClient = new RedisClient(static::$serverSettings);
            } else {
                if (!isset($settings['host']) ||
                    !isset($settings['port'])
                ) {
                    throw new RedisException('Host and/or port missing in server settings');
                }
                static::$redisClient = new RedisClient($settings);
            }
        } catch (Exception $e) {
            throw new RedisException('Redis connection failed');
        }
    }

    /**
     * @throws RedisException
     * @param array $settings
     */
    public static function setServerSettings(array $settings) {
        if (!isset($settings['host']) ||
            !isset($settings['port'])
        ) {
            throw new RedisException('Host and/or port missing in server settings');
        }

        static::$serverSettings = $settings;
    }
}
