<?php

namespace Tardis\Abstracts;

use Tardis\Interfaces\StorageInterface;

abstract class HubAbstract {
    protected $hub_id;
    /**
     *
     * @var StorageAbstract
     */
    protected $storage;

    function __construct($hub_id, StorageAbstract $storage) {
        $this->hub_id = $hub_id;
        $this->storage = $storage;
    }

    abstract public function setInt(int $timestamp, int $value);

    /**
     *
     * @param int $from UNIX timestamp
     * @param int $to UNIX timestamp
     * @return array
     */
    abstract public function getSections(int $from = null, int $to = null): array;

    public function getHubSectionIdByTimestamp(int $timestamp): string {
        $year = (int)gmdate('Y-01-01 00:00:00', $timestamp);
        return (string)gmmktime(0, 0, 0, 1, 1, $year);
    }

    public function getItemCountByTimestamp(string $section_id): int {
        $year = (int)gmdate('Y-01-01 00:00:00', (int)$section_id);
        $days = date("z", mktime(0, 0, 0, 12, 31, $year)) + 1;
        return $days * 24 * 60;
    }
}
