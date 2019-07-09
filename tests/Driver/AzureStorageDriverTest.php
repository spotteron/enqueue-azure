<?php

namespace Enqueue\AzureStorage\Tests\Driver;

use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\AzureStorage\Driver\AzureStorageDriver;
use Enqueue\Client\Config;
use Enqueue\Client\RouteCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class AzureStorageDriverTest.
 */
class AzureStorageDriverTest extends TestCase
{
    public function testCreateTransportQueueName(): void
    {
        $context = $this->createContextMock();
        $driver =
            new AzureStorageDriver(
                $context,
                new Config('enqueue', '.', 'app', 'topic', 'queue', 'default', 'processor', [], []),
                new RouteCollection([])
            );
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('enqueue-dot-app-dot-test-queue');

        $driver->createQueue('test_queue');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AzureStorageContext
     */
    private function createContextMock()
    {
        return $this->createMock(AzureStorageContext::class);
    }
}
