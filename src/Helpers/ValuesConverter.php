<?php

namespace Tardis\Helpers;

use Tardis\Classes\ValueDelta;

class ValuesConverter {
    protected $values = null;
    protected $width = null;

    public function __construct($values) {
        $this->values = $values;
    }

    public function getDeltas(): array {
        $deltas = [];
        $last = 0;
        foreach ($this->values as $value) {
            if (is_array($value)) {
                $deltas[] = $this->getArrayValueDelta($value, $last);
            } else {
                $deltas[] = $this->getValueDelta($value, $last);
            }
        }

        return $deltas;
    }

    protected function getArrayValueDelta(array $values, &$last): array {
        if (is_array($last) && count($values) === count($last)) {
            $same_type = true;
        } else {
            $same_type = false;
            $last = [];
        }

        $deltas = [];
        foreach ($values as $i => $value) {
            if (!$same_type) {
                $last[$i] = 0;
            }
            $deltas[] = $this->getValueDelta($value, $last[$i]);
        }

        return $deltas;
    }

    protected function getValueDelta($value, &$last): ValueDelta {
        if ($value === null) {
            $last = 0;
            $delta = new ValueDelta(ValueDelta::TYPE_NULL, null);
            return $delta;
        } else {
            $type = ValueDelta::TYPE_INT;
            if (is_float($value)) {
                $type = ValueDelta::TYPE_FLOAT;
            }

            $delta = $value - $last;
            $last = $value;
            return new ValueDelta($type, $delta);
        }
    }

    public function getWidth(): int {
        if ($this->width === null) {
            $width = 0;
            foreach ($this->values as $value) {
                if (is_array($value)) {
                    $w = count($value);
                } else {
                    $w = 1;
                }

                if ($w > $width) {
                    $width = $w;
                }
            }

            $this->width = $width;
        }

        return $this->width;
    }
}
