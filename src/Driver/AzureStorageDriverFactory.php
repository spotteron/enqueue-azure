<?php

declare(strict_types=1);

namespace Enqueue\AzureStorage\Driver;

use Enqueue\AzureStorage\AzureStorageConnectionFactory;
use Enqueue\Client\Resources;
use Enqueue\ConnectionFactoryFactoryInterface;
use Interop\Queue\ConnectionFactory;
use InvalidArgumentException;

class AzureStorageDriverFactory implements ConnectionFactoryFactoryInterface
{
    /**
     * If string is used, it should be a valid DSN.
     *
     * If array is used, it must have a dsn key with valid DSN string.
     * The other array options are treated as default values.
     * Options from DSN overwrite them.
     *
     *
     * @param string|array $config
     *
     * @return AzureStorageConnectionFactory
     *
     * @throws InvalidArgumentException if invalid config provided
     */
    public function create($config): ConnectionFactory
    {
        Resources::addDriver(AzureStorageDriver::class, ['azure'], [], ['assoconnect/enqueue-azure']);

        return new AzureStorageConnectionFactory(AzureStorageConnectionFactory::transformConfiguration($config));
    }
}
