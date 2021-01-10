<?php

namespace App\Tests\Controller;

use App\Tests\Fixtures\ToDoLoader;
use App\Tests\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ToDoControllerTest extends WebTestCaseWithDatabase
{
    public function testCreateToDo(): void
    {
        $this->postJson('/api/todos', <<<'JSON'
                {
                    "name": "some name",
                    "description": "some description",
                    "tasks": [
                        {"name": "some task"}
                    ]
                }
            JSON);

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('http://localhost/api/todos/1', $response->headers->get('Location'));
        self::assertJson($response->getContent());

        $timestamps = $this->assertTimestamps(json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));

        $expectedJson = sprintf(
            <<<'JSON'
                    {
                        "id": 1,
                        "name": "some name",
                        "description": "some description",
                        "tasks": [
                            {
                                "name": "some task",
                                "description": null
                            }
                        ],
                        "updatedAt": "%s",
                        "createdAt": "%s"
                    }
                JSON,
            $timestamps['updatedAt'],
            $timestamps['createdAt']
        );
        self::assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    public function testGetSingleToDo(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->client->request('GET', '/api/todos/2');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $responseArray = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $timestamps = $this->assertTimestamps($responseArray);

        $expectedJson = sprintf(
            <<<'JSON'
                    {
                        "id": 2,
                        "name": "ToDo 2",
                        "description": null,
                        "tasks": [
                            {
                                "name": "Task 1 for ToDo 2",
                                "description": "Task description"
                            },
                            {
                                "name": "Task 2 for ToDo 2",
                                "description": null
                            }
                        ],
                        "updatedAt": "%s",
                        "createdAt": "%s"
                    }
                JSON,
            $timestamps['updatedAt'],
            $timestamps['createdAt'],
        );

        static::assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    public function testGetNonExistingToDo(): void
    {
        $this->client->request('GET', '/api/todos/1');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertEmpty($response->getContent());
    }

    public function testGetAllToDos(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->client->request('GET', '/api/todos');

        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson($response->getContent());

        $responseArray = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        static::assertIsArray($responseArray);
        static::assertCount(2, $responseArray);
        $timestamps1 = $this->assertTimestamps($responseArray[0]);
        $timestamps2 = $this->assertTimestamps($responseArray[1]);

        $expectedJson = sprintf(
            <<<'JSON'
                    [
                        {
                            "id": 1,
                            "name": "ToDo 1",
                            "description": "Description 1",
                            "tasks": [],
                            "updatedAt": "%s",
                            "createdAt": "%s"
                        },
                        {
                            "id": 2,
                            "name": "ToDo 2",
                            "description": null,
                            "tasks": [
                                {
                                    "name": "Task 1 for ToDo 2",
                                    "description": "Task description"
                                },
                                {
                                    "name": "Task 2 for ToDo 2",
                                    "description": null
                                }
                            ],
                            "updatedAt": "%s",
                            "createdAt": "%s"
                        }
                    ]
                JSON,
            $timestamps1['updatedAt'],
            $timestamps1['createdAt'],
            $timestamps2['updatedAt'],
            $timestamps2['createdAt'],
        );

        static::assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    public function testEmptyListOfTodos(): void
    {
        $this->client->request('GET', '/api/todos');

        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson($response->getContent());

        self::assertJsonStringEqualsJsonString('[]', $response->getContent());
    }

    public function testDelete(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->client->request('DELETE', '/api/todos/1');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertEmpty($response->getContent());
    }

    public function testTryToDeleteNonExistingToDo(): void
    {
        $this->client->request('DELETE', '/api/todos/1');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertEmpty($response->getContent());
    }

    public function testNotFoundRoute(): void
    {
        $this->client->request('GET', '/api/todos/not-found-route');

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider invalidMethodDataProvider
     */
    public function testMethodNotAllowed(string $method, string $uri): void
    {
        $this->client->request($method, $uri);

        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $this->client->getResponse()->getStatusCode());
    }

    public function invalidMethodDataProvider(): array
    {
        return [
            [Request::METHOD_DELETE, '/api/todos'],
            [Request::METHOD_PUT, '/api/todos'],
            [Request::METHOD_POST, '/api/todos/1'],
        ];
    }

    private function assertTimestamps(array $toDo): array
    {
        self::assertAtomDateTime($toDo['updatedAt']);
        self::assertAtomDateTime($toDo['createdAt']);

        return [
            'updatedAt' => $toDo['updatedAt'],
            'createdAt' => $toDo['createdAt'],
        ];
    }
}
