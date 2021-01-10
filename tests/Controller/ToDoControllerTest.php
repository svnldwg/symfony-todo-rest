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

        $responseArray = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertAtomDateTime($responseArray['updatedAt']);
        self::assertAtomDateTime($responseArray['createdAt']);
        self::assertJsonStringEqualsJsonString(json_encode([
            'id'          => 1,
            'name'        => 'some name',
            'description' => 'some description',
            'tasks'       => [
                ['name' => 'some task', 'description' => null],
            ],
            'updatedAt' => $responseArray['updatedAt'],
            'createdAt' => $responseArray['createdAt'],
        ], JSON_THROW_ON_ERROR), $response->getContent());
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
}
