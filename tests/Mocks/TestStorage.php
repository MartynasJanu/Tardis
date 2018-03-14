<?php

namespace Tardis\Tests\Mocks;

use Tardis\Abstracts\StorageAbstract;

class TestStorage extends StorageAbstract {
    public $hubSectionExistsReturn = false;
    public $writeHubSectionSet = null;
    public $hubSectionBuffer = null;
    public $hubSections = [];

    public function hubExists(string $hub_id): bool {
        die('hubExists');
    }

    public function hubSectionExists(string $hub_id, string $hub_section_id): bool {
        return $this->hubSectionExistsReturn;
    }

    public function getHubSections(string $hub_id): array {
        return $this->hubSections;
    }

    public function createHubIfNotExists(string $hub_id) {

    }

    public function writeHubSection(string $hub_id, string $hub_section_id, string $buffer) {
        $this->hubSectionBuffer = $buffer;
    }

    public function readHubSection(string $hub_id, string $hub_section_id): string {
        return $this->hubSectionBuffer;
    }
}
