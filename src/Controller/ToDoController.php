<?php

namespace App\Controller;

use App\Entity\ToDo;
use App\Repository\ToDoRepository;
use App\Service\ToDoRequestValidator;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
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
     * Add a new ToDo item
     *
     * @Route("/api/todos", name="add_todo", methods={"POST"})
     * @OA\RequestBody(
     *     request="ToDo",
     *     description="The ToDo item to be created",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=ToDo::class, groups={"request"}))
     * )
     * @OA\Response(
     *     response=201,
     *     description="The created ToDo item",
     *     @OA\JsonContent(ref=@Model(type=ToDo::class))
     * )
     * @OA\Response(response=400, description="Bad Request")
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $toDo = $this->requestValidator->validate($request->getContent());
        } catch (BadRequestHttpException $exception) {
            return new JsonResponse(
                ['error' => json_decode($exception->getMessage(), true) ?? $exception->getMessage(), ],
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
     * List all ToDo items
     *
     * @Route("/api/todos", name="list_todos", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="All existing ToDo items",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=ToDo::class))
     *     )
     * )
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
     * Show a single ToDo item
     *
     * @Route("/api/todos/{id<\d+>}", name="show_todo", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="ToDo item",
     *     @OA\JsonContent(ref=@Model(type=ToDo::class))
     * )
     * @OA\Response(response=404, description="ToDo item not found")
     */
    public function show(ToDo $toDo): JsonResponse
    {
        $responseJsonData = $this->convertToDoToJson($toDo);

        return new JsonResponse($responseJsonData, Response::HTTP_OK, [], true);
    }

    /**
     * TODO: api doc
     *
     * @Route("/api/todos/{id<\d+>}", name="delete_todo", methods={"DELETE"})
     */
    public function delete(ToDo $toDo): Response
    {
        $this->toDoRepository->delete($toDo);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * TODO: api doc
     *
     * @Route("/api/todos/{id<\d+>}", name="update_todo", methods={"PUT"})
     */
    public function update(int $id, Request $request): Response
    {
        try {
            // @TODO https://symfony.com/doc/current/components/serializer.html#deserializing-in-an-existing-object
            $toDoUpdate = $this->requestValidator->validate($request->getContent());
        } catch (BadRequestHttpException $exception) {
            return new JsonResponse(
                ['error' => json_decode($exception->getMessage(), true) ?? $exception->getMessage(), ],
                $exception->getStatusCode(),
            );
        }

        $toDo = $this->toDoRepository->find($id);

        if ($toDo === null) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

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
