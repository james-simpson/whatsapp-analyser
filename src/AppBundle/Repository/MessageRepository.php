<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class MessageRepository extends EntityRepository
{
	/**
     * Returns the first X messages of a chat.
     *
     * @param string $chatId Chat ID
     * @param int    $count  Number of messages to return
     *
     * @return array Array of messages entities
     */
    public function getFirstX($chatId, $count)
    {
    	return $this->findBy(
            array('chatId' => $chatId),
            array('sendDate' => 'ASC'),
            $count
        );
    }

	/**
     * Returns the first X messages of a chat that contain the search term provided.
     *
     * @param string $chatId Chat ID
     * @param string $searchTerm String to search for
     * @param int    $count  Number of messages to return
     *
     * @return array Array of messages entities
     */
    public function getFirstXContaining($chatId, $searchTerm, $count)
    {
    	return $this->createQueryBuilder('m')
           ->where('m.chatId = :chatId')
           ->andWhere('m.message LIKE :searchTerm')
           ->setParameter('chatId', $chatId)
           ->setParameter('searchTerm', "%{$searchTerm}%")
           ->setFirstResult(0)
           ->setMaxResults($count)
           ->getQuery()
           ->getResult();
    }

	/**
     * Returns the number of messages of a chat that contain the search term provided.
     *
     * @param string $chatId Chat ID
     * @param string $searchTerm String to search for
     *
     * @return int Message count
     */
    public function countAllContaining($chatId, $searchTerm)
    {
        return $this->createQueryBuilder('m')
            ->select('m.sender as sender, count(m) as msgCount')
            ->where('m.chatId = :chatId')
            ->andWhere('m.message LIKE :searchTerm')
            ->setParameter('chatId', $chatId)
            ->setParameter('searchTerm', "%{$searchTerm}%")
            ->groupBy('m.sender')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the number of messages each member has sent.
     *
     * @param string $chatId Chat ID
     *
     * @return array Message counts for each sender in the chat
     */
    public function countAllGroupedBySender($chatId)
    {
        return $this->createQueryBuilder('m')
            ->select('m.sender as sender, count(m) as msgCount')
            ->where('m.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->groupBy('m.sender')
            ->getQuery()
            ->getResult();
    }
}