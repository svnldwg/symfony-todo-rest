<?php

namespace App\Controller;

use App\Entity\ToDo;
use App\Repository\ToDoRepository;
use App\Service\ToDoRequestValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ToDoController
{
    private EntityManagerInterface $entityManager;
    private ToDoRepository $toDoRepository;
    private ToDoRequestValidator $requestValidator;
    private SerializerInterface $serializer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ToDoRepository $toDoRepository,
        ToDoRequestValidator $requestValidator,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->toDoRepository = $toDoRepository;
        $this->requestValidator = $requestValidator;
        $this->serializer = $serializer;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/todos", name="add_todo", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $toDo = $this->requestValidator->validate($request->getContent());
        } catch (BadRequestHttpException $exception) {
            return new JsonResponse(
                [
                    'error' => json_decode($exception->getMessage(), true),
                ],
                $exception->getStatusCode(),
            );
        }

        $this->toDoRepository->save($toDo);

        $todoJson = $this->convertToDoToJson($toDo);

        return new JsonResponse(
            $todoJson,
            Response::HTTP_CREATED,
            [
                'Location' => $this->urlGenerator->generate(
                    'show_todo',
                    ['id' => $toDo->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ],
            true
        );
    }

    /**
     * @Route("/todos", name="list_todos", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $toDos = $this->toDoRepository->findAll();

        $responseJsonData = $this->serializer->serialize($toDos, 'json', [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (ToDo $object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($responseJsonData, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/todos/{id<\d+>}", name="show_todo", methods={"GET"})
     */
    public function read(int $id): Response
    {
        $toDo = $this->toDoRepository->find($id);

        if ($toDo === null) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $responseJsonData = $this->convertToDoToJson($toDo);

        return new JsonResponse($responseJsonData, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/todos/{id<\d+>}", name="delete_todo", methods={"DELETE"})
     */
    public function delete(int $id): Response
    {
        $toDo = $this->toDoRepository->find($id);

        if ($toDo === null) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $this->toDoRepository->delete($toDo);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/todos/{id<\d+>}", name="update_todo", methods={"PUT"})
     */
    public function update(int $id, Request $request): Response
    {
        $toDo = $this->toDoRepository->find($id);

        if ($toDo === null) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $toDoUpdate = $this->serializer->deserialize($request->getContent(), ToDo::class, 'json');
        assert($toDoUpdate instanceof ToDo);

        $toDo->setName($toDoUpdate->getName())
            ->setDescription($toDoUpdate->getDescription())
            ->setTasks($toDoUpdate->getTasks());

        $this->entityManager->flush();

        $responseJsonData = $this->convertToDoToJson($toDo);

        return new JsonResponse($responseJsonData, Response::HTTP_OK, [], true);
    }

    /**
     * @param ToDo $toDo
     *
     * @return string
     */
    private function convertToDoToJson(ToDo $toDo): string
    {
        return $this->serializer->serialize($toDo, 'json', [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (ToDo $object) {
                return $object->getId();
            },
        ]);
    }
}
