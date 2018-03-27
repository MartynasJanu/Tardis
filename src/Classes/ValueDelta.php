<?php
namespace Tardis\Classes;

class ValueDelta {
    const TYPE_NULL = 'null';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';

    protected $type;
    protected $delta;

    function __construct($type, $delta) {
        $this->type = $type;
        $this->delta = $delta;
    }

    public function get() {
        return $this->delta;
    }

    public function getType() {
        return $this->type;
    }

    public function getAbs() {
        return abs($this->delta);
    }
}
