<?php

namespace Tardis;

use Tardis\Interfaces\HubInterface;
use Tardis\Interfaces\StorageInterface;
use Tardis\Storage\FilesystemStorage;

class Tardis {
    protected $hub_id;

    protected $hub_created;

    protected $delayed_save;

    protected $set_instructions = [];

    protected $hub = null;

    protected $useCache = false;

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
            $hub->setInt($timestamp, $value);
        }
    }

    public function write() {
        $hub = $this->getHub();
        foreach ($this->set_instructions as $section_id => $instructions) {
            $hub->setFromInstructions($section_id, $instructions);
        }

        $this->set_instructions = [];
    }

    public function getUseCache(): bool {
        return $this->useCache;
    }

    public function setUseCache($useCache) {
        $this->useCache = $useCache;
    }
}
