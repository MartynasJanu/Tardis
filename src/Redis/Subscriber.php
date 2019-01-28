<?php

namespace Tardis\Redis;

use Tardis\Abstracts\RedisAbstract;
use Tardis\Exceptions\RedisUnsubscribedException;
use Tardis\Exceptions\RedisException;
use Tardis\Storage\FileSystemStorage;
use Tardis\Tardis;
use stdClass;

class Subscriber extends RedisAbstract {
    protected $subscriberLoop = null;

    protected $channel;
    protected $controlChannel;
    protected $unsubscribeCommand;

    public function __construct() {
        $this->channel = Tardis::getRedisChannel();
        $this->controlChannel = Tardis::getRedisControlChannel();
        $this->unsubscribeCommand = Tardis::getRedisUnsubscribeCommand();

        if (empty($this->channel) ||
            empty($this->controlChannel) ||
            empty($this->unsubscribeCommand)
        ) {
            throw new RedisException('Redis channels not set.');
        }

        if (static::$redisClient === null) {
            static::initClient();
        }

        $this->subscriberLoop = static::$redisClient->pubSubLoop();
        $this->subscriberLoop->subscribe($this->channel, $this->controlChannel);

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
                if ($message->channel === $this->controlChannel) {
                    if ($message->payload === $this->unsubscribeCommand) {
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
            echo 'Data received, writing... ';

            $storage = new FilesystemStorage();
            $storage->setStorageDir($data->storage_dir);
            if ($data->gzip_enabled === false) {
                $storage->disableGzip();
            }

            $tardis = new Tardis($data->hub_id, true, $storage);
            $tardis->setInstructions((array)$data->instructions);
            $tardis->write();
            echo 'Done!'.PHP_EOL;
        } else {
            throw new RedisException('Data in incorrect format: '.$payload);
        }
    }
}
