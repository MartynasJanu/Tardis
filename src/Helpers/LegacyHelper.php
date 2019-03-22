<?php

namespace Tardis\Helpers;

use Tardis\Hub;

class LegacyHelper {
    const TYPE_NULL = 'x';
    const TYPE_INT_SHORT = 's';
    const TYPE_INT_LONG = 'l';
    const TYPE_INT_LONGLONG = 'q';
    const TYPE_FLOAT_SHORT = '1';
    const TYPE_FLOAT_LONG = '2';
    const TYPE_FLOAT_LONGLONG = '3';

    public static function unpackBuffer(string $buffer): array {
        $header = mb_substr($buffer, 0, Hub::ROWS);
        $raw_data = mb_substr($buffer, Hub::ROWS);
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
                $value = (float)(($last + $data_item[1]) / pow(10, Hub::DECIMALS));
                $last = (int)($value * pow(10, Hub::DECIMALS));
                $offset += 2;
            } elseif ($type === self::TYPE_FLOAT_LONG) {
                $data_item = unpack(self::TYPE_INT_LONG, $raw_data, $offset);
                $value = (float)(($last + $data_item[1]) / pow(10, Hub::DECIMALS));
                $last = (int)($value * pow(10, Hub::DECIMALS));
                $offset += 4;
            } elseif ($type === self::TYPE_FLOAT_LONGLONG) {
                $data_item = unpack(self::TYPE_INT_LONGLONG, $raw_data, $offset);
                $value = (float)(($last + $data_item[1]) / pow(10, Hub::DECIMALS));
                $last = (int)($value * pow(10, Hub::DECIMALS));
                $offset += 8;
            }

            $data[] = $value;
        }

        return $data;
    }
}
