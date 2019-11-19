<?php

declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use InvalidArgumentException;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageConnectionFactory implements ConnectionFactory
{
    /**
     * Configuration key for connection string.
     */
    private const KEY_CONNECTION_STRING = 'connection_string';
    
    /**
     * Configuration key for DSN.
     */
    private const KEY_DSN = 'dsn';
    
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

    public function createContext(): Context
    {
        $client = QueueRestProxy::createQueueService($this->config[self::KEY_CONNECTION_STRING]);

        return new AzureStorageContext($client, $this->config);
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
            $config = self::parseDsn($config);

            if (empty($config[self::KEY_CONNECTION_STRING])) {
                throw new InvalidArgumentException('Configuration cannot be empty.');
            }

            return array_replace_recursive($configProto, $config);
        }

        if (is_array($config)) {
            if (isset($config[self::KEY_DSN])) {
                $parsed = array_replace_recursive($configProto, self::parseDsn($config[self::KEY_DSN]));

                // Support for old usage in Enqueue bundle, when schema has been 'azure:'
                // and all other parameters had to be passed
                if (!empty($parsed[self::KEY_CONNECTION_STRING])) {
                    return $parsed;
                }

                unset($config[self::KEY_DSN]);
            }

            if (!empty($config[self::KEY_CONNECTION_STRING])) {
                return array_replace_recursive($configProto, $config);
            }

            throw new InvalidArgumentException('Array config has to contain non empty "connection_string" key.');
        }

        throw new InvalidArgumentException('Configuration cannot be empty.');
    }

    /**
     * Parse DSN.
     *
     * @param string $dsn
     *
     * @return array
     */
    private static function parseDsn(string $dsn): array
    {
        // Scheme without transport prefix is used
        if (false === strpos($dsn, ':')) {
            $dsn = 'azure:' . $dsn;
        }

        $parsed = Dsn::parseFirst($dsn);

        // Tis place ignores coverage, as it seems impossible to invoke using Dsn class
        // @codeCoverageIgnoreStart
        if (!$parsed) {
            throw new InvalidArgumentException('Invalid DSN provided.');
        }
        // @codeCoverageIgnoreEnd

        // This is to support old installations, where dsn has been simply 'azure:'.
        if (!$parsed->getPath()) {
            return [];
        }

        $return = [
            self::KEY_CONNECTION_STRING => $parsed->getPath(),
        ];

        foreach ($parsed->getQueryBag()->toArray() as $key => $val) {
            $return[$key] = $val;
        }

        return $return;
    }
}
