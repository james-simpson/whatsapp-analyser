<?php

namespace AppBundle\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Message;


class ChatExtractor
{
    // entity manager
    private $em;

    // logger
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function extractMessages(\SplFileObject &$file, string $chatId, int $batchSize)
    {

        $lineNo = 0;
        while (!$file->eof()) {
            $line = $file->fgets();
            $lineNo++;

            if (strlen($line) > 1) {

                // skip the line if it has less than two colons
                // as this usually means it's not a real message
                // e.g. someone changed the group name
                if (substr_count($line, ":") < 2) {
                    continue;
                }

                try {
                    $message = $this->getMessageFromLine($line, $chatId);
                } catch (\Throwable $t) {
                    $this->logger->info("Could not parse message from line: " . $line);
                    continue;
                }

                $this->em->persist($message);

                if ($lineNo % $batchSize == 0) {
                    // end of batch so save to the DB
                    $this->em->flush();
                }
                
            }
        }

        $this->em->flush(); // Persist objects that did not make up an entire batch
        $this->em->clear();

        // 'close' the file by setting the file handle to null
        $file = null;
    }

    /** 
     * Create a message object from a line of the chat file.
     * Example of expected line format:
     * 13/08/2016, 9:43 p.m. - Jennifer Beckingham: I'm quite indifferent about puddings.
     *
     * @param string $line
     * @param string $chatId
     * @return Message
     */
    private function getMessageFromLine(string $line, string $chatId)
    {
        $sendDateEnd = strpos($line, '-') - 1;
        $sendDateString = substr($line, 0, $sendDateEnd);
        $sendDate = \DateTime::createFromFormat('d/m/Y, h:i a', $sendDateString);
        if ($sendDate === false) {
            throw new \Exception();
        }           

        $nameStart = strpos($line, '-') + 2;
        $nameEnd = strpos($line, ':', $nameStart);
        $name = substr($line, $nameStart, $nameEnd - $nameStart);

        $messageTextStart = $nameEnd + 2;
        $messageText = trim(substr($line, $messageTextStart));

        $message = new Message();
        $message->setChatId($chatId);
        $message->setMessage($messageText);
        $message->setSender($name);
        $message->setSendDate($sendDate);

        $this->logger->info("Sender: " . $name);
        $this->logger->info("Line: " . $line);

        return $message;
    }
}