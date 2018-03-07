<?php

namespace Tardis\Abstracts;

use Tardis\Interfaces\StorageInterface;

abstract class StorageAbstract implements StorageInterface {
    abstract public function hubExists(string $hub_id): bool;
    abstract public function hubSectionExists(string $hub_id, string $hub_section_id): bool;
    abstract public function createHubIfNotExists(string $hub_id);
    abstract public function writeHubSection(string $hub_id, string $hub_section_id, string $buffer);
    abstract public function readHubSection(string $hub_id, string $hub_section_id): string;
}
