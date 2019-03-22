<?php

namespace Tardis;

use Tardis\Abstracts\HubAbstract;
use Tardis\Interfaces\HubInterface;
use Tardis\Helpers\LegacyHelper;

class Hub extends HubAbstract implements HubInterface {
    const ROWS = 366 * 24 * 60;

    const TYPE_NULL = 'x';
    const TYPE_INT = 'i';
    const TYPE_FLOAT = 'f';
    const TYPE_STRING = 's';
    const TYPE_ARRAY = 'a';

    const BASE_TYPE_INT = 'int';
    const BASE_TYPE_DECIMAL = 'decimal';
    const BASE_TYPE_ARRAY = 'array';
    const BASE_TYPE_STRING = 'string';

    const DECIMALS = 8;

    static function antiEOL(): string {
        return chr(2);
    }

    static function nonPackedFileIndicator(): string {
        return chr(14);
    }

    /** @todo implement cache properly **/
    public $useCache = false;
    protected $groupedSectionCache = [];

    public function setInt(int $timestamp, int $value) {
        return $this->set($timestamp, $value, self::BASE_TYPE_INT);
    }

    public function setDecimal(int $timestamp, float $value) {
        return $this->set($timestamp, $value, self::BASE_TYPE_DECIMAL);
    }

    public function setString(int $timestamp, string $value) {
        return $this->set($timestamp, $value, self::BASE_TYPE_STRING);
    }

    public function setArray(int $timestamp, array $value) {
        return $this->set($timestamp, $value, self::BASE_TYPE_ARRAY);
    }

    public function setFromInstructions(string $section_id, array $set_instructions) {
        $this->storage->createHubIfNotExists($this->hub_id);
        if (!$this->storage->hubSectionExists($this->hub_id, $section_id)) {
            $this->createBlankHubSection($section_id);
        }

        $timed_data = $this->getSectionData($section_id);
        /* @var $set_instruction SetInstruction */
        foreach ($set_instructions as $set_instruction) {
            $rounded_timestamp = (int)(floor($set_instruction->timestamp / 60) * 60);

            if (!array_key_exists($rounded_timestamp, $timed_data)) {
                die('Timed array is corrupted: '.$rounded_timestamp.' '.$section_id);
            }

            if ($set_instruction->type === self::BASE_TYPE_INT) {
                $timed_data[$rounded_timestamp] = (int)$set_instruction->value;
            } elseif ($set_instruction->type === self::BASE_TYPE_DECIMAL) {
                $timed_data[$rounded_timestamp] = (float)$set_instruction->value;
            } elseif ($set_instruction->type === self::BASE_TYPE_STRING) {
                $timed_data[$rounded_timestamp] = (string)$set_instruction->value;
            } elseif ($set_instruction->type === self::BASE_TYPE_ARRAY) {
                $timed_data[$rounded_timestamp] = $set_instruction->value;
            } else {
                throw new Exception('Unknown data type');
            }
        }

        $this->writeToSection($section_id, $timed_data);
    }

    public function recreate() {
        $sections = $this->getSections();
        foreach ($sections as $section_id) {
            $data = $this->getSectionData($section_id);
            $this->writeToSection($section_id, $data);
        }
    }

    protected function set(int $timestamp, $value, string $type) {
        $section_id = $this->getHubSectionIdByTimestamp($timestamp);
        $this->storage->createHubIfNotExists($this->hub_id);
        if (!$this->storage->hubSectionExists($this->hub_id, $section_id)) {
            $this->createBlankHubSection($section_id);
        }

        $timed_data = $this->getSectionData($section_id);
        $rounded_timestamp = $this->roundTimestamp($timestamp);

        if (!array_key_exists($rounded_timestamp, $timed_data)) {
            die('Timed array is corrupted: '.$rounded_timestamp.' '.$section_id);
        }

        if ($type === self::BASE_TYPE_INT) {
            $timed_data[$rounded_timestamp] = (int)$value;
        } elseif ($type === self::BASE_TYPE_DECIMAL) {
            $timed_data[$rounded_timestamp] = (float)$value;
        } elseif ($type === self::BASE_TYPE_STRING) {
            $timed_data[$rounded_timestamp] = (string)$value;
        } elseif ($type === self::BASE_TYPE_ARRAY) {
            $timed_data[$rounded_timestamp] = $value;
        } else {
            throw new Exception('Unknown data type');
        }

        $this->writeToSection($section_id, $timed_data);
    }

    public function getSectionData(string $section_id): array {
        $buffer = $this->storage->readHubSection($this->hub_id, $section_id);
        if (empty($buffer)) {
            $this->createBlankHubSection($section_id);
            $buffer = $this->storage->readHubSection($this->hub_id, $section_id);
        }

        $data = $this->unpackBuffer($buffer);
        return $this->groupDataByTime($data, (int)$section_id, true);
    }

    /**
     *
     * @todo implement streamed reading
     */
    public function getSectionValues(
        string $section_id,
        int $from = null,
        int $to = null,
        bool $keep_nulls = false
    ): array {
        if (!isset($this->groupedSectionCache[$section_id])) {
            $buffer = $this->storage->readHubSection($this->hub_id, $section_id);
            $sectionData = $this->unpackBuffer($buffer);
            $this->groupedSectionCache[$section_id] = $this->groupDataByTime(
                $sectionData,
                (int)$section_id,
                $keep_nulls
            );
        }

        $data = $this->groupedSectionCache[$section_id];

        if (empty($from) && $to === null) {
            return $data;
        }

        foreach ($data as $timestamp => $value) {
            if (!empty($from) && $timestamp < $from) {
                unset($data[$timestamp]);
            }

            if ($to !== null && $timestamp > $to) {
                unset($data[$timestamp]);
            }
        }

        return $data;
    }

    /**
     *
     * @param int $from UNIX timestamp
     * @param int $to UNIX timestamp
     * @return array
     */
    public function getSections(int $from = null, int $to = null): array {
        $all_sections = $this->storage->getHubSections($this->hub_id);
        if (empty($from) && $to === null) {
            return $all_sections;
        }

        $actual_sections = [];
        foreach ($all_sections as $section) {
            $actual = true;
            $item_count = $this->getItemCountByTimestamp((int)$section);
            $section_from = (int)$section;
            $section_to = $section_from + $item_count * 60 - 1;

            if (!empty($from) && $section_to < $from) {
                $actual = false;
            }

            if ($to !== null && $section_from > $to) {
                $actual = false;
            }

            if ($actual) {
                $actual_sections[] = $section;
            }
        }

        return $actual_sections;
    }

    protected function writeToSection(string $section_id, array $data) {
        $typestring = self::nonPackedFileIndicator();
        $data_buffer = '';

        foreach (array_slice($data, 0, self::ROWS) as $item) {
            if ($item === null) {
                $typestring .= self::TYPE_NULL;
                continue;
            } elseif (is_float($item)) {
                $typestring .= self::TYPE_FLOAT;
            } elseif (is_int($item)) {
                $typestring .= self::TYPE_INT;
            } elseif (is_array($item)) {
                $typestring .= self::TYPE_ARRAY;
                $item = str_replace(PHP_EOL, self::antiEOL(), json_encode($item));
            } else {
                $typestring .= self::TYPE_STRING;
                $item = str_replace(PHP_EOL, self::antiEOL(), $item);
            }

            $data_buffer .= (string)$item.PHP_EOL;
        }

        $this->storage->writeHubSection($this->hub_id, $section_id, $typestring.$data_buffer);
    }

    protected function roundTimestamp(int $timestamp, int $step = 60): int {
        return (int)(floor($timestamp / $step) * $step);
    }

    protected function groupDataByTime(array $data, int $offset, bool $keep_nulls = false): array {
        $step = 60;
        $time = $offset;
        $grouped_data = [];
        foreach ($data as $item) {
            if ($keep_nulls || $item !== null)  {
                $grouped_data[$time] = $item;
            }
            $time += $step;
        }

        return $grouped_data;
    }

    /**
     * @todo refactor!
     */
    protected function unpackBuffer(string $buffer): array {
        if (mb_substr($buffer, 0, 1) !== self::nonPackedFileIndicator()) {
            return LegacyHelper::unpackBuffer($buffer);
        }

        $header = mb_substr($buffer, 1, self::ROWS);
        $raw_data = mb_substr($buffer, self::ROWS + 1);
        $data = [];
        $types = str_split($header);

        $offset = 0;
        $raw_items = explode(PHP_EOL, $raw_data);
        foreach ($types as $type) {
            if ($type === self::TYPE_NULL) {
                $value = null;

            } elseif ($type === self::TYPE_INT) {
                $value = (int)$raw_items[$offset];
                ++$offset;

            } elseif ($type === self::TYPE_FLOAT) {
                $value = (float)$raw_items[$offset];
                ++$offset;

            } elseif ($type === self::TYPE_STRING) {
                $value = str_replace(self::antiEOL(), PHP_EOL, (string)$raw_items[$offset]);
                ++$offset;

            } elseif ($type === self::TYPE_ARRAY) {
                $value = str_replace(self::antiEOL(), PHP_EOL, (string)$raw_items[$offset]);
                $value = json_decode($value, true);
                ++$offset;
            }

            $data[] = $value;
        }

        return $data;
    }

    protected function createBlankHubSection(string $section_id) {
        $buffer = self::nonPackedFileIndicator().str_repeat(self::TYPE_NULL, self::ROWS);

        $this->storage->writeHubSection($this->hub_id, $section_id, $buffer);
    }
}
