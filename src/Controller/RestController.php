<?php

namespace App\Controller;

use App\Repository\ToDoRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RestController
{
    private ToDoRepository $toDoRepository;
    private SerializerInterface $serializer;

    public function __construct(
        ToDoRepository $toDoRepository,
        SerializerInterface $serializer
    ) {
        $this->toDoRepository = $toDoRepository;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/rest/add", name="add_number", methods={"POST"})
     */
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $number = $data['number'] ?? null;

        if ($number === null) {
            throw new NotFoundHttpException('Expecting mandatory parameter "number"!');
        }

        $this->toDoRepository->save($number);

        return new JsonResponse(['status' => 'Number persisted!'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/rest/latest/{limit<\d+>?10}", name="latest_numbers", methods={"GET"})
     */
    public function latest(int $limit): JsonResponse
    {
        $numbers = $this->toDoRepository->findLatest($limit);

        $data = [
            'limit'   => $limit,
            'numbers' => $numbers,
        ];

        $jsonData = $this->serializer->serialize($data, 'json');

        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }
}
