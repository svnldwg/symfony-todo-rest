<?php

namespace App\Tests\Controller;

use App\Tests\Fixtures\ToDoLoader;
use App\Tests\WebTestCaseWithDatabase;

class ToDoControllerTest extends WebTestCaseWithDatabase
{
    public function testNotFoundRoute(): void
    {
        $this->client->request('GET', '/api/todos/not-found-route');

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

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
        self::assertSame(201, $response->getStatusCode());
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

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson($response->getContent());

        self::assertJsonStringEqualsJsonString('[]', $response->getContent());
    }

    public function testDelete(): void
    {
        $this->addFixture(ToDoLoader::class);

        $this->client->request('DELETE', '/api/todos/1');

        $response = $this->client->getResponse();
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getContent());
    }

    public function testTryToDeleteNonExistingToDo(): void
    {
        $this->client->request('DELETE', '/api/todos/1');

        $response = $this->client->getResponse();
        self::assertSame(404, $response->getStatusCode());
        self::assertEmpty($response->getContent());
    }
}
