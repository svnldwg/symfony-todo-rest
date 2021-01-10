<?php

namespace App\Controller;

use App\Entity\ToDo;
use App\Repository\ToDoRepository;
use App\Service\ToDoSerializer;
use App\Service\ToDoValidator;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ToDoController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ToDoRepository $toDoRepository,
        private ToDoSerializer $toDoSerializer,
        private ToDoValidator $toDoValidator,
        private UrlGeneratorInterface $urlGenerator
    ) {
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
        $toDo = $this->toDoSerializer->deserializeRequestIntoNew($request->getContent());
        $this->toDoValidator->validate($toDo);

        $this->entityManager->persist($toDo);
        $this->entityManager->flush();

        $resourceUrl = $this->urlGenerator->generate(
            'show_todo',
            ['id' => $toDo->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json($toDo, JsonResponse::HTTP_CREATED, ['Location' => $resourceUrl, ]);
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

        return $this->json($toDos);
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
        return $this->json($toDo);
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
    public function delete(ToDo $toDo): JsonResponse
    {
        $this->entityManager->remove($toDo);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
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
    public function update(ToDo $toDo, Request $request): JsonResponse
    {
        $this->toDoSerializer->deserializeRequestIntoExisting($request->getContent(), $toDo);
        $this->toDoValidator->validate($toDo);

        $this->entityManager->flush();

        return $this->json($toDo);
    }
}
