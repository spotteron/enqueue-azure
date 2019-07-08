<?php

namespace Enqueue\AzureStorage\Driver;

use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\Client\Driver\GenericDriver;

/**
 * @method AzureStorageContext getContext
 * @method AzureStorageDestination createQueue(string $name)
 */
class AzureStorageDriver extends GenericDriver
{
    public function __construct(AzureStorageContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    /**
     * Create transport queue name.
     *
     * This driver replaces some queue name characters, which are not valid in Azure Storage queues.
     *
     * @param string $name
     * @param bool $prefix
     *
     * @return string
     */
    protected function createTransportQueueName(string $name, bool $prefix): string
    {
        $name = parent::createTransportQueueName($name, $prefix);

        return str_replace(['.', '_'], ['-dot-', '--'], $name);
    }
}
