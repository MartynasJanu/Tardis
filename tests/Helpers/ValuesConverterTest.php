<?php

use PHPUnit\Framework\TestCase;
use Tardis\Classes\ValueDelta;
use Tardis\Helpers\ValuesConverter;

class ValuesConverterTest extends TestCase {
    /**
     * @covers Tardis\Helpers\ValuesConverter::getWidth()
     * @covers Tardis\Helpers\ValuesConverter::__construct()
     * @dataProvider dataTestGetWidth
     */
    public function testGetWidth($values, $expected) {
        $converter = new ValuesConverter($values);
        $this->assertSame($expected, $converter->getWidth());
    }

    public function dataTestGetWidth() {
        return [
            [
                'values ' => [
                    1, 2, 3,
                ],
                'expected' => 1,
            ],
            [
                'values ' => [],
                'expected' => 0,
            ],
            [
                'values ' => [
                    [1, 2],
                    [1, 2],
                ],
                'expected' => 2,
            ],
            [
                'values ' => [
                    1,
                    [1, 2],
                ],
                'expected' => 2,
            ],
            [
                'values ' => [
                    [1, 2],
                    1,
                ],
                'expected' => 2,
            ],
            [
                'values ' => [
                    [1, 2],
                    null,
                    1,
                ],
                'expected' => 2,
            ],
            [
                'values ' => [
                    [1, 2],
                    null,
                    [1, 2, 3, 4],
                    1,
                ],
                'expected' => 4,
            ],
        ];
    }

    /**
     * @covers Tardis\Helpers\ValuesConverter::getDeltas()
     * @covers Tardis\Helpers\ValuesConverter::getArrayValueDelta()
     * @covers Tardis\Helpers\ValuesConverter::getValueDelta()
     * @covers Tardis\Helpers\ValuesConverter::__construct()
     * @dataProvider dataTestGetDeltas
     */
    public function testGetDeltas($values, $expected) {
        $converter = new ValuesConverter($values);
        $this->assertEquals($expected, $converter->getDeltas());
    }

    public function dataTestGetDeltas() {
        return [
            [ // #0
                'values ' => [
                    1, 2, 3,
                ],
                'expected' => [
                    new ValueDelta(ValueDelta::TYPE_INT, 1),
                    new ValueDelta(ValueDelta::TYPE_INT, 1),
                    new ValueDelta(ValueDelta::TYPE_INT, 1),
                ],
            ],
            [ // #1
                'values ' => [
                    1, 1, 1,
                ],
                'expected' => [
                    new ValueDelta(ValueDelta::TYPE_INT, 1),
                    new ValueDelta(ValueDelta::TYPE_INT, 0),
                    new ValueDelta(ValueDelta::TYPE_INT, 0),
                ],
            ],
            [ // #2
                'values ' => [
                    3, 2, 1,
                ],
                'expected' => [
                    new ValueDelta(ValueDelta::TYPE_INT, 3),
                    new ValueDelta(ValueDelta::TYPE_INT, -1),
                    new ValueDelta(ValueDelta::TYPE_INT, -1),
                ],
            ],
            [ // #3
                'values ' => [
                    3, 2, 4,
                ],
                'expected' => [
                    new ValueDelta(ValueDelta::TYPE_INT, 3),
                    new ValueDelta(ValueDelta::TYPE_INT, -1),
                    new ValueDelta(ValueDelta::TYPE_INT, 2),
                ],
            ],
            [ // #4
                'values ' => [
                    3, -2, 4,
                ],
                'expected' => [
                    new ValueDelta(ValueDelta::TYPE_INT, 3),
                    new ValueDelta(ValueDelta::TYPE_INT, -5),
                    new ValueDelta(ValueDelta::TYPE_INT, 6),
                ],
            ],
            [ // #5
                'values ' => [
                    1, null, 2, null, 4,
                ],
                'expected' => [
                    new ValueDelta(ValueDelta::TYPE_INT, 1),
                    new ValueDelta(ValueDelta::TYPE_NULL, null),
                    new ValueDelta(ValueDelta::TYPE_INT, 2),
                    new ValueDelta(ValueDelta::TYPE_NULL, null),
                    new ValueDelta(ValueDelta::TYPE_INT, 4),
                ],
            ],
            [ // #6
                'values ' => [
                    1, 2.1,
                ],
                'expected' => [
                    new ValueDelta(ValueDelta::TYPE_INT, 1),
                    new ValueDelta(ValueDelta::TYPE_FLOAT, 1.1),
                ],
            ],
            [ // #7
                'values ' => [
                    [1, 2.1],
                    [4, 0.1],
                ],
                'expected' => [
                    [
                        new ValueDelta(ValueDelta::TYPE_INT, 1),
                        new ValueDelta(ValueDelta::TYPE_FLOAT, 2.1),
                    ],
                    [
                        new ValueDelta(ValueDelta::TYPE_INT, 3),
                        new ValueDelta(ValueDelta::TYPE_FLOAT, -2.0),
                    ],
                ],
            ],
            [ // #8
                'values ' => [
                    [1, 2.1, null],
                    [4, 0.1, null],
                ],
                'expected' => [
                    [
                        new ValueDelta(ValueDelta::TYPE_INT, 1),
                        new ValueDelta(ValueDelta::TYPE_FLOAT, 2.1),
                        new ValueDelta(ValueDelta::TYPE_NULL, null),
                    ],
                    [
                        new ValueDelta(ValueDelta::TYPE_INT, 3),
                        new ValueDelta(ValueDelta::TYPE_FLOAT, -2.0),
                        new ValueDelta(ValueDelta::TYPE_NULL, null),
                    ],
                ],
            ],
            [ // #9
                'values ' => [
                    [1, 2.1, null],
                    [4, 0.1],
                ],
                'expected' => [
                    [
                        new ValueDelta(ValueDelta::TYPE_INT, 1),
                        new ValueDelta(ValueDelta::TYPE_FLOAT, 2.1),
                        new ValueDelta(ValueDelta::TYPE_NULL, null),
                    ],
                    [
                        new ValueDelta(ValueDelta::TYPE_INT, 4),
                        new ValueDelta(ValueDelta::TYPE_FLOAT, 0.1),
                    ],
                ],
            ],
        ];
    }
}
