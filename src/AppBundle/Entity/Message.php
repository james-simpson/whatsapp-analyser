<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="message")
 */
class Message
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $chatId;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $sender;

    /**
     * @ORM\Column(type="datetime")
     */
    private $sendDate;

    /**
     * Get message id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /** 
     * Set chat id
     *
     * @param string $chatId
     * @return Message
     */
    public function setChatId($chatId)
    {
        $this->chatId = $chatId;
        return $this;
    }

    /**
     * Get chat id
     *
     * @return string
     */
    public function getChatId()
    {
        return $this->chatId;
    }

    /** 
     * Set message text
     *
     * @param string $message
     * @return Message
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message text
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /** 
     * Set sender
     *
     * @param string $sender
     * @return Message
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /** 
     * Set date/time message was sent
     *
     * @param datetime $datetime
     * @return Message
     */
    public function setSendDate($datetime)
    {
        $this->sendDate = $datetime;
        return $this;
    }

    /**
     * Get date/time message was sent
     *
     * @return datetime
     */
    public function getSendDate()
    {
        return $this->sendDate;
    }
}