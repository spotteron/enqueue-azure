<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use InvalidArgumentException;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageConnectionFactory implements ConnectionFactory
{
    /**
     * Factory configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Class constructor.
     *
     * @param array|string|null $config
     */
    public function __construct($config)
    {
        $this->config = self::transformConfiguration($config);
    }

    /**
     * Transform configuration to match common format.
     *
     * @param array|string|null $config
     *
     * @return array
     *
     * @throws InvalidArgumentException Thrown, when config is invalid.
     */
    public static function transformConfiguration($config): array
    {
        $configProto = [
            'visibility_timeout' => null,
        ];

        if (is_string($config)) {
            return array_replace_recursive($configProto, ['connection_string' => $config]);
        }

        if (!is_array($config) || empty($config['connection_string'])) {
            throw new InvalidArgumentException('Array config has to contain non empty "connection_string" key');
        }

        return array_replace_recursive($configProto, $config);
    }

    public function createContext(): Context
    {
        $client = QueueRestProxy::createQueueService($this->config['connection_string']);

        return new AzureStorageContext($client, $this->config);
    }
}
