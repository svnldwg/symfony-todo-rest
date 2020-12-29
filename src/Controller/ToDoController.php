<?php

namespace App\Controller;

use App\Repository\ToDoRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ToDoController
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
     * @Route("/todos", name="add_todo", methods={"POST"})
     */
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;

        if ($name === null) {
            throw new NotFoundHttpException('Expecting mandatory parameter "name"!');
        }

        $this->toDoRepository->save($name);

        return new JsonResponse(['status' => 'ToDo persisted!'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/todos", name="list_todos", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $toDos = $this->toDoRepository->findAll();

        $jsonData = $this->serializer->serialize($toDos, 'json');

        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }
}
