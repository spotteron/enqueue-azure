# Azure Storage transport
Azure Storage transport is a messaging solution transport using Azure comptabile with [Queue Interop](https://github.com/queue-interop/queue-interop) 

[![Build Status](https://travis-ci.org/assoconnect/enqueue-azure.svg?branch=master)](https://travis-ci.org/assoconnect/enqueue-azure)
[![Coverage Status](https://coveralls.io/repos/github/assoconnect/enqueue-azure/badge.svg?branch=master)](https://coveralls.io/github/assoconnect/enqueue-azure?branch=master)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=assoconnect_enqueue-azure&metric=alert_status)](https://sonarcloud.io/dashboard?id=assoconnect_enqueue-azure)

The transport uses [Azure Storage](https://docs.microsoft.com/en-us/azure/storage/queues/storage-dotnet-how-to-use-queues) as a message broker. 
It creates a collection (a queue or topic) there. It's a FIFO system (First In First Out).
 
* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Send expiration message](#send-expiration-message)
* [Consume message](#consume-message)
* [Delete queue (purge messages)](#delete-queue-purge-messages)
* [Delete topic (purge messages)](#delete-topic-purge-messages)

## Installation

* With composer:

```bash
$ composer require assoconnect/enqueue-azure
```

## Create context

```php
<?php
use Enqueue\AzureStorage\AzureStorageConnectionFactory;

// connects to azure
$factory = new AzureStorageConnectionFactory('DefaultEndpointsProtocol=https;AccountName=<accountname>;AccountKey=<youraccountkey>');

$context = $factory->createContext();

```

## Send message to topic

```php
<?php
/** @var \Enqueue\AzureStorage\AzureStorageContext $context */

$fooTopic = $context->createTopic('aTopic');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\AzureStorage\AzureStorageContext $context */

$fooQueue = $context->createQueue('aQueue');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```

## Send expiration message

```php
<?php
/** @var \Enqueue\AzureStorage\AzureStorageContext $context */
/** @var \Enqueue\AzureStorage\AzureStorageDestination $fooQueue */


$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setTimeToLive(60000) // 60 sec
    ->send($fooQueue, $message)
;
```

## Consume message:

```php
<?php
/** @var \Enqueue\AzureStorage\AzureStorageContext $context */

$fooQueue = $context->createQueue('aQueue');
$consumer = $context->createConsumer($fooQueue);

$message = $consumer->receiveNoWait();

// process a message

$consumer->acknowledge($message);
//$consumer->reject($message);
```

## Delete queue (purge messages):

```php
<?php
/** @var \Enqueue\AzureStorage\AzureStorageContext $context */

$fooQueue = $context->createQueue('aQueue');

$context->deleteQueue($fooQueue);
```

## Delete topic (purge messages):

```php
<?php
/** @var \Enqueue\AzureStorage\AzureStorageContext $context */

$fooTopic = $context->createTopic('aTopic');

$context->deleteTopic($fooTopic);
```

[back to index](../index.md)
