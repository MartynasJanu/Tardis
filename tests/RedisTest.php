<?php

use PHPUnit\Framework\TestCase;
use Predis\Connection\ConnectionException;
use Tardis\Exceptions\RedisException;
use Tardis\Redis\Publisher;
use Tardis\Redis\Subscriber;
use Tardis\Hub;
use Tardis\Tardis;

class RedisTest extends TestCase {
    /**
     * @covers \Tardis\Redis\Publisher::publishString()
     * @covers \Tardis\Abstracts\RedisAbstract::initClient()
     * @dataProvider dataTestPublisherExceptions
     */
    public function testPublisherExceptions(
        array $serverSettings,
        array $exception
    ) {
        Tardis::resetRedis();
        Publisher::resetClient();

        if (!empty($serverSettings)) {
            Tardis::setRedisServer($serverSettings['host'], $serverSettings['port']);
        }

        if (isset($exception['message'])) {
            $exceptionMessage = null;
            $exceptionClass = null;

            try {
                Publisher::publishString('test', 'test');
            } catch (\Exception $e) {
                $exceptionClass = get_class($e);
                $exceptionMessage = $e->getMessage();
            }

            $this->assertEquals($exceptionClass, $exception['class']);
            $this->assertEquals($exceptionMessage, $exception['message']);
        } else {
            $this->expectException($exception['class']);
            Publisher::publishString('test', 'test');
        }
    }

    public function dataTestPublisherExceptions(): array {
        return [
            '0 - no server' => [
                'serverSettings' => [],
                'exception' => [
                    'class' => RedisException::class,
                    'message' => 'Host and/or port missing in server settings',
                ],
            ],
            '1 - no listeners' => [
                'serverSettings' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                ],
                'exception' => [
                    'class' => RedisException::class,
                    'message' => 'No subscribers listening to channel: test',
                ],
            ],
            '2 - incorrect port' => [
                'serverSettings' => [
                    'host' => '127.0.0.1',
                    'port' => 1234,
                ],
                'exception' => [
                    'class' => ConnectionException::class,
                ],
            ],
            '3 - incorrect host' => [
                'serverSettings' => [
                    'host' => '192.0.0.1',
                    'port' => 6379,
                ],
                'exception' => [
                    'class' => ConnectionException::class,
                ],
            ],
        ];
    }

    /**
     * @covers \Tardis\Redis\Subscriber::__construct()
     */
    public function testSubscriberNoChannels() {
        Tardis::resetRedis();
        Subscriber::resetClient();

        $exceptionMessage = null;
        $exceptionClass = null;

        try {
            new Subscriber();
        } catch (\Exception $e) {
            $exceptionClass = get_class($e);
            $exceptionMessage = $e->getMessage();
        }

        $this->assertEquals($exceptionClass, RedisException::class);
        $this->assertEquals($exceptionMessage, 'Redis channels not set.');
    }
}
