<?php

namespace Tardis;

use Tardis\Exceptions\RedisException;
use Tardis\Interfaces\HubInterface;
use Tardis\Interfaces\StorageInterface;
use Tardis\Redis\Publisher as RedisPublisher;
use Tardis\Storage\FilesystemStorage;

class Tardis {
    protected $hub_id;

    protected $hub_created;

    protected $delayed_save;

    protected $set_instructions = [];

    protected $hub = null;

    protected $useCache = false;

    protected static $redisHost = null;
    protected static $redisPort = null;
    protected static $redisChannel = null;
    protected static $redisControlChannel = null;
    protected static $redisUnsubscribeCommand = null;

    /**
     *
     * @var StorageInterface
     */
    protected $storage;

    public function __construct(string $hub_id, bool $delayed_save = true, StorageInterface $storage = null) {
        if ($storage === null) {
            $this->storage = new FilesystemStorage();
        } else {
            $this->storage = $storage;
        }

        $this->hub_id = $hub_id;
        $this->hub_created = $this->storage->hubExists($hub_id);
        $this->delayed_save = $delayed_save;
    }

    public function getHub(): HubInterface {
        if ($this->hub === null) {
            $this->hub = new Hub($this->hub_id, $this->storage);
            $this->hub->useCache = $this->useCache;
        }

        return $this->hub;
    }

    /**
     *
     * @param int $from UNIX timestamp
     * @param int $to UNIX timestamp
     */
    public function getValues(int $from = null, int $to = null, bool $keep_nulls = false): array {
        $hub = $this->getHub();
        $sections = $hub->getSections($from, $to);

        $values = [];
        foreach ($sections as $section_id) {
            $section_values = $hub->getSectionValues($section_id, $from, $to, $keep_nulls);
            foreach ($section_values as $timestamp => $value) {
                $values[$timestamp] = $value;
            }
        }

        return $values;
    }

    public function getInts(int $timestamp) {
        $hub = $this->getHub();
        $section_id = $hub->getHubSectionIdByTimestamp($timestamp);
        return $hub->getSectionData($section_id);
    }

    public function setInt(int $timestamp, int $value) {
        $hub = $this->getHub();
        if ($this->delayed_save) {
            $section_id = $hub->getHubSectionIdByTimestamp($timestamp);
            $set_instruction = new SetInstruction();
            $set_instruction->section_id = $section_id;
            $set_instruction->timestamp = $timestamp;
            $set_instruction->type = Hub::BASE_TYPE_INT;
            $set_instruction->value = $value;
            if (!isset($this->set_instructions[$section_id])) {
                $this->set_instructions[$section_id] = [];
            }
            $this->set_instructions[$section_id][] = $set_instruction;
        } else {
            $hub->setInt($timestamp, $value);
        }
    }

    public function setDecimal(int $timestamp, float $value) {
        $hub = $this->getHub();
        if ($this->delayed_save) {
            $section_id = $hub->getHubSectionIdByTimestamp($timestamp);
            $set_instruction = new SetInstruction();
            $set_instruction->section_id = $section_id;
            $set_instruction->timestamp = $timestamp;
            $set_instruction->type = Hub::BASE_TYPE_DECIMAL;
            $set_instruction->value = $value;
            if (!isset($this->set_instructions[$section_id])) {
                $this->set_instructions[$section_id] = [];
            }
            $this->set_instructions[$section_id][] = $set_instruction;
        } else {
            $hub->setDecimal($timestamp, $value);
        }
    }

    public function write() {
        $hub = $this->getHub();
        foreach ($this->set_instructions as $section_id => $instructions) {
            $hub->setFromInstructions($section_id, $instructions);
        }

        $this->set_instructions = [];
    }

    public function writeAsync() {
        $data = [
            'hub_id' => $this->hub_id,
            'storage_dir' => $this->storage->getStorageDir(),
            'gzip_enabled' => $this->storage->isGzipEnabled(),
            'instructions' => $this->set_instructions,
        ];

        try {
            if (self::$redisChannel === null) {
                throw new RedisException('Redis channels not set.');
            }

            RedisPublisher::publishArray(self::$redisChannel, $data);
        } catch (RedisException $e) {
            $this->write();
        }

        $this->set_instructions = [];
    }

    public function setInstructions(array $instructions) {
        $this->set_instructions = $instructions;
    }

    public function getUseCache(): bool {
        return $this->useCache;
    }

    public function setUseCache($useCache) {
        $this->useCache = $useCache;
    }

    public function getHubId(): string {
        return $this->hub_id;
    }

    public static function setRedisServer(string $host, int $port) {
        self::$redisHost = $host;
        self::$redisPort = $port;
    }

    public static function getRedisServer(): array {
        if (self::$redisHost === null ||
            self::$redisPort === null
        ) {
            return [];
        }

        return [
            'host' => self::$redisHost,
            'port' => self::$redisPort,
            'read_write_timeout' => 0,
        ];
    }

    public static function setRedisChannels(
        string $channel,
        string $controlChannel,
        string $unsubscribeCommand = 'unsubscribe'
    ) {
        self::$redisChannel = $channel;
        self::$redisControlChannel = $controlChannel;
        self::$redisUnsubscribeCommand = $unsubscribeCommand;
    }

    public static function getRedisChannel(): ?string {
        return self::$redisChannel;
    }

    public static function getRedisControlChannel(): ?string {
        return self::$redisControlChannel;
    }

    public static function getRedisUnsubscribeCommand(): ?string {
        return self::$redisUnsubscribeCommand;
    }
}
