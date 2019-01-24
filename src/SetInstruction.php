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

    public function __construct(
        int $timestamp = null,
        $value = null,
        string $type = null,
        string $section_id = null
    ) {
        $this->timestamp = $timestamp;
        $this->value = $value;
        $this->type = $type;
        $this->section_id = $section_id;
    }
}
