<?php

// namespace Enqueue\Client\Driver;
namespace Enqueue\AzureStorage\Driver;

use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\Client\Driver\GenericDriver;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
}
