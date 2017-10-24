<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Message;
use AppBundle\Service\ChatExtractor;

require_once __DIR__ . '/../../../vendor/autoload.php';

class MainController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/upload", name="upload")
     * Method("POST")
     */
    public function uploadAction(Request $request)
    {
        try {
            $file = $request->files->get('chatFile');

            // name the file with a unique id and move to the uploads directory
            $chatId = md5(uniqid());
            $fileName = $chatId . '.' . $file->guessExtension ();
            $file-> move($this->container->getParameter('file_directory'), $fileName);

            return new JsonResponse(array('chatId' => $chatId), 200);
        } catch ( Exception $e ) {
            $array = array('status' => 0);
            $response = new JsonResponse($array, 400);
            return $response;
        }
    }

    /**
     * @Route("/extractMessages", name="extractMessages")
     * Method("POST")
     */
    public function extractMessageAction(Request $request, ChatExtractor $chatExtractor)
    {
        $chatId = $request->request->get('chatId');
        $filePath = "uploads/{$chatId }.txt";
        $file = new \SplFileObject($filePath);

        try {
            // extract the messages from the uploaded file and save them to the DB
            $chatExtractor->extractMessages($file, $chatId, 1000);
        } catch (Throwable $t) {
            $this->get('logger')->info("Could not extract message from chat file {$chatId}");
            unlink($filePath);
            return $this->json(array('status' => 'error'));
        }
        
        // delete the chat file
        unlink($filePath);

        return $this->json(array('status' => 'OK'));
    }

    /**
     * @Route("/getAllMessages", name="getAllMessges")
     * Method("POST")
     */
    public function getAllMessagesAction(Request $request)
    {
        $chatId = $request->request->get('chatId');
        $repository = $this->getDoctrine()->getRepository(Message::class);

        $messages = $repository->getFirstX($chatId, 100);

        // serialize the messages to json
        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        $messagesJson = $serializer->serialize($messages, 'json');

        return new JsonResponse(array('messages' => $messagesJson), 200);
    }

    /**
     * @Route("/searchMessages", name="searchMessges")
     * Method("POST")
     */
    public function searchMessagesAction(Request $request)
    {
        $chatId = $request->request->get('chatId');
        $searchTerm = $request->request->get('searchTerm');
        $repository = $this->getDoctrine()->getRepository(Message::class);

        $messages = $repository->getFirstXContaining($chatId, $searchTerm, 200);
        $counts = $repository->countAllContaining($chatId, $searchTerm);

        // serialize the messages to json
        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        $messagesJson = $serializer->serialize($messages, 'json');

        return $this->json(array('messages' => $messagesJson, 'counts' => $counts));
    }

    /**
     * @Route("/chatOverview", name="chatOverview")
     * Method("POST")
     */
    public function chatOverviewAction(Request $request)
    {
        $chatId = $request->request->get('chatId');
        $repository = $this->getDoctrine()->getRepository(Message::class);

        $countsBySender = $repository->countAllGroupedBySender($chatId);

        return $this->json(array('data' => $countsBySender));
    }
}
