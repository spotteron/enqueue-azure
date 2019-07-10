<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\AzureStorage\AzureStorageConnectionFactory;
use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class AzureStorageConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface(): void
    {
        $this->assertClassImplements(ConnectionFactory::class, AzureStorageConnectionFactory::class);
    }

    /**
     * Test for configuration transformation.
     */
    public function testConstruct(): void
    {
        $dsn = 'azure:somedsn';
        $factory = new AzureStorageConnectionFactory($dsn);
        $this->assertInstanceOf(AzureStorageConnectionFactory::class, $factory);
    }

    /**
     * Test for configuration transformation.
     */
    public function testCreateContext(): void
    {
        $dsn = 'azure:DefaultEndpointsProtocol=https;'
            . 'AccountName=myaccount;'
            . 'AccountKey=bXlrZXk=;EndpointSuffix=core.windows.net';
        $factory = new AzureStorageConnectionFactory($dsn);
        $context = $factory->createContext();
        $this->assertInstanceOf(AzureStorageContext::class, $context);
    }

    /**
     * Test for configuration transformation.
     *
     * @param string|array $config
     * @param array $expected
     *
     * @dataProvider dataProviderTestTransformConfiguration
     */
    public function testTransformConfiguration($config, array $expected): void
    {
        $parsed = AzureStorageConnectionFactory::transformConfiguration($config);
        $this->assertEquals($expected, $parsed);
    }

    /**
     * Test for configuration transformation exception - empty.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Configuration cannot be empty.
     */
    public function testTransformConfigurationExceptionEmpty(): void
    {
        AzureStorageConnectionFactory::transformConfiguration(null);
    }

    /**
     * Test for configuration transformation exception - empty.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Configuration cannot be empty.
     */
    public function testTransformConfigurationExceptionEmpty2(): void
    {
        AzureStorageConnectionFactory::transformConfiguration('azure:');
    }

    /**
     * Test for configuration transformation exception - empty.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Array config has to contain non empty "connection_string" key.
     */
    public function testTransformConfigurationExceptionEmptyConnectionString(): void
    {
        $config = ['connection_string'];
        AzureStorageConnectionFactory::transformConfiguration($config);
    }

    /**
     * Test for configuration transformation exception - empty.
     *
     * @expectedException LogicException
     * @expectedExceptionMessage The DSN is invalid. Scheme contains illegal symbols.
     */
    public function testTransformConfigurationInvalidDsn(): void
    {
        $config = 'az@ure:o';
        AzureStorageConnectionFactory::transformConfiguration($config);
    }


    /**
     * Data provider for testTransformConfiguration().
     *
     * @return array
     */
    public function dataProviderTestTransformConfiguration(): array
    {
        return [
            'legacy-no-params' => [
                'DefaultEndpointsProtocol=https;'
                . 'AccountName=myaccount;'
                . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                . 'EndpointSuffix=core.windows.net',
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => null,
                ],
            ],
            'legacy-params' => [
                'DefaultEndpointsProtocol=https;'
                . 'AccountName=myaccount;'
                . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                . 'EndpointSuffix=core.windows.net?visibility_timeout=1&some_param=1',
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => 1,
                    'some_param' => 1,
                ],
            ],
            'dsn' => [
                'azure:DefaultEndpointsProtocol=https;'
                . 'AccountName=myaccount;'
                . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                . 'EndpointSuffix=core.windows.net?some_param=1&visibility_timeout=2',
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => 2,
                    'some_param' => 1,
                ],
            ],
            'dsn-other-scheme' => [
                'azure+storage+queue:DefaultEndpointsProtocol=https;'
                . 'AccountName=myaccount;'
                . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                . 'EndpointSuffix=core.windows.net?some_param=1&visibility_timeout=2',
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => 2,
                    'some_param' => 1,
                ],
            ],
            'params-no-timeout' => [
                'azure:DefaultEndpointsProtocol=https;'
                . 'AccountName=myaccount;'
                . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                . 'EndpointSuffix=core.windows.net?some_param=1',
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => null,
                    'some_param' => 1,
                ],
            ],
            'array-no-params' => [
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                ],
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => null,
                ],
            ],
            'array-params' => [
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => 10,
                    'some_param' => 'test',
                ],
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => 10,
                    'some_param' => 'test',
                ],
            ],
            'array-dsn-non-empty' => [
                [
                    'dsn' => 'azure:DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'some_param' => '',
                ],
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => null,
                ],
            ],
            'array-dsn-non-empty-other-scheme' => [
                [
                    'dsn' => 'azure+storage+queue:DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'some_param' => '',
                ],
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => null,
                ],
            ],
            'array-dsn-schema-only' => [
                [
                    'dsn' => 'azure:',
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => '1',
                ],
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => 1,
                ],
            ],
            'array-dsn-empty-with-connection-string' => [
                [
                    'dsn' => '',
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => '1',
                ],
                [
                    'connection_string' => 'DefaultEndpointsProtocol=https;'
                        . 'AccountName=myaccount;'
                        . 'AccountKey=aBcDeFgHea/OT99L1234567CKbbtgRqS/dEfGh+A==;'
                        . 'EndpointSuffix=core.windows.net',
                    'visibility_timeout' => 1,
                ],
            ],
            'params-simple-array' => [
                [
                    'dsn' => 'azure:somedsn?param[0]=0&param[2]=1',
                ],
                [
                    'connection_string' => 'somedsn',
                    'visibility_timeout' => null,
                    'param' => [0 => 0, 2 => 1],
                ],
            ],
            'params-assoc-array' => [
                [
                    'dsn' => 'azure:somedsn?param[test]=0&param[test]=1&param[test2]=2',
                ],
                [
                    'connection_string' => 'somedsn',
                    'visibility_timeout' => null,
                    'param' => ['test' => [0, 1], 'test2' => 2],
                ],
            ],
        ];
    }
}
