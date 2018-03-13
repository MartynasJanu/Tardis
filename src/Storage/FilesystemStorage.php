<?php

namespace Tardis\Storage;

use Tardis\Abstracts\StorageAbstract;

class FilesystemStorage extends StorageAbstract {
    protected $storage_dir = __DIR__.'/../../data';
    protected $gzip_enabled = true;

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
        return $this->storage_dir;
    }

    public function setStorageDir(string $dir) {
        $this->storage_dir = $dir;
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
        $hub_section_path_gzipped = $hub_section_path.'.gzipped';

        if ($this->isGzipEnabled()) {
            file_put_contents($hub_section_path_gzipped, gzcompress($buffer));
        } else {
            file_put_contents($hub_section_path, $buffer);
        }
    }

    public function readHubSection(string $hub_id, string $hub_section_id): string {
        $hub_dir = $this->getHubDirByHubId($hub_id);
        $hub_section_path = $hub_dir.'/'.$hub_section_id;
        $hub_section_path_gzipped = $hub_section_path.'.gzipped';

        if ($this->isGzipEnabled() && file_exists($hub_section_path_gzipped)) {
            return gzuncompress(file_get_contents($hub_section_path_gzipped));
        } else {
            return file_get_contents($hub_section_path);
        }
    }

    public function isGzipEnabled(): bool {
        return $this->gzip_enabled;
    }

    public function enableGzip() {
        $this->gzip_enabled = true;
    }

    public function disableGzip() {
        $this->gzip_enabled = false;
    }
}
