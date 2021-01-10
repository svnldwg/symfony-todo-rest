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
                        {"name": "some task"},
                        {"name": "another task", "description": "with description"}
                    ]
                }
            JSON);

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        static::assertSame('http://localhost/api/todos/1', $response->headers->get('Location'));
        static::assertJson($response->getContent());

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
                            },
                            {
                                "name": "another task",
                                "description": "with description"
                            }
                        ],
                        "updatedAt": "%s",
                        "createdAt": "%s"
                    }
                JSON,
            $timestamps['updatedAt'],
            $timestamps['createdAt']
        );
        static::assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    /**
     * @dataProvider createToDoInvalidBodyDataProvider
     */
    public function testCreateToDoBadRequest(string $requestBody, string $expectedJson): void
    {
        $this->postJson('/api/todos', $requestBody);

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertJson($response->getContent());

        static::assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    public function createToDoInvalidBodyDataProvider(): array
    {
        return [
            [
                <<<'JSON'
                    {
                        "description": "my first ToDo but I forgot the name"
                    }
                    JSON,
                <<<'JSON'
                    {
                         "errors": [
                              "name: This value should not be blank."
                         ]
                    }
                    JSON
            ],
            [
                <<<'JSON'
                    {
                        "name": ""
                    }
                    JSON,
                <<<'JSON'
                    {
                         "errors": [
                              "name: This value should not be blank."
                         ]
                    }
                    JSON
            ],
            [
                <<<'JSON'
                    {
                        "name": "       ",
                        "tasks": [
                            {
                                "name": "    "
                            }
                        ]
                    }
                    JSON,
                <<<'JSON'
                    {
                         "errors": [
                              "name: This value should not be blank.",
                              "tasks[0].name: This value should not be blank."
                         ]
                    }
                    JSON
            ],
            [
                <<<'JSON'
                    {
                        "name": "ToDo",
                        "unknown": "unknown"
                    }
                    JSON,
                <<<'JSON'
                    {
                         "errors": [
                            "Extra attributes are not allowed (\"unknown\" are unknown)."
                         ]
                    }
                    JSON
            ],
            [
                <<<'JSON'
                    {
                        "name": "ToDo",
                        "id": 1
                    }
                    JSON,
                <<<'JSON'
                    {
                         "errors": [
                            "Extra attributes are not allowed (\"id\" are unknown)."
                         ]
                    }
                    JSON
            ],
        ];
    }

    public function testGetSingleToDo(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->client->request('GET', '/api/todos/2');

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

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
        static::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        static::assertEmpty($response->getContent());
    }

    public function testGetAllToDos(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->client->request('GET', '/api/todos');

        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('application/json', $response->headers->get('Content-Type'));
        static::assertJson($response->getContent());

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

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('application/json', $response->headers->get('Content-Type'));
        static::assertJson($response->getContent());

        static::assertJsonStringEqualsJsonString('[]', $response->getContent());
    }

    public function testDelete(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->client->request('DELETE', '/api/todos/1');

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertEmpty($response->getContent());
    }

    public function testTryToDeleteNonExistingToDo(): void
    {
        $this->client->request('DELETE', '/api/todos/1');

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        static::assertEmpty($response->getContent());
    }

    /**
     * @dataProvider updateToDoDataProvider
     */
    public function testUpdateToDo(string $requestBody, string $expectedResponse): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->putJson('/api/todos/2', $requestBody);

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertJson($response->getContent());

        $timestamps = $this->assertTimestamps(json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $expectedJson = sprintf(
            $expectedResponse,
            $timestamps['updatedAt'],
            $timestamps['createdAt'],
        );
        static::assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    public function updateToDoDataProvider(): array
    {
        return [
            [
                <<<'JSON'
                          {"description": "new description"}
                    JSON,
                <<<'JSON'
                          {
                              "id": 2,
                              "name": "ToDo 2",
                              "description": "new description",
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
                    JSON
            ],
            [
                <<<'JSON'
                          {
                            "name": "Updated name",
                            "description": "Updated description",
                            "tasks": [
                                {"name": "Updated first task"},
                                {"name": "Updated second task", "description": "I have a description now"},
                                {"name": "Newly added third task"}
                            ]
                          }
                    JSON,
                <<<'JSON'
                          {
                              "id": 2,
                              "name": "Updated name",
                              "description": "Updated description",
                              "tasks": [
                                  {
                                      "name": "Updated first task",
                                      "description": null
                                  },
                                  {
                                      "name": "Updated second task",
                                      "description": "I have a description now"
                                  },
                                  {
                                      "name": "Newly added third task",
                                      "description": null
                                  }
                              ],
                              "updatedAt": "%s",
                              "createdAt": "%s"
                          }
                    JSON
            ],
        ];
    }

    public function testTryToUpdateWithInvalidBody(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->putJson('/api/todos/1', <<<'JSON'
                {
                    "name": ""
                }
            JSON);

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertJson($response->getContent());
        $expectedJson = <<<'JSON'
                    {
                        "errors": [
                          "name: This value should not be blank."
                        ]
                    }
            JSON;
        static::assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    public function testTryToUpdateNonExistingToDo(): void
    {
        $this->putJson('/api/todos/1', <<<'JSON'
                {
                    "name": "some name"
                }
            JSON);

        $response = $this->client->getResponse();
        static::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        static::assertEmpty($response->getContent());
    }

    public function testNotFoundRoute(): void
    {
        $this->client->request('GET', '/api/todos/not-found-route');

        static::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider invalidMethodDataProvider
     */
    public function testMethodNotAllowed(string $method, string $uri): void
    {
        $this->client->request($method, $uri);

        static::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $this->client->getResponse()->getStatusCode());
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
        static::assertAtomDateTime($toDo['updatedAt']);
        static::assertAtomDateTime($toDo['createdAt']);

        return [
            'updatedAt' => $toDo['updatedAt'],
            'createdAt' => $toDo['createdAt'],
        ];
    }
}
