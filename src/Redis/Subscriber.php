<?php

namespace Tardis\Redis;

use Tardis\Abstracts\RedisAbstract;
use Tardis\Exceptions\RedisUnsubscribedException;
use Tardis\Exceptions\RedisException;
use Tardis\Tardis;
use Tardis\Storage\FileSystemStorage;
use stdClass;

class Subscriber extends RedisAbstract {
    protected $subscriberLoop = null;

    public function __construct(array $channels) {
        if (static::$redisClient === null) {
            static::initClient();
        }

        $this->subscriberLoop = static::$redisClient->pubSubLoop();
        $this->subscriberLoop->subscribe(array_merge($channels, [self::CONTROL_CHANNEL]));

        foreach ($this->subscriberLoop as $message) {
            $this->processMessage($message);
        }

        unset($this->subscriberLoop);
        throw new RedisUnsubscribedException();
    }

    protected function processMessage(stdClass $message) {
        switch ($message->kind) {
            case 'subscribe':
                echo "Subscribed to {$message->channel}".PHP_EOL;
                break;
            case 'message':
                if ($message->channel === self::CONTROL_CHANNEL) {
                    if ($message->payload === self::UNSUBSCRIBE_COMMAND) {
                        $this->subscriberLoop->unsubscribe();
                        return;
                    }

                    echo 'Control command: '.$message->payload.PHP_EOL;
                } else {
                    try {
                        $this->writeData($message->payload);
                    } catch (RedisException $e) {
                        echo 'RedisException: '.$e->getMessage().PHP_EOL;
                    }
                }
                break;
        }
    }

    protected function writeData(string $payload) {
        $data = json_decode($payload);
        if ($data !== null &&
            isset($data->hub_id) &&
            isset($data->storage_dir) &&
            isset($data->gzip_enabled) &&
            isset($data->instructions)
        ) {
            echo 'Data received, writing'.PHP_EOL;

            $storage = new FilesystemStorage();
            $storage->setStorageDir($data->storage_dir);
            if ($data->gzip_enabled === false) {
                $storage->disableGzip();
            }

            $tardis = new Tardis($data->hub_id, true, $storage);
            $tardis->setInstructions((array)$data->instructions);
            $tardis->write();
        } else {
            throw new RedisException('Data in incorrect format: '.$payload);
        }
    }
}
