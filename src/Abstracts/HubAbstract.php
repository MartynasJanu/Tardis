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

    public function getHubSectionIdByTimestamp(int $timestamp): string {
        $year = (int)gmdate('Y-01-01 00:00:00', $timestamp);
        return (string)gmmktime(0, 0, 0, 1, 1, $year);
    }
}
