<?php

namespace Tardis\Storage;

use Tardis\Abstracts\StorageAbstract;

class FilesystemStorage extends StorageAbstract {
    public function hubExists(string $hub_id): bool {
        $hub_dir = $this->getHubDirByHubId($hub_id);

        if (file_exists($hub_dir) && is_dir($hub_dir)) {
            return true;
        } else {
            return false;
        }
    }

    public function hubSectionExists(string $hub_id, string $hub_section_id): bool {
        $hub_dir = $this->getHubDirByHubId($hub_id);
        $hub_section_path = $hub_dir.'/'.$hub_section_id;
        if (file_exists($hub_section_path)) {
            return true;
        } else {
            return false;
        }
    }

    public function getHubDirByHubId(string $hub_id): string {
        $hub_key = substr($hub_id, 0, 1);
        $path = '%s/%s/%s';

        return sprintf($path, $this->getStorageDir(), $hub_key, $hub_id);
    }

    public function getStorageDir(): string {
        return __DIR__.'/../../data';
    }

    public function createHubIfNotExists(string $hub_id) {
        if ($this->hubExists($hub_id)) {
            return;
        }

        $hub_dir = $this->getHubDirByHubId($hub_id);
        if (!mkdir($hub_dir, 0777, true)) {
            die('Failed to create '.$hub_dir);
        }
    }

    public function writeHubSection(string $hub_id, string $hub_section_id, string $buffer) {
        $hub_dir = $this->getHubDirByHubId($hub_id);
        $hub_section_path = $hub_dir.'/'.$hub_section_id;
        file_put_contents($hub_section_path, $buffer);
        file_put_contents($hub_section_path.'.gzipped', gzcompress(file_get_contents($hub_section_path, 6)));
    }

    public function readHubSection(string $hub_id, string $hub_section_id): string {
        $hub_dir = $this->getHubDirByHubId($hub_id);
        $hub_section_path = $hub_dir.'/'.$hub_section_id;

        return file_get_contents($hub_section_path);
    }
}
