<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\AzureStorage\AzureStorageConsumer;
use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\AzureStorage\AzureStorageMessage;

use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use LogicException;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesResult;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;

class AzureStorageConsumerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, AzureStorageConsumer::class);
    }

    public function testCouldBeConstructedWithContextAndDestinationAndPreFetchCountAsArguments()
    {
        $restProxy = $this->createQueueRestProxyMock();
        new AzureStorageConsumer(
            $restProxy,
            new AzureStorageDestination('aQueue'),
            new AzureStorageContext($restProxy)
        );
    }

    public function testShouldReturnDestinationSetInConstructorOnGetQueue()
    {
        $destination = new AzureStorageDestination('aQueue');
        $restProxy = $this->createQueueRestProxyMock();
        $consumer = new AzureStorageConsumer($restProxy, $destination, new AzureStorageContext($restProxy));

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testShouldAlwaysReturnNullOnReceiveNoWait()
    {
        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);

        $listMessagesResultMock = $this->createMock(ListMessagesResult::class);
        $listMessagesResultMock
            ->expects($this->any())
            ->method('getQueueMessages')
            ->willReturn([])
        ;

        $azureMock = $this->createQueueRestProxyMock();
        $azureMock
            ->expects($this->any())
            ->method('listMessages')
            ->with('aQueue', $options)
            ->willReturn($listMessagesResultMock)
        ;

        $consumer = new AzureStorageConsumer(
            $azureMock,
            new AzureStorageDestination('aQueue'),
            new AzureStorageContext($azureMock)
        );

        $this->assertNull($consumer->receiveNoWait());
        $this->assertNull($consumer->receiveNoWait());
        $this->assertNull($consumer->receiveNoWait());
    }

    public function testShouldDoNothingOnAcknowledge()
    {
        $restProxy = $this->createQueueRestProxyMock();
        $consumer = new AzureStorageConsumer(
            $restProxy,
            new AzureStorageDestination('aQueue'),
            new AzureStorageContext($restProxy)
        );

        $consumer->acknowledge(new AzureStorageMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $restProxy = $this->createQueueRestProxyMock();
        $consumer = new AzureStorageConsumer(
            $restProxy,
            new AzureStorageDestination('aQueue'),
            new AzureStorageContext($restProxy)
        );

        $consumer->reject(new AzureStorageMessage());
    }

    public function testShouldQueueMsgAgainReject()
    {
        $messageMock = $this->createQueueMessageMock();

        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);

        $listMessagesResultMock = $this->createMock(ListMessagesResult::class);
        $listMessagesResultMock
            ->expects($this->any())
            ->method('getQueueMessages')
            ->willReturn([$messageMock])
        ;
        $createMessageResultMock = $this->createMock(CreateMessageResult::class);
        $createMessageResultMock
            ->expects($this->any())
            ->method('getQueueMessage')
            ->willReturn($messageMock)
        ;

        $azureMock = $this->createQueueRestProxyMock();
        $azureMock
            ->expects($this->any())
            ->method('listMessages')
            ->with('aQueue', $options)
            ->willReturn($listMessagesResultMock)
        ;
         $azureMock
            ->expects($this->any())
            ->method('createMessage')
            ->with('aQueue', $messageMock->getMessageText())
            ->willReturn($createMessageResultMock)
        ;
 
        $consumer = new AzureStorageConsumer(
            $azureMock,
            new AzureStorageDestination('aQueue'),
            new AzureStorageContext($azureMock)
        );

        $receivedMessage = $consumer->receiveNoWait();

        $consumer->reject($receivedMessage, true);

        $this->assertInstanceOf(AzureStorageMessage::class, $receivedMessage);
        $this->assertSame('aBody', $receivedMessage->getBody());
    }

    public function testShouldReturnMsgOnReceiveNoWait()
    {
        $messageMock = $this->createQueueMessageMock();

        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);

        $listMessagesResultMock = $this->createMock(ListMessagesResult::class);
        $listMessagesResultMock
            ->expects($this->any())
            ->method('getQueueMessages')
            ->willReturn([$messageMock])
        ;

        $azureMock = $this->createQueueRestProxyMock();
        $azureMock
            ->expects($this->any())
            ->method('listMessages')
            ->with('aQueue', $options)
            ->willReturn($listMessagesResultMock)
        ;

        $consumer = new AzureStorageConsumer(
            $azureMock,
            new AzureStorageDestination('aQueue'),
            new AzureStorageContext($azureMock)
        );

        $receivedMessage = $consumer->receiveNoWait();
        $this->assertInstanceOf(AzureStorageMessage::class, $receivedMessage);
        $this->assertSame('aBody', $receivedMessage->getBody());
    }

    /**
     * testShouldFailOnReceiveNoWaitInvalidJsonMessage.
     *
     * @param string $exceptionClass
     * @param string $exceptionMessage
     * @param string $messageText
     *
     * @dataProvider dpTestShouldFailOnReceiveNoWaitInvalidJsonMessage
     */
    public function testShouldFailOnReceiveNoWaitInvalidJsonMessage(
        string $exceptionClass,
        string $exceptionMessage,
        string $messageText
    ): void {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        $messageMock = $this->createQueueMessageMock(base64_encode($messageText));

        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);

        $listMessagesResultMock = $this->createMock(ListMessagesResult::class);
        $listMessagesResultMock
            ->expects($this->any())
            ->method('getQueueMessages')
            ->willReturn([$messageMock])
        ;

        $azureMock = $this->createQueueRestProxyMock();
        $azureMock
            ->expects($this->any())
            ->method('listMessages')
            ->with('aQueue', $options)
            ->willReturn($listMessagesResultMock)
        ;

        $consumer = new AzureStorageConsumer(
            $azureMock,
            new AzureStorageDestination('aQueue'),
            new AzureStorageContext($azureMock)
        );

        $consumer->receiveNoWait();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueRestProxy
     */
    private function createQueueRestProxyMock()
    {
        return $this->createMock(QueueRestProxy::class);
    }

    /**
     * @param string|null $messageText
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueMessage
     */
    private function createQueueMessageMock(?string $messageText = null)
    {
        $insertionDateMock = $this->createMock(\DateTime::class);
        $insertionDateMock
            ->expects($this->any())
            ->method('getTimestamp')
            ->willReturn(1542809366);

        $message = new AzureStorageMessage();
        $message->setBody('aBody');
        $messageText = $messageText ?? $message->getMessageText();

        $messageMock = $this->createMock(QueueMessage::class);
        $messageMock
            ->expects($this->any())
            ->method('getMessageId')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getMessageText')
            ->willReturn($messageText);
        $messageMock
            ->expects($this->any())
            ->method('getInsertionDate')
            ->willReturn($insertionDateMock);
        $messageMock
            ->expects($this->any())
            ->method('getDequeueCount')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getDequeueCount')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getExpirationDate')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getExpirationDate')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getTimeNextVisible')
            ->willReturn('any');
        return $messageMock;
    }

    public function dpTestShouldFailOnReceiveNoWaitInvalidJsonMessage(): array
    {
        return [
            'invalid JSON' => [
                LogicException::class,
                'Invalid JSON content for the message.'
                . ' Error was: "Syntax error" and message content is: "invalidJSON".',
                'invalidJSON'
            ],
            'no body' => [
                LogicException::class,
                'Missing body in the message.',
                '[]'
            ],
            'no properties' => [
                LogicException::class,
                'Missing properties in the message.',
                '{"body": ""}'
            ],
        ];
    }
}
