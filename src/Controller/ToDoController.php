<?php

namespace App\Controller;

use App\Entity\ToDo;
use App\Repository\ToDoRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
        // @TODO handle incorrect parameters gracefully (good exception messages, correct http status)
        $toDo = $this->serializer->deserialize($request->getContent(), ToDo::class, 'json');

        $this->toDoRepository->save($toDo);

        $todoJson = $this->serializer->serialize($toDo, 'json', [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($todoJson, Response::HTTP_CREATED, [], true);
    }

    /**
     * @Route("/todos", name="list_todos", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $toDos = $this->toDoRepository->findAll();

        // outside primitive should be an object to prevent JSON Hijacking
        $responseData = ['todos' => $toDos];

        $responseJsonData = $this->serializer->serialize($responseData, 'json', [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (ToDo $object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($responseJsonData, Response::HTTP_OK, [], true);
    }
}
