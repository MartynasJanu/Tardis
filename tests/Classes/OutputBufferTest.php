<?php

use PHPUnit\Framework\TestCase;
use Tardis\Classes\OutputBuffer;

class OutputBufferTest extends TestCase {
    /**
     * @covers Tardis\Classes\OutputBuffer::add()
     * @covers Tardis\Classes\OutputBuffer::addInt()
     * @covers Tardis\Classes\OutputBuffer::addFloat()
     * @covers Tardis\Classes\OutputBuffer::addArray()
     * @covers Tardis\Classes\OutputBuffer::addNull()
     * @covers Tardis\Classes\OutputBuffer::getIntType()
     * @covers Tardis\Classes\OutputBuffer::getFloatType()
     * @covers Tardis\Classes\OutputBuffer::getTypeString()
     * @covers Tardis\Classes\OutputBuffer::getDataBuffer()
     * @covers Tardis\Classes\OutputBuffer::getFullBuffer()
     * @dataProvider dataTestAdd
     */
    public function testAdd($value, $typestring, $data) {
        $buffer = new OutputBuffer();
        $buffer->add($value);
        $buffer->getTypeString();

        $this->assertSame($typestring, $buffer->getTypeString());
        $this->assertSame($data, $buffer->getDataBuffer());
        $this->assertSame($typestring.$data, $buffer->getFullBuffer());
    }

    public function dataTestAdd() {
        return [
            [// #0
                'value' => 0,
                'typestring' => OutputBuffer::TYPE_INT_SHORT,
                'data' => pack(OutputBuffer::TYPE_INT_SHORT, 0),
            ],
            [// #1
                'value' => 1,
                'typestring' => OutputBuffer::TYPE_INT_SHORT,
                'data' => pack(OutputBuffer::TYPE_INT_SHORT, 1),
            ],
            [// #2
                'value' => 50000,
                'typestring' => OutputBuffer::TYPE_INT_LONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONG, 50000),
            ],
            [// #3
                'value' => OutputBuffer::LENGTH_LONG + 1,
                'typestring' => OutputBuffer::TYPE_INT_LONGLONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONGLONG, OutputBuffer::LENGTH_LONG + 1),
            ],
            [// #4
                'value' => -1,
                'typestring' => OutputBuffer::TYPE_INT_SHORT,
                'data' => pack(OutputBuffer::TYPE_INT_SHORT, -1),
            ],
            [// #5
                'value' => -50000,
                'typestring' => OutputBuffer::TYPE_INT_LONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONG, -50000),
            ],
            [// #6
                'value' => -OutputBuffer::LENGTH_LONG - 1,
                'typestring' => OutputBuffer::TYPE_INT_LONGLONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONGLONG, -OutputBuffer::LENGTH_LONG - 1),
            ],
            [// #7
                'value' => 1.1,
                'typestring' => OutputBuffer::TYPE_FLOAT_LONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONG, 1.1 * pow(10, OutputBuffer::DECIMALS)),
            ],
            [// #8
                'value' => 50000.123,
                'typestring' => OutputBuffer::TYPE_FLOAT_LONGLONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONGLONG, 50000.123 * pow(10, OutputBuffer::DECIMALS)),
            ],
            [// #9
                'value' => -1.1,
                'typestring' => OutputBuffer::TYPE_FLOAT_LONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONG, -1.1 * pow(10, OutputBuffer::DECIMALS)),
            ],
            [// #10
                'value' => -50000.123,
                'typestring' => OutputBuffer::TYPE_FLOAT_LONGLONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONGLONG, -50000.123 * pow(10, OutputBuffer::DECIMALS)),
            ],
            [// #11
                'value' => null,
                'typestring' => OutputBuffer::TYPE_NULL,
                'data' => pack(OutputBuffer::TYPE_NULL),
            ],
            [// #12
                'value' => [
                    1, 2, 3,
                ],
                'typestring' => OutputBuffer::TYPE_ARRAY.pack(OutputBuffer::TYPE_INT_SHORT, 3).
                    OutputBuffer::TYPE_INT_SHORT.
                    OutputBuffer::TYPE_INT_SHORT.
                    OutputBuffer::TYPE_INT_SHORT,
                'data' => pack(OutputBuffer::TYPE_INT_SHORT, 1).
                    pack(OutputBuffer::TYPE_INT_SHORT, 2).
                    pack(OutputBuffer::TYPE_INT_SHORT, 3),
            ],
            [// #13
                'value' => [
                    1.1, 2.2, 3.3,
                ],
                'typestring' => OutputBuffer::TYPE_ARRAY.pack(OutputBuffer::TYPE_INT_SHORT, 3).
                    OutputBuffer::TYPE_FLOAT_LONG.
                    OutputBuffer::TYPE_FLOAT_LONG.
                    OutputBuffer::TYPE_FLOAT_LONG,
                'data' => pack(OutputBuffer::TYPE_INT_LONG, 1.1 * pow(10, OutputBuffer::DECIMALS)).
                    pack(OutputBuffer::TYPE_INT_LONG, 2.2 * pow(10, OutputBuffer::DECIMALS)).
                    pack(OutputBuffer::TYPE_INT_LONG, 3.3 * pow(10, OutputBuffer::DECIMALS)),
            ],
            [// #14
                'value' => [
                    1.1, -2.2, 3.3, null,
                ],
                'typestring' => OutputBuffer::TYPE_ARRAY.pack(OutputBuffer::TYPE_INT_SHORT, 4).
                    OutputBuffer::TYPE_FLOAT_LONG.
                    OutputBuffer::TYPE_FLOAT_LONG.
                    OutputBuffer::TYPE_FLOAT_LONG.
                    OutputBuffer::TYPE_NULL,
                'data' => pack(OutputBuffer::TYPE_INT_LONG, 1.1 * pow(10, OutputBuffer::DECIMALS)).
                    pack(OutputBuffer::TYPE_INT_LONG, -2.2 * pow(10, OutputBuffer::DECIMALS)).
                    pack(OutputBuffer::TYPE_INT_LONG, 3.3 * pow(10, OutputBuffer::DECIMALS)).
                    pack(OutputBuffer::TYPE_NULL),
            ],
        ];
    }
}
