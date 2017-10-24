<?php

namespace Tests\AppBundle\Service;

use AppBundle\Service\ChatExtractor;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\Message;

class ChatExtractorTest extends TestCase
{
	/**
     * @test
     * Tests that a line that contains less than two colons 
     * is skipped. This is because this usually indicates that it's
     * not in the correct format so is not a real message
     * e.g. someone changed the group name.
     */
    public function test_line_containing_less_than_two_colons()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('persist');

        $logger = $this->createMock(LoggerInterface::class);

        $fileObject = $this->getMockBuilder('SplFileObject')
        	->setConstructorArgs(['php://memory'])
        	->getMock();

		$fileObject
		    ->expects($this->once())
		    ->method('fgets')
		    ->will($this->onConsecutiveCalls('only one colon:'));
		$fileObject
		    ->expects($this->any())
		    ->method('eof')
		    ->will($this->onConsecutiveCalls(false, true));

        $chatExtractor = new ChatExtractor($em, $logger);

        $chatExtractor->extractMessages($fileObject, 'a1b2c3', 10);
    }

    /**
     * @test
     * Tests that for a line in the expected format a message
     * object is created and persisted.
     */
    public function test_valid_message_line()
    {
        $line = "13/08/2016, 9:43 p.m. - Sender: message";

        $em = $this->createMock(EntityManagerInterface::class);

        $msgObject = new Message();
        $msgObject->setChatId('a1b2c3');
        $msgObject->setMessage('message');
        $msgObject->setSender('Sender');
        $sendDate = new \DateTime();
        $sendDate->setDate(2016, 8, 13);
        $sendDate->setTime(21, 43);
        $msgObject->setSendDate($sendDate);

        $em->expects($this->once())
            ->method('persist')
            ->with($msgObject);
        $em->expects($this->once())
            ->method('flush');
        $em->expects($this->once())
            ->method('clear');

        $logger = $this->createMock(LoggerInterface::class);

        $fileObject = $this->getMockBuilder('SplFileObject')
            ->setConstructorArgs(['php://memory'])
            ->getMock();

        $fileObject
            ->expects($this->once())
            ->method('fgets')
            ->will($this->onConsecutiveCalls($line));
        $fileObject
            ->expects($this->any())
            ->method('eof')
            ->will($this->onConsecutiveCalls(false, true));

        $chatExtractor = new ChatExtractor($em, $logger);

        $chatId = 'a1b2c3';
        $chatExtractor->extractMessages($fileObject, $chatId, 10);
    }

    /**
     * @test
     * Tests that for a line not in the expected format a message
     * nothing is persisted and a debug log is written.
     */
    public function test_invalid_message_line()
    {
        $line = "Invalid message::";

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('persist');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with("Could not parse message from line: {$line}");

        $fileObject = $this->getMockBuilder('SplFileObject')
            ->setConstructorArgs(['php://memory'])
            ->getMock();

        $fileObject
            ->expects($this->once())
            ->method('fgets')
            ->will($this->onConsecutiveCalls($line));
        $fileObject
            ->expects($this->any())
            ->method('eof')
            ->will($this->onConsecutiveCalls(false, true));

        $chatExtractor = new ChatExtractor($em, $logger);

        $chatId = 'a1b2c3';
        $chatExtractor->extractMessages($fileObject, $chatId, 10);
    }

    /**
     * @test
     * Tests that for a file that contains 6 valid lines, when extractMessages
     * called with a batch size of 5, all the messages are parsed
     * and persisted. Also verify thatthey are persisted in two batches, 
     * one of 5 and one for the remaining message.
     */
    public function test_inserting_batch_of_valid_messages()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $msgObject = new Message();
        $msgObject->setChatId('a1b2c3');
        $msgObject->setMessage('message');
        $msgObject->setSender('Sender');
        $sendDate = new \DateTime();
        $sendDate->setDate(2016, 8, 13);
        $sendDate->setTime(21, 43);
        $msgObject->setSendDate($sendDate);

        $lines = [];
        $messages = [];

        for ($i = 1; $i <= 6; $i++) {
            $lines[] = "13/08/2016, 9:43 p.m. - Sender: message {$i}";
            $msgObject->setMessage("message {$i}");
            $messages[] = $msgObject;
        }
        $em->expects($this->exactly(6))
            ->method('persist')
            ->withConsecutive(...$messages);      
        $em->expects($this->exactly(2))
            ->method('flush');
        $em->expects($this->once())
            ->method('clear');

        $logger = $this->createMock(LoggerInterface::class);

        $fileObject = $this->getMockBuilder('SplFileObject')
            ->setConstructorArgs(['php://memory'])
            ->getMock();

        $fileObject
            ->expects($this->exactly(6))
            ->method('fgets')
            ->will($this->onConsecutiveCalls(...$lines));
        $fileObject
            ->expects($this->any())
            ->method('eof')
            ->will($this->onConsecutiveCalls(false, false, false, false, false, false, true));

        $chatExtractor = new ChatExtractor($em, $logger);

        $chatId = 'a1b2c3';
        $chatExtractor->extractMessages($fileObject, $chatId, 5);
    }
}