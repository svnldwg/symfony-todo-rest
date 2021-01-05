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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
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
     * @OA\Tag(name="ToDo")
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
     * @OA\Response(
     *     response=400,
     *     description="Bad Request",
     *     @OA\JsonContent(ref="#/components/schemas/BadRequestError")
     * ),
     * @OA\Response(response="default", description="Unexpected error", @OA\JsonContent(ref="#/components/schemas/ErrorModel"))
     */
    public function create(Request $request): JsonResponse
    {
        $toDo = $this->requestValidator->deserialize($request->getContent());

        $this->toDoRepository->save($toDo);

        $responseJsonData = $this->serializeToJson($toDo);
        $resourceUrl = $this->urlGenerator->generate(
            'show_todo',
            ['id' => $toDo->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $responseJsonData,
            Response::HTTP_CREATED,
            [
                'Location' => $resourceUrl,
            ],
            true
        );
    }

    /**
     * List all ToDo items
     *
     * @Route("/api/todos", name="list_todos", methods={"GET"})
     * @OA\Tag(name="ToDo")
     * @OA\Response(
     *     response=200,
     *     description="All existing ToDo items",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=ToDo::class))
     *     )
     * ),
     * @OA\Response(response="default", description="Unexpected error", @OA\JsonContent(ref="#/components/schemas/ErrorModel"))
     */
    public function list(): JsonResponse
    {
        $toDos = $this->toDoRepository->findAll();

        $responseJsonData = $this->serializer->serialize($toDos, JsonEncoder::FORMAT, [
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
     * @OA\Tag(name="ToDo")
     * @OA\Response(
     *     response=200,
     *     description="ToDo item",
     *     @OA\JsonContent(ref=@Model(type=ToDo::class))
     * )
     * @OA\Response(response=404, description="ToDo item not found"),
     * @OA\Response(response="default", description="Unexpected error", @OA\JsonContent(ref="#/components/schemas/ErrorModel"))
     */
    public function show(ToDo $toDo): JsonResponse
    {
        $responseJsonData = $this->serializeToJson($toDo);

        return new JsonResponse($responseJsonData, Response::HTTP_OK, [], true);
    }

    /**
     * Delete a ToDo item
     *
     * @Route("/api/todos/{id<\d+>}", name="delete_todo", methods={"DELETE"})
     * @OA\Tag(name="ToDo")
     * @OA\Response(
     *     response=204,
     *     description="ToDo deleted"
     * )
     * @OA\Response(response=404, description="ToDo item not found"),
     * @OA\Response(response="default", description="Unexpected error", @OA\JsonContent(ref="#/components/schemas/ErrorModel"))
     */
    public function delete(ToDo $toDo): Response
    {
        // @TODO write test
        $this->toDoRepository->delete($toDo);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Update existing ToDo
     *
     * @Route("/api/todos/{id<\d+>}", name="update_todo", methods={"PUT"})
     * @OA\Tag(name="ToDo")
     * @OA\RequestBody(
     *     request="ToDo",
     *     description="The ToDo item to be updated",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=ToDo::class, groups={"request"}))
     * )
     * @OA\Response(
     *     response=200,
     *     description="The updated ToDo item",
     *     @OA\JsonContent(ref=@Model(type=ToDo::class))
     * )
     * @OA\Response(
     *     response=400,
     *     description="Bad Request",
     *     @OA\JsonContent(ref="#/components/schemas/BadRequestError")
     * ),
     * @OA\Response(response="default", description="Unexpected error", @OA\JsonContent(ref="#/components/schemas/ErrorModel"))
     */
    public function update(ToDo $toDo, Request $request): Response
    {
        $this->requestValidator->deserializeIntoExisting($request->getContent(), $toDo);

        $this->entityManager->flush();

        $responseJsonData = $this->serializeToJson($toDo);

        return new JsonResponse($responseJsonData, Response::HTTP_OK, [], true);
    }

    private function serializeToJson(ToDo $toDo): string
    {
        return $this->serializer->serialize($toDo, JsonEncoder::FORMAT, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (ToDo $object) {
                return $object->getId();
            },
        ]);
    }
}
