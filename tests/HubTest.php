<?php

use PHPUnit\Framework\TestCase;
use Tardis\Hub;
use Tardis\Tests\Mocks\TestStorage;

class HubTest extends TestCase {
    public function testSetInt() {
        $storage = new TestStorage();
        $storage->hubSectionExistsReturn = false;

        $hub_id = 'test_hub';
        $section_id = '1483228800'; // 2017
        $hub = new Hub($hub_id, $storage);
        $timestamp = 1494421980;
        $value = (int)1000;
        
        $hub->setInt($timestamp, $value);
        $values = $hub->getSectionValues($section_id, (int)$section_id);
        $this->assertCount(1, $values);
        $this->assertSame($values[$timestamp], $value);
    }
}
