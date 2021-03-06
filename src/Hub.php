<?php

namespace Tardis;

use Tardis\Abstracts\HubAbstract;
use Tardis\Interfaces\HubInterface;

class Hub extends HubAbstract implements HubInterface {
    const ROWS = 366 * 24 * 60;

    const TYPE_NULL = 'x';
    const TYPE_INT_SHORT = 's';
    const TYPE_INT_LONG = 'l';
    const TYPE_INT_LONGLONG = 'q';
    const TYPE_FLOAT_SHORT = '1';
    const TYPE_FLOAT_LONG = '2';
    const TYPE_FLOAT_LONGLONG = '3';

    const BASE_TYPE_INT = 'int';
    const BASE_TYPE_DECIMAL = 'decimal';

    const DECIMALS = 8;

    /** @todo implement cache properly **/
    public $useCache = false;
    protected $groupedSectionCache = [];

    public function setInt(int $timestamp, int $value) {
        return $this->set($timestamp, $value, self::BASE_TYPE_INT);
    }

    public function setDecimal(int $timestamp, float $value) {
        return $this->set($timestamp, $value, self::BASE_TYPE_DECIMAL);
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
            }
        }

        $this->writeToSection($section_id, $timed_data);
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

    /**
     * @todo refactor!
     */
    protected function writeToSection(string $section_id, array $data) {
        $short = pow(2, 15) - 1;
        $long = pow(2, 31) - 1;
        $double = pow(2, 63) - 1;

        $typestring = '';
        $data_buffer = '';

        $data = array_slice($data, 0, self::ROWS);
        $last = 0;
        foreach ($data as $item) {
            if ($item === null) {
                $typestring .= 'x';
                $data_buffer .= pack(self::TYPE_NULL);
                $last = 0;
                continue;
            }

            $type = '';
            $real_type = '';
            if (is_float($item)) {
                $item = (int)($item * pow(10, self::DECIMALS));
                $delta = $item - $last;
                $delta_abs = abs($delta);

                if ($delta_abs <= $short) {
                    $type = self::TYPE_FLOAT_SHORT;
                    $real_type = self::TYPE_INT_SHORT;
//                } elseif ($delta_abs <= $long) {
//                    $type = self::TYPE_FLOAT_LONG;
//                    $real_type = self::TYPE_INT_LONG;
                } else {
                    $type = self::TYPE_FLOAT_LONGLONG;
                    $real_type = self::TYPE_INT_LONGLONG;
                }
            } else {
                $delta = $item - $last;
                $delta_abs = abs($delta);

                if ($delta_abs <= $short) {
                    $type = self::TYPE_INT_SHORT;
                } elseif ($delta_abs <= $long) {
                    $type = self::TYPE_INT_LONG;
                } else {
                    $type = self::TYPE_INT_LONGLONG;
                }

                $real_type = $type;
            }

            $typestring .= $type;
            $data_buffer .= pack($real_type, $delta);
            $last = $item;
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
        $header = mb_substr($buffer, 0, self::ROWS);
        $raw_data = mb_substr($buffer, self::ROWS);
        $data = [];
        $types = str_split($header);

        $offset = 0;
        $last = 0;
        foreach ($types as $type) {
            if ($type === self::TYPE_NULL) {
                $data[] = null;
                $last = 0;
                ++$offset;
                continue;
            } elseif ($type === self::TYPE_INT_SHORT) {
                $data_item = unpack(self::TYPE_INT_SHORT, $raw_data, $offset);
                $delta = $data_item[1];
                $value = $last + $delta;
                $last = $value;
                $offset += 2;
            } elseif ($type === self::TYPE_INT_LONG) {
                $data_item = unpack(self::TYPE_INT_LONG, $raw_data, $offset);
                $delta = $data_item[1];
                $value = $last + $delta;
                $last = $value;
                $offset += 4;
            } elseif ($type === self::TYPE_INT_LONGLONG) {
                $data_item = unpack(self::TYPE_INT_LONGLONG, $raw_data, $offset);
                $delta = $data_item[1];
                $value = $last + $delta;
                $last = $value;
                $offset += 8;

            } elseif ($type === self::TYPE_FLOAT_SHORT) {
                $data_item = unpack(self::TYPE_INT_SHORT, $raw_data, $offset);
                $value = (float)(($last + $data_item[1]) / pow(10, self::DECIMALS));
                $last = (int)($value * pow(10, self::DECIMALS));
                $offset += 2;
            } elseif ($type === self::TYPE_FLOAT_LONG) {
                $data_item = unpack(self::TYPE_INT_LONG, $raw_data, $offset);
                $value = (float)(($last + $data_item[1]) / pow(10, self::DECIMALS));
                $last = (int)($value * pow(10, self::DECIMALS));
                $offset += 4;
            } elseif ($type === self::TYPE_FLOAT_LONGLONG) {
                $data_item = unpack(self::TYPE_INT_LONGLONG, $raw_data, $offset);
                $value = (float)(($last + $data_item[1]) / pow(10, self::DECIMALS));
                $last = (int)($value * pow(10, self::DECIMALS));
                $offset += 8;
            }

            $data[] = $value;
        }

        return $data;
    }

    protected function createBlankHubSection(string $section_id) {
        $buffer = str_repeat(self::TYPE_NULL, self::ROWS);
        for ($i = 0; $i < self::ROWS; ++$i) {
            $buffer .= pack(self::TYPE_NULL);
        }

        $this->storage->writeHubSection($this->hub_id, $section_id, $buffer);
    }
}
