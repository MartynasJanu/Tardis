<?php

namespace Tardis;

class SetInstruction {
    /**
     *
     * @var int
     */
    public $timestamp;

    /**
     *
     * @var int|float
     */
    public $value;

    /**
     *
     * @var string Hub::BASE_TYPE_*
     */
    public $type;

    /**
     *
     * @var string
     */
    public $section_id;
}
