<?php

use PHPUnit\Framework\TestCase;
use Tardis\Hub;
use Tardis\Tests\Mocks\TestStorage;

class HubTest extends TestCase {
    /**
     * @covers \Tardis\Hub::setInt()
     * @covers \Tardis\Hub::set()
     * @covers \Tardis\Hub::createBlankHubSection()
     * @covers \Tardis\Hub::getSectionData()
     * @covers \Tardis\Hub::roundTimestamp()
     * @covers \Tardis\Hub::writeToSection()
     * @covers \Tardis\Hub::unpackBuffer()
     * @covers \Tardis\Hub::groupDataByTime()
     * @covers \Tardis\Hub::getSectionValues()
     * @covers \Tardis\Abstracts\HubAbstract::__construct()
     * @covers \Tardis\Abstracts\HubAbstract::getHubSectionIdByTimestamp()
     * @dataProvider dataTestSetInt
     */
    public function testSetInt(int $timestamp, int $value) {
        $storage = new TestStorage();
        $storage->hubSectionExistsReturn = false;

        $hub_id = 'test_hub';
        $section_id = '1483228800'; // 2017
        $hub = new Hub($hub_id, $storage);
        
        $hub->setInt($timestamp, $value);
        $values = $hub->getSectionValues($section_id);

        $this->assertCount(1, $values);
        $this->assertSame($values[$timestamp], $value);

        $values = $hub->getSectionValues($section_id, (int)$section_id);
        $this->assertCount(1, $values);
        $this->assertSame($values[$timestamp], $value);

        $values = $hub->getSectionValues($section_id, $timestamp - 1000, $timestamp + 1000);
        $this->assertCount(1, $values);
        $this->assertSame($values[$timestamp], $value);
    }

    public function dataTestSetInt() {
        return [
            // 0 short
            [
                'timestamp' => 1494421980,
                'value' => 1000,
            ],
            // 1 short
            [
                'timestamp' => 1494421980,
                'value' => 0,
            ],
            // 2 negative short
            [
                'timestamp' => 1494421980,
                'value' => -1000,
            ],
            // 3 longlong
            [
                'timestamp' => 1494421980,
                'value' => 1000000000000,
            ],
            // 4 negative longlong
            [
                'timestamp' => 1494421980,
                'value' => -1000000000000,
            ],
            // 5 long
            [
                'timestamp' => 1494421980,
                'value' => 1000000000,
            ],
            // 6 negative long
            [
                'timestamp' => 1494421980,
                'value' => -1000000000,
            ],
        ];
    }

    /**
     * @covers \Tardis\Hub::setDecimal()
     * @covers \Tardis\Hub::set()
     * @covers \Tardis\Hub::createBlankHubSection()
     * @covers \Tardis\Hub::getSectionData()
     * @covers \Tardis\Hub::roundTimestamp()
     * @covers \Tardis\Hub::writeToSection()
     * @covers \Tardis\Hub::unpackBuffer()
     * @covers \Tardis\Abstracts\HubAbstract::getHubSectionIdByTimestamp()
     * @covers \Tardis\Abstracts\HubAbstract::__construct()
     * @dataProvider dataTestSetDecimal
     */
    public function testSetDecimal(int $timestamp, float $value) {
        $storage = new TestStorage();
        $storage->hubSectionExistsReturn = false;

        $hub_id = 'test_hub';
        $section_id = '1483228800'; // 2017
        $hub = new Hub($hub_id, $storage);

        $hub->setDecimal($timestamp, $value);
        $values = $hub->getSectionValues($section_id);
        $this->assertCount(1, $values);
        $this->assertSame($values[$timestamp], $value);

        $values = $hub->getSectionValues($section_id, (int)$section_id);
        $this->assertCount(1, $values);
        $this->assertSame($values[$timestamp], $value);

        $values = $hub->getSectionValues($section_id, $timestamp - 1000, $timestamp + 1000);
        $this->assertCount(1, $values);
        $this->assertSame($values[$timestamp], $value);
    }

    public function dataTestSetDecimal() {
        return [
            // 0 short
            [
                'timestamp' => 1494421980,
                'value' => 1234.5678,
            ],
            // 1 short
            [
                'timestamp' => 1494421980,
                'value' => (float)0.0,
            ],
            // 2 negative short
            [
                'timestamp' => 1494421980,
                'value' => -1234.5678,
            ],
            // 3 longlong
            [
                'timestamp' => 1494421980,
                'value' => 10000000000.1234,
            ],
            // 4 negative longlong
            [
                'timestamp' => 1494421980,
                'value' => -10000000000.1234,
            ],
            // 5 long
            [
                'timestamp' => 1494421980,
                'value' => 1000000000.1234,
            ],
            // 6 negative long
            [
                'timestamp' => 1494421980,
                'value' => -1000000000.1234,
            ],
        ];
    }

    /**
     * @covers \Tardis\Hub::getSections()
     * @covers \Tardis\Hub::getItemCountByTimestamp()
     * @covers \Tardis\Abstracts\HubAbstract::__construct()
     * @dataProvider dataTestGetSections
     */
    public function testGetSections(array $sections, array $expected, int $from = null, int $to = null) {
        $storage = new TestStorage();
        $storage->hubSectionExistsReturn = false;
        $storage->hubSections = $sections;

        $hub_id = 'test_hub';
        $hub = new Hub($hub_id, $storage);
        $actual = $hub->getSections($from, $to);
        $this->assertSame($expected, $actual);
    }

    public function dataTestGetSections() {
        return [
            // 0
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
            ],
            // 1
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'from' => strtotime('2016-01-01 00:00:00 UTC'),
            ],
            // 2
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'from' => strtotime('2016-05-01 00:00:00 UTC'),
            ],
            // 3
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'from' => strtotime('2016-12-31 23:59:59 UTC'),
            ],
            // 4
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                ],
                'from' => strtotime('2016-01-01 00:00:00 UTC'),
                'to' => strtotime('2016-01-01 00:00:00 UTC'),
            ],
            // 5
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                ],
                'from' => strtotime('2015-01-01 00:00:00 UTC'),
                'to' => strtotime('2016-01-01 00:00:00 UTC'),
            ],
            // 6
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                ],
                'from' => null,
                'to' => strtotime('2016-01-01 00:00:00 UTC'),
            ],
            // 7
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [],
                'from' => null,
                'to' => strtotime('2014-01-01 00:00:00 UTC'),
            ],
            // 8
            [
                'sections' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'expected' => [
                    (string)strtotime('2015-01-01 00:00:00 UTC'),
                    (string)strtotime('2016-01-01 00:00:00 UTC'),
                    (string)strtotime('2017-01-01 00:00:00 UTC'),
                ],
                'from' => strtotime('2014-01-01 00:00:00 UTC'),
                'to' => strtotime('2018-01-01 00:00:00 UTC'),
            ],
        ];
    }
}
