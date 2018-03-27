<?php

namespace Tardis\Abstracts;


abstract class BufferAbstract {
    const HEADER_SIG = [0x00, 0x3B, 0x6F];
    const VERSION_0 = '0';
    const VERSION_1 = '1';

    const ROWS = 366 * 24 * 60;

    const TYPE_NULL = 'x';
    const TYPE_INT_SHORT = 's';
    const TYPE_INT_LONG = 'l';
    const TYPE_INT_LONGLONG = 'q';
    const TYPE_FLOAT_SHORT = '1';
    const TYPE_FLOAT_LONG = '2';
    const TYPE_FLOAT_LONGLONG = '3';
    const TYPE_ARRAY = '4';

    const BASE_TYPE_INT = 'int';
    const BASE_TYPE_DECIMAL = 'decimal';
    const BASE_TYPE_NULL = 'null';
    const BASE_TYPE_ARRAY = 'array';

    const DECIMALS = 8;

    const LENGTH_SHORT = 32767; // 2^15 - 1
    const LENGTH_LONG = 2147483647; // 2^31 - 1
    const LENGTH_LONGLONG = 9.223372E18; // 2^63 - 1

    protected $typestring = '';
    protected $data = '';
    protected $version;

    public function getTypeString(): string {
        return $this->typestring;
    }

    public function getDataBuffer(): string {
        return $this->data;
    }

    public function getFullBuffer(): string {
        return $this->typestring.$this->data;
    }

    public static function getBufferVersion(string $raw_data): string {
        $possible_header = mb_substr($raw_data, 0, 3);
        if ($possible_header === implode(self::HEADER_SIG)) {
            $version = mb_substr($raw_data, 3, 1);
            if ($version === '1') {
                return self::VERSION_1;
            }
        }

        return self::VERSION_0;
    }
}
