<?php

namespace Tardis\Classes;

use Tardis\Abstracts\BufferAbstract;

class OutputBuffer extends BufferAbstract {
    public function add($value) {
        if (is_int($value)) {
            return $this->addInt($value);
        } elseif (is_float($value)) {
            return $this->addFloat($value);
        } elseif (is_array($value)) {
            return $this->addArray($value);
        } elseif ($value === null) {
            return $this->addNull();
        } else {
            var_dump($value);
            die('OutputBuffer::add() Unsupported data type');
        }
    }

    public function addInt(int $value) {
        $abs = abs($value);
        $type = $this->getIntType($abs);
        $this->typestring .= $type;
        $this->data .= pack($type, $value);
    }

    public function addFloat(float $value) {
        $value = (int)($value * pow(10, self::DECIMALS));
        $abs = abs($value);
        $type = $this->getFloatType($abs);
        $this->typestring .= $type['type'];
        $this->data .= pack($type['real_type'], $value);
    }

    /**
     * Max. number of elements: 32767
     * @param array $values
     */
    public function addArray(array $values) {
        $count = count($values);
        $this->typestring .= self::TYPE_ARRAY;
        $this->typestring .= pack(self::TYPE_INT_SHORT, $count);

        foreach ($values as $value) {
            $this->add($value);
        }
    }

    public function addNull() {
        $this->typestring .= self::TYPE_NULL;
    }

    protected function getIntType(int $abs_value): string {
        if ($abs_value <= self::LENGTH_SHORT) {
            return self::TYPE_INT_SHORT;
        } elseif ($abs_value <= self::LENGTH_LONG) {
            return self::TYPE_INT_LONG;
        } else {
            return self::TYPE_INT_LONGLONG;
        }
    }

    protected function getFloatType(float $abs_value): array {
        if ($abs_value <= self::LENGTH_SHORT) {
            $type = self::TYPE_FLOAT_SHORT;
            $real_type = self::TYPE_INT_SHORT;
        } elseif ($abs_value <= self::LENGTH_LONG) {
            $type = self::TYPE_FLOAT_LONG;
            $real_type = self::TYPE_INT_LONG;
        } else {
            $type = self::TYPE_FLOAT_LONGLONG;
            $real_type = self::TYPE_INT_LONGLONG;
        }

        return [
            'type' => $type,
            'real_type' => $real_type,
        ];
    }
}
