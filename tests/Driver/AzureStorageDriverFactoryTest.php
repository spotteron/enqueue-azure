<?php

namespace Enqueue\AzureStorage\Tests\Driver;

use Enqueue\AzureStorage\AzureStorageConnectionFactory;
use Enqueue\AzureStorage\Driver\AzureStorageDriver;
use Enqueue\AzureStorage\Driver\AzureStorageDriverFactory;
use Enqueue\Client\Resources;
use PHPUnit\Framework\TestCase;

/**
 * Class AzureStorageDriverFactoryTest.
 */
class AzureStorageDriverFactoryTest extends TestCase
{
    public function testCreate()
    {
        $factory = new AzureStorageDriverFactory();
        $driver = $factory->create('azure:somedsn');

        $this->assertInstanceOf(AzureStorageConnectionFactory::class, $driver);

        $drivers = array_filter(
            Resources::getKnownDrivers(),
            static function (array $driver) {
                return $driver['driverClass'] === AzureStorageDriver::class;
            }
        );

        $this->assertCount(1, $drivers);
        $driverData = reset($drivers);
        $this->assertArrayHasKey('schemes', $driverData);
        $this->assertArraySubset(['azure'], $driverData['schemes']);
    }
}
